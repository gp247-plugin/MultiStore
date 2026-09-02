<?php
#App\GP247\Plugins\MultiStore\Admin\Livewire\StoreConfigManager.php

namespace App\GP247\Plugins\MultiStore\Admin\Livewire;

use App\GP247\Plugins\MultiStore\AppConfig;
use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use GP247\Core\Models\AdminConfig;
use GP247\Core\Models\AdminLanguage;
use GP247\Core\Models\AdminStore;
use Illuminate\Contracts\View\View;

/**
 * Per-store configuration screen (v2 port of the legacy AdminLTE store_config
 * view + its config_info / config_mail / captcha / limit-per-page tabs).
 * Follows the core WebsiteInfo cutover pattern, parameterized by store id:
 * every change persists immediately inside the component, replacing the
 * removed v1 endpoints (admin_store.update, admin_config.update). Template
 * switching runs the destructive removeStore()/setupStore() hooks behind an
 * explicit confirmation, exactly like the core screen.
 *
 * @aidlc-unit multi-store-free
 * @aidlc-story GP247-v2-compat
 * @aidlc-adr ADR-001, ADR-005
 */
class StoreConfigManager extends GP247AdminComponent
{
    use ResolvesStackOptions;

    protected ?string $permission = 'admin_MultiStore';

    /** @var int|string The configured (sub-)store id. */
    public $storeId;

    /** @var array<string, mixed> Editable scalar store fields. */
    public array $store = [];

    /** @var array<string, array<string, string>> Descriptions keyed by lang => field. */
    public array $desc = [];

    /** @var array<string, bool> email_action toggles (email_action_queue excluded, as in v1). */
    public array $emailAction = [];

    /** @var array<string, string> smtp_config values keyed by config key. */
    public array $smtp = [];

    /** @var array<string, mixed> captcha_config values (captcha_page is an array). */
    public array $captcha = [];

    /** @var array<string, string> display_config (limit per page) values. */
    public array $display = [];

    /**
     * Template key awaiting confirmation before the destructive
     * removeStore()/setupStore() switch runs (same gate as core WebsiteInfo).
     *
     * @var string|null
     */
    public ?string $pendingTemplate = null;

    /** Store scalar text fields editable on this screen. */
    private const FIELDS = ['phone', 'long_phone', 'email', 'time_active', 'address', 'office', 'warehouse'];

    /** Media (image path) fields rendered with the media picker. */
    private const MEDIA = ['logo', 'icon', 'og_image'];

    /** Select fields. */
    private const SELECTS = ['language', 'currency', 'template'];

    /**
     * Per-language description fields (column `name` holds the legacy "title").
     * `maintain_content` / `maintain_note` are folded in from the legacy
     * store_maintain screen, exactly like core WebsiteInfo.
     */
    private const DESC_FIELDS = ['name', 'keyword', 'description', 'maintain_content', 'maintain_note'];

    /**
     * Description fields holding admin-authored rich HTML (TinyMCE). Stored raw —
     * gp247_clean() htmlspecialchars-escapes and would break the HTML (same
     * exception as core WebsiteInfo::RICH_FIELDS); safe: RBAC-gated + Layer-2.
     */
    private const RICH_FIELDS = ['maintain_content'];

    /**
     * Load the store, its descriptions and its per-store config groups.
     *
     * @param int|string $id
     * @return void
     */
    public function mount($id = null): void
    {
        parent::mount();

        // The root store is configured on the core screens, not here (v1 rule).
        if ((string) $id === (string) GP247_STORE_ID_ROOT) {
            $this->redirect(gp247_route_admin('admin_store.index'));

            return;
        }

        $model = AdminStore::with('descriptions')->find($id);
        if ($model === null) {
            $this->redirect(gp247_route_admin('admin_MultiStore.index'));

            return;
        }

        $this->storeId = $id;

        foreach (array_merge(self::MEDIA, self::FIELDS, self::SELECTS, ['domain', 'code']) as $field) {
            $this->store[$field] = (string) ($model->{$field} ?? '');
        }

        $descriptions = $model->descriptions->keyBy('lang');
        foreach (array_keys($this->languages()) as $code) {
            foreach (self::DESC_FIELDS as $field) {
                $this->desc[$code][$field] = (string) ($descriptions[$code][$field] ?? '');
            }
        }

        foreach ($this->configRows('email_action') as $row) {
            if ($row->key === 'email_action_queue') {
                continue;
            }
            $this->emailAction[$row->key] = (bool) (int) $row->value;
        }
        foreach ($this->configRows('smtp_config') as $row) {
            $this->smtp[$row->key] = (string) $row->value;
        }
        foreach ($this->configRows('captcha_config') as $row) {
            if ($row->key === 'captcha_page') {
                $decoded = json_decode((string) $row->value, true);
                $this->captcha[$row->key] = is_array($decoded) ? $decoded : [];
            } elseif ($row->key === 'captcha_mode') {
                $this->captcha[$row->key] = (bool) (int) $row->value;
            } else {
                $this->captcha[$row->key] = (string) $row->value;
            }
        }
        foreach ($this->configRows('display_config') as $row) {
            $this->display[$row->key] = (string) $row->value;
        }
    }

