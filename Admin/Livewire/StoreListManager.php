<?php
#App\GP247\Plugins\MultiStore\Admin\Livewire\StoreListManager.php

namespace App\GP247\Plugins\MultiStore\Admin\Livewire;

use App\GP247\Plugins\MultiStore\AppConfig;
use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use GP247\Core\Models\AdminConfig;
use GP247\Core\Models\AdminStore;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Store list screen (v2 port of the legacy AdminLTE store_list view of this
 * plugin): lists every store with an Active toggle per sub-store, the global
 * "domain strict" toggle, and Create / Configure / Delete actions. Replaces
 * the removed v1 endpoints (admin_store.update, admin_config_global.update)
 * by persisting directly inside the component (WebsiteInfo cutover pattern).
 * Route name `admin_MultiStore.index` matches the AdminMenu row installed by
 * AppConfig::install() ('admin::MultiStore'). The plugin was renamed from the
 * legacy key `MultiStorePro` to `MultiStore` (Free edition) in P0.5 — the core
 * multi-store check reads both keys for backward compatibility.
 *
 * @aidlc-unit multi-store-free
 * @aidlc-story GP247-v2-compat, US-multi-store-free-identity-rename
 * @aidlc-adr ADR-001, ADR-005, multi-store_free-pro-split
 */
class StoreListManager extends GP247AdminComponent
{
    protected ?string $permission = 'admin_MultiStore';

    /**
     * Store-scoped tables purged together with the store. Two shapes, both
     * safe to drop wholesale:
     *  - N-N pivots (`*_store`): the parent product / category / banner / page
     *    is shared across stores and must survive; only the join row goes.
     *  - Rows this store exclusively owns (layout blocks, subscribers, SEO
     *    redirects, abandoned carts).
     * `front_link_store` is handled separately because its parent row is
     * store-owned too. `admin_config` and `admin_store_description` are NOT
     * listed: the AdminStore `deleting` model event already removes them.
     */
    private const PURGE_TABLES = [
        'front_layout_block',
        'front_banner_store',
        'front_page_store',
        'front_subscribe',
        'front_redirects',
        'shop_product_store',
        'shop_category_store',
        'shop_shoppingcart',
    ];

    /**
     * Tables whose rows block deletion, mapped to the language key naming them
     * in the error message: financial history (orders), personal accounts
     * (customers) and master data referenced by shared products (suppliers).
     * Dropping a store must never destroy these silently — the admin has to
     * move or remove them first.
     */
    private const GUARDED_TABLES = [
        'shop_order' => 'admin.order.title',
        'shop_customer' => 'admin.customer.list',
        'shop_supplier' => 'admin.supplier.title',
    ];

    /** @var array<int|string, bool> Active flag per store id. */
    public array $active = [];

    /** @var bool Global "domain strict" flag (Plugins config, store GLOBAL). */
    public bool $domainStrict = false;

    /**
     * Load the toggles' initial state.
     *
     * @return void
     */
    public function mount(): void
    {
        parent::mount();

        $this->domainStrict = (bool) (int) gp247_config_global('domain_strict');
        foreach (AdminStore::pluck('active', 'id') as $id => $flag) {
            $this->active[$id] = (bool) (int) $flag;
        }
    }

