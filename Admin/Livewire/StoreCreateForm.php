<?php
#App\GP247\Plugins\MultiStore\Admin\Livewire\StoreCreateForm.php

namespace App\GP247\Plugins\MultiStore\Admin\Livewire;

use App\GP247\Plugins\MultiStore\AppConfig;
use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use GP247\Core\AdminShell\Infrastructure\HasValidationLabels;
use GP247\Core\Models\AdminLanguage;
use GP247\Core\Models\AdminStore;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

/**
 * "Add new store" form (v2 port of the legacy store_add view +
 * AdminStoreListController::postCreate). Same validation and the same
 * transactional create (store + per-language descriptions + default data
 * seeding via AdminStore::setUpDataDefault). The description text field is
 * persisted into the `name` column — core 2.x renamed the legacy `title`
 * column of admin_store_description.
 *
 * Free-edition boundaries enforced here: the store quota is checked
 * server-side in save() (ROOT included, limit via AppConfig::storeQuota()),
 * and there is no `status` control — locking stores is the Pro tier's
 * marketplace-owner concern, so new stores keep the DB default status=1.
 *
 * @aidlc-unit multi-store-free
 * @aidlc-story GP247-v2-compat, US-multi-store-free-store-quota
 * @aidlc-adr ADR-001, ADR-005, multi-store_free-store-quota, multi-store_store-status-vs-active
 */
class StoreCreateForm extends GP247AdminComponent
{
    use HasValidationLabels;
    use ResolvesStackOptions;

    protected ?string $permission = 'admin_MultiStore';

    /** @var array<string, array<string, string>> Descriptions keyed by lang => field. */
    public array $descriptions = [];

    /** @var array<string, string> Scalar store fields. */
    public array $store = [
        'logo' => '',
        'phone' => '',
        'long_phone' => '',
        'email' => '',
        'time_active' => '',
        'address' => '',
        'office' => '',
        'warehouse' => '',
        'domain' => '',
        'code' => '',
        'language' => '',
        'currency' => '',
        'template' => '',
    ];

    /**
     * Default maintenance-page copy seeded into every language (the same HTML
     * the legacy postCreate() hardcoded) — shown pre-filled in the editor so
     * the admin can adjust it per language before creating the store.
     */
    private const DEFAULT_MAINTAIN_CONTENT = '<center><img src="/images/maintenance.png" />
                            <h3><span style="color:#e74c3c;"><strong>Sorry! We are currently doing site maintenance!</strong></span></h3>
                            </center>';

    /**
     * Seed empty description state and select defaults.
     *
     * @return void
     */
    public function mount(): void
    {
        parent::mount();

        foreach (array_keys($this->languages()) as $code) {
            $this->descriptions[$code] = [
                'title' => '',
                'keyword' => '',
                'description' => '',
                'maintain_content' => self::DEFAULT_MAINTAIN_CONTENT,
                'maintain_note' => '',
            ];
        }
    }

    /**
     * Active languages keyed by code.
     *
     * @return array<string, mixed>
     */
    public function languages(): array
    {
        return AdminLanguage::getListActive()->all();
    }

    /**
     * Validation rules — same shape as the legacy postCreate().
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'descriptions.*.title' => 'required|string|max:200',
            'descriptions.*.keyword' => 'nullable|string|max:200',
            'descriptions.*.description' => 'nullable|string|max:300',
            'descriptions.*.maintain_content' => 'nullable|string',
            'descriptions.*.maintain_note' => 'nullable|string|max:200',
            'store.code' => 'required|string|max:20|unique:"'.AdminStore::class.'",code',
            'store.language' => 'required',
            'store.currency' => 'required',
            'store.template' => 'required',
            'store.domain' => 'required|string|max:200|unique:"'.AdminStore::class.'",domain',
        ];
    }

    /**
     * Map every rule key to the SAME language keys the form labels render, so
     * validation errors read the localized field name instead of leaking the
     * raw Livewire state path ("descriptions.en.title") in English.
     *
     * @return array<string, string>
     */
    protected function attributeLabels(): array
    {
        return [
            'descriptions.*.title' => 'store.title',
            'descriptions.*.keyword' => 'store.keyword',
            'descriptions.*.description' => 'store.description',
            'descriptions.*.maintain_content' => 'admin.maintain.description',
            'descriptions.*.maintain_note' => 'admin.maintain.description_note',
            'store.code' => 'admin.store.code',
            'store.language' => 'store.language',
            'store.currency' => 'store.currency',
            'store.template' => 'admin.store.template',
            'store.domain' => 'admin.store.domain',
        ];
    }