    /**
     * This store's config rows for one code group, ordered by key.
     *
     * @param string $code
     * @return \Illuminate\Support\Collection
     */
    private function configRows(string $code)
    {
        return AdminConfig::where('code', $code)
            ->where('store_id', $this->storeId ?? request()->route('id'))
            ->orderBy('key')
            ->get();
    }

    /**
     * Active languages keyed by code for the description panel.
     *
     * @return array<string, mixed>
     */
    public function languages(): array
    {
        return AdminLanguage::getListActive()->all();
    }

    /**
     * Persist a scalar store field the moment it changes (Layer-2 gated).
     * Domain and code are uniqueness-checked like the legacy controller;
     * template defers to the confirmation modal (destructive switch).
     *
     * @param mixed  $value
     * @param string $key   The changed `store.<key>` segment.
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedStore($value, string $key): void
    {
        $this->authorizeAction('update');

        if (!in_array($key, array_merge(self::MEDIA, self::FIELDS, self::SELECTS, ['domain', 'code']), true)) {
            return;
        }

        $clean = gp247_clean((string) $value);

        if ($key === 'domain') {
            $domain = function_exists('gp247_store_process_domain') ? gp247_store_process_domain($clean) : $clean;
            $taken = AdminStore::where('domain', $domain)->where('id', '<>', $this->storeId)->exists();
            if ($taken) {
                $this->notify('error', gp247_language_render('admin.store.domain_exist'));

                return;
            }
            $this->store['domain'] = $domain;
            AdminStore::where('id', $this->storeId)->update(['domain' => $domain]);
        } elseif ($key === 'code') {
            $taken = AdminStore::where('code', $clean)->where('id', '<>', $this->storeId)->exists();
            if ($taken || $clean === '') {
                $this->notify('error', gp247_language_render('multi_store.code_exist'));
                $this->store['code'] = (string) (AdminStore::where('id', $this->storeId)->value('code') ?? '');

                return;
            }
            AdminStore::where('id', $this->storeId)->update(['code' => $clean]);
        } elseif ($key === 'template') {
            // WHY: switching template runs removeStore()/setupStore(), which delete
            // this store's layout blocks/banners and reseed sample data — destructive.
            // Defer behind an explicit confirmation modal (same gate as core WebsiteInfo).
            $current = (string) (AdminStore::where('id', $this->storeId)->value('template') ?? '');
            if ($clean === '' || $clean === $current) {
                return;
            }
            $this->pendingTemplate = $clean;

            return;
        } else {
            AdminStore::where('id', $this->storeId)->update([$key => $clean]);
        }

        $this->notify('success', gp247_language_render('admin.msg_change_success'));
    }

    /**
     * Execute the deferred template switch after the admin confirms: run the
     * outgoing template's removeStore() then the new template's setupStore()
     * for THIS store id (the same hook pairing AdminStore::setUpDataDefault uses).
     *
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function confirmTemplateSwitch(): void
    {
        $this->authorizeAction('update');

        if ($this->pendingTemplate === null || $this->pendingTemplate === '') {
            return;
        }

        $key = $this->pendingTemplate;
        $oldKey = (string) (AdminStore::where('id', $this->storeId)->value('template') ?? '');
        if ($oldKey !== '' && $oldKey !== $key) {
            $oldClass = 'App\\GP247\\Templates\\' . $oldKey . '\\AppConfig';
            if (class_exists($oldClass) && method_exists($oldClass, 'removeStore')) {
                (new $oldClass())->removeStore($this->storeId);
            }
        }

        $newClass = 'App\\GP247\\Templates\\' . $key . '\\AppConfig';
        if (class_exists($newClass) && method_exists($newClass, 'setupStore')) {
            (new $newClass())->setupStore($this->storeId);
        }
        // Persist the choice even when the template ships no setupStore() hook.
        AdminStore::where('id', $this->storeId)->update(['template' => $key]);

        $this->store['template'] = $key;
        $this->pendingTemplate = null;
        $this->notify('success', gp247_language_render('admin.msg_change_success'));
    }

    /**
     * Abort a pending template switch: snap the select back to the persisted value.
     *
     * @return void
     */
    public function cancelTemplateSwitch(): void
    {
        $this->store['template'] = (string) (AdminStore::where('id', $this->storeId)->value('template') ?? '');
        $this->pendingTemplate = null;
    }