    /**
     * Persist a store's Active toggle (Layer-2 gated). The root store cannot
     * be deactivated from this screen (same rule as the v1 view).
     *
     * @param mixed  $value
     * @param string $key   The changed store id (wire path `active.<id>`).
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedActive($value, string $key): void
    {
        $this->authorizeAction('update');

        if ((string) $key === (string) GP247_STORE_ID_ROOT) {
            $this->active[$key] = true;

            return;
        }

        AdminStore::where('id', $key)->update(['active' => $value ? 1 : 0]);
        $this->notify('success', gp247_language_render('admin.msg_change_success'));
    }

    /**
     * Persist the global "domain strict" flag installed by this plugin.
     *
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedDomainStrict(): void
    {
        $this->authorizeAction('update');

        AdminConfig::where('key', 'domain_strict')
            ->where('store_id', GP247_STORE_ID_GLOBAL)
            ->update(['value' => $this->domainStrict ? 1 : 0]);
        $this->notify('success', gp247_language_render('admin.msg_change_success'));
    }

    /**
     * Delete a store together with every row scoped to it. Refuses when the
     * store still holds business records (see GUARDED_TABLES) so a single
     * click can never destroy orders or customer accounts. The whole cascade
     * runs in one transaction: a partial cleanup would leave the store gone
     * but its pivot rows pointing at a missing id, which is exactly the
     * orphan state this method used to produce. Confirmed client-side via
     * wire:confirm.
     *
     * @param int|string|null $id Store id to delete.
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function delete($id): void
    {
        $this->authorizeAction('delete');

        if ($id === null || (string) $id === (string) GP247_STORE_ID_ROOT) {
            return;
        }
        if ((string) config('app.storeId') === (string) $id) {
            $this->notify('error', gp247_language_render('multi_store.cannot_delete'));

            return;
        }

        $blocking = $this->blockingRecords((string) $id);
        if ($blocking !== []) {
            $this->notify('error', gp247_language_render(
                'multi_store.delete_has_data',
                ['detail' => implode(', ', $blocking)]
            ));

            return;
        }

        try {
            DB::connection(GP247_DB_CONNECTION)->transaction(function () use ($id) {
                $this->purgeStoreScopedData((string) $id);
                // WHY: AdminStore::boot() deletes the description rows and the
                // admin_config rows in its `deleting` event, so they are not
                // repeated here — destroy() fires model events.
                AdminStore::destroy($id);
            });
        } catch (\Throwable $e) {
            gp247_report('[MultiStore] delete store '.$id.' failed: '.$e->getMessage());
            $this->notify('error', $e->getMessage());

            return;
        }

        unset($this->active[$id]);
        $this->notify('success', gp247_language_render('action.delete_confirm_deleted_msg'));
    }

    /**
     * Business records that must be dealt with before the store can go.
     *
     * @param string $id Store id.
     * @return array<int, string> Localized "<label> (<count>)" entries, empty when clear.
     */
    private function blockingRecords(string $id): array
    {
        $found = [];
        foreach (self::GUARDED_TABLES as $table => $langKey) {
            $query = $this->scopedQuery($table, $id);
            if ($query === null) {
                continue;
            }
            $count = $query->count();
            if ($count > 0) {
                $found[] = gp247_language_render($langKey).' ('.$count.')';
            }
        }

        return $found;
    }

    /**
     * Remove every row scoped to the store across the core/front/shop tables.
     * Must run inside a transaction opened by the caller.
     *
     * @param string $id Store id.
     * @return void
     */
    private function purgeStoreScopedData(string $id): void
    {
        // WHY: menu links are store-owned rather than shared —
        // AdminStore::setUpDataDefault() seeds a fresh front_link row per
        // store — so the parent row is deleted along with the pivot. Every
        // other pivot in PURGE_TABLES points at a shared parent that stays.
        $linkPivot = $this->scopedQuery('front_link_store', $id);
        if ($linkPivot !== null) {
            $linkIds = $linkPivot->pluck('link_id')->all();
            $this->scopedQuery('front_link_store', $id)->delete();

            $linkTable = GP247_DB_PREFIX.'front_link';
            if ($linkIds !== [] && Schema::connection(GP247_DB_CONNECTION)->hasTable($linkTable)) {
                DB::connection(GP247_DB_CONNECTION)
                    ->table($linkTable)
                    ->whereIn('id', $linkIds)
                    ->delete();
            }
        }

        foreach (self::PURGE_TABLES as $table) {
            $this->scopedQuery($table, $id)?->delete();
        }
    }