    /**
     * Create the store: validate, insert store + descriptions and seed the
     * default per-store data inside one transaction, then return to the list.
     *
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function save(): void
    {
        $this->authorizeAction('create');

        // WHY server-side and BEFORE validation: the store quota is a
        // commercial boundary of the Free edition; hiding the button is UX
        // only — a Livewire action can always be called directly. Counting
        // admin_store at write time (ROOT included) is the enforcement point
        // (NFR-SEC-store-quota-server-side, ADR multi-store_free-store-quota).
        // Skip the cap entirely when the quota is unlimited (Pro raises it via config).
        $quota = AppConfig::storeQuota();
        if (!AppConfig::isStoreQuotaUnlimited() && AdminStore::count() >= $quota) {
            $this->notify('error', trans(
                (new AppConfig)->appPath.'::lang.quota_reached',
                ['quota' => $quota]
            ));

            return;
        }

        $this->store['domain'] = gp247_store_process_domain($this->store['domain']);
        $this->validate();

        $dataInsert = [
            'logo'        => $this->store['logo'],
            'phone'       => $this->store['phone'],
            'long_phone'  => $this->store['long_phone'],
            'email'       => $this->store['email'],
            'time_active' => $this->store['time_active'],
            'address'     => $this->store['address'],
            'office'      => $this->store['office'],
            'warehouse'   => $this->store['warehouse'],
            'language'    => $this->store['language'],
            'currency'    => $this->store['currency'],
            'template'    => $this->store['template'],
            'domain'      => $this->store['domain'],
            'code'        => $this->store['code'],
            // WHY no 'status' key: locking/unlocking a store is the
            // marketplace owner's Pro-tier concern (ADR
            // multi-store_store-status-vs-active); the Free edition relies on
            // the safe DB default status=1 so a new store is always reachable.
        ];

        try {
            DB::connection(GP247_DB_CONNECTION)
                ->transaction(function () use ($dataInsert) {
                    $store = AdminStore::create($dataInsert);
                    $dataDes = [];
                    foreach (array_keys($this->languages()) as $code) {
                        $dataDes[] = [
                            'store_id'    => $store->id,
                            'lang'        => $code,
                            // WHY: core 2.x renamed admin_store_description.title -> name.
                            'name'        => $this->descriptions[$code]['title'] ?? '',
                            'keyword'     => $this->descriptions[$code]['keyword'] ?? '',
                            'description' => $this->descriptions[$code]['description'] ?? '',
                            // WHY: maintain_content is admin-authored rich HTML (TinyMCE) —
                            // stored raw like core WebsiteInfo::RICH_FIELDS; RBAC-gated screen.
                            'maintain_content' => $this->descriptions[$code]['maintain_content'] ?? '',
                            'maintain_note' => $this->descriptions[$code]['maintain_note'] ?? '',
                        ];
                    }
                    AdminStore::insertDescription($dataDes);

                    // Seed the default config/layout data for the new store.
                    AdminStore::setUpDataDefault($store);
                });
        } catch (\Throwable $e) {
            $this->notify('error', $e->getMessage());

            return;
        }

        session()->flash('gp247_admin_success', gp247_language_render('action.create_success'));
        $this->redirect(gp247_route_admin('admin_MultiStore.index'));
    }

    /**
     * @return View
     */
    public function render(): View
    {
        $plugin = new AppConfig;

        $currencyOptions = $this->currencyOptions();
        $templateOptions = $this->templateOptions();
        $languageOptions = [];
        foreach ($this->languages() as $code => $lang) {
            $languageOptions[$code] = $lang->name;
        }

        return view('Plugins/MultiStore::Admin.livewire.store_add', [
            'pathPlugin' => $plugin->appPath,
            'languages' => $this->languages(),
            'languageOptions' => $languageOptions,
            'currencyOptions' => $currencyOptions,
            'templateOptions' => $templateOptions,
        ])->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render('admin.store.add_new_title'),
        ]);
    }
}