    /**
     * Persist a per-language description field. The wire path is
     * `desc.<lang>.<field>`, so $path arrives as "<lang>.<field>".
     * Column `name` holds the legacy "title" (renamed in core 2.x).
     *
     * @param mixed  $value
     * @param string $path
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedDesc($value, string $path): void
    {
        $this->authorizeAction('update');

        [$lang, $field] = array_pad(explode('.', $path, 2), 2, '');
        if ($lang === '' || !in_array($field, self::DESC_FIELDS, true)) {
            return;
        }

        // WHY: rich HTML fields keep their markup (raw); plain-text fields are XSS-cleaned.
        $value = (string) $value;
        $value = in_array($field, self::RICH_FIELDS, true) ? $value : gp247_clean($value);

        AdminStore::updateDescription([
            'storeId' => $this->storeId,
            'lang' => $lang,
            'name' => $field,
            'value' => $value,
        ]);

        $this->notify('success', gp247_language_render('admin.msg_change_success'));
    }

    /**
     * Persist an email_action toggle for this store.
     *
     * @param mixed  $value
     * @param string $key
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedEmailAction($value, string $key): void
    {
        $this->authorizeAction('update');
        $this->persistConfig($key, $value ? 1 : 0);
    }

    /**
     * Persist an smtp_config value for this store.
     *
     * @param mixed  $value
     * @param string $key
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedSmtp($value, string $key): void
    {
        $this->authorizeAction('update');
        $this->persistConfig($key, gp247_clean((string) $value));
    }

    /**
     * Persist a captcha_config value for this store. captcha_page is a
     * checklist stored as a JSON array (legacy gp247_captcha_page format).
     *
     * @param mixed  $value
     * @param string $key   `captcha_page` updates may arrive as `captcha_page.<idx>`.
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedCaptcha($value, string $key): void
    {
        $this->authorizeAction('update');

        $root = explode('.', $key, 2)[0];
        if ($root === 'captcha_page') {
            $selected = array_values(array_filter(
                (array) ($this->captcha['captcha_page'] ?? []),
                static fn ($v): bool => $v !== '' && $v !== null && $v !== false,
            ));
            $this->persistConfig('captcha_page', json_encode($selected));

            return;
        }
        if ($root === 'captcha_mode') {
            $this->persistConfig('captcha_mode', $value ? 1 : 0);

            return;
        }

        $this->persistConfig($root, gp247_clean((string) $value));
    }

    /**
     * Persist a display_config (limit per page) value for this store.
     *
     * @param mixed  $value
     * @param string $key
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedDisplay($value, string $key): void
    {
        $this->authorizeAction('update');
        $this->persistConfig($key, (string) (int) $value);
    }

    /**
     * Write one config value for this store (key is unique per store).
     *
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    private function persistConfig(string $key, $value): void
    {
        AdminConfig::where('key', $key)
            ->where('store_id', $this->storeId)
            ->update(['value' => $value]);
        $this->notify('success', gp247_language_render('admin.msg_change_success'));
    }

    /**
     * @return View
     */
    public function render(): View
    {
        $plugin = new AppConfig;
        $languages = $this->languages();

        $languageOptions = [];
        foreach ($languages as $code => $lang) {
            $languageOptions[$code] = $lang->name;
        }

        $currencyOptions = $this->currencyOptions();
        $templateOptions = $this->templateOptions();
        $captchaMethods = function_exists('gp247_captcha_get_plugin_installed')
            ? (array) gp247_captcha_get_plugin_installed()
            : [];

        return view('Plugins/MultiStore::Admin.livewire.store_config', [
            'pathPlugin' => $plugin->appPath,
            'languages' => $languages,
            'mediaFields' => self::MEDIA,
            'fields' => self::FIELDS,
            'languageOptions' => $languageOptions,
            'currencyOptions' => $currencyOptions,
            'templateOptions' => $templateOptions,
            'captchaMethods' => $captchaMethods,
            'captchaPages' => [
                'register' => gp247_language_render('admin.captcha.captcha_page_register'),
                'forgot'   => gp247_language_render('admin.captcha.captcha_page_forgot_password'),
                'checkout' => gp247_language_render('admin.captcha.captcha_page_checkout'),
                'contact'  => gp247_language_render('admin.captcha.captcha_page_contact'),
                'review'   => gp247_language_render('admin.captcha.captcha_page_review'),
            ],
            'smtpMethodOptions' => ['' => 'None Security', 'TLS' => 'TLS', 'SSL' => 'SSL'],
            'emailActionRows' => $this->configRows('email_action')->where('key', '<>', 'email_action_queue'),
            'smtpRows' => $this->configRows('smtp_config'),
            'captchaRows' => $this->configRows('captcha_config'),
            'displayRows' => $this->configRows('display_config'),
        ])->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render('admin.store.config_store', ['id' => $this->storeId]),
        ]);
    }
}