    /**
     * Query builder scoped to one store, or null when the table is absent.
     *
     * WHY the table check: gp247/front is not even a composer requirement of
     * this plugin and gp247/shop can be present in vendor/ without ever having
     * run `gp247:shop-install`, so its tables may not exist. Going through the
     * query builder instead of the Eloquent models keeps this method from
     * fataling on a class that was never autoloadable (NFR-MAINT-001) — the
     * previous implementation referenced GP247\Front models unconditionally.
     *
     * @param string $table Table name without the GP247 prefix.
     * @param string $id    Store id.
     * @return Builder|null
     */
    private function scopedQuery(string $table, string $id): ?Builder
    {
        if (!Schema::connection(GP247_DB_CONNECTION)->hasTable(GP247_DB_PREFIX.$table)) {
            return null;
        }

        return DB::connection(GP247_DB_CONNECTION)
            ->table(GP247_DB_PREFIX.$table)
            ->where('store_id', $id);
    }

    /**
     * Whether the Multi-store Pro plugin is installed. Store lock is a Pro feature;
     * when Pro is absent the store-list lock control is disabled + noted as Pro.
     *
     * @return bool
     *
     * @aidlc-unit multi-store-free
     * @aidlc-story US-multi-store-pro-platform-lock
     */
    public function proInstalled(): bool
    {
        return function_exists('gp247_extension_check_active')
            && gp247_extension_check_active('Plugins', 'MultiStorePro');
    }

    /**
     * Toggle a store's platform lock (`admin_store.status`) — a Pro feature shown in
     * the store list. Refuses when Pro is not installed (upsell), for a
     * non-administrator, and for the root store; writes ONLY `status`, never the
     * owner's `active` maintenance flag (ADR multi-store_store-status-vs-active). A
     * locked store (status=0) is dropped by AdminStore::getDomainStore() so its
     * domain stops serving.
     *
     * @param int|string $storeId
     * @return void
     *
     * @aidlc-unit multi-store-free
     * @aidlc-story US-multi-store-pro-platform-lock
     * @aidlc-adr multi-store_store-status-vs-active
     */
    public function toggleLock($storeId): void
    {
        $path = (new AppConfig)->appPath;

        // Store lock is a paid feature — gated by Pro being installed (soft gate).
        if (!$this->proInstalled()) {
            $this->notify('info', gp247_language_render($path.'::lang.lock.pro_only'));
            return;
        }
        // Platform lock is administrator-only (distinct from the owner's active flag).
        if (!app(\GP247\Core\AdminShell\Domain\AdminUserContract::class)->isAdministrator()) {
            $this->notify('error', gp247_language_render($path.'::lang.lock.denied'));
            return;
        }
        $root = defined('GP247_STORE_ID_ROOT') ? GP247_STORE_ID_ROOT : 1;
        if ((string) $storeId === (string) $root) {
            $this->notify('warning', gp247_language_render($path.'::lang.lock.root_protected'));
            return;
        }
        $store = AdminStore::find($storeId);
        if (!$store) {
            return;
        }
        $store->update(['status' => $store->status ? 0 : 1]);
        $this->notify('success', gp247_language_render(
            $store->status ? $path.'::lang.lock.unlocked' : $path.'::lang.lock.locked'
        ));
    }

    /**
     * @return View
     */
    public function render(): View
    {
        $plugin = new AppConfig;

        $stories = AdminStore::with('descriptions')->get()->keyBy('id');
        // WHY: hiding the create button at quota is UX only — the real
        // enforcement is server-side in StoreCreateForm::save()
        // (ADR multi-store_free-store-quota).
        $storeQuota = AppConfig::storeQuota();
        $quotaUnlimited = AppConfig::isStoreQuotaUnlimited();
        $storeUsed = $stories->count();

        return view('Plugins/MultiStore::Admin.livewire.store_list', [
            'stories' => $stories,
            'pathPlugin' => $plugin->appPath,
            'proInstalled' => $this->proInstalled(),
            'storeQuota' => $storeQuota,
            'storeUsed' => $storeUsed,
            // WHY: unlimited (Pro) has no remaining/reached — the indicator shows a
            // distinct "no limit" state instead of a X/N bar.
            'quotaUnlimited' => $quotaUnlimited,
            'storeRemaining' => $quotaUnlimited ? 0 : max(0, $storeQuota - $storeUsed),
            'quotaReached' => !$quotaUnlimited && $storeUsed >= $storeQuota,
        ])->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render($plugin->appPath.'::lang.title'),
        ]);
    }
}
