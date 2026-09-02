<?php
/**
 * Plugin format 2.0
 */
#App\GP247\Plugins\MultiStore\AppConfig.php
namespace App\GP247\Plugins\MultiStore;

use App\GP247\Plugins\MultiStore\Models\ExtensionModel;
use GP247\Core\Models\AdminConfig;
use GP247\Core\Models\AdminHome;
use GP247\Core\Models\AdminMenu;
use GP247\Core\ExtensionConfigDefault;
use GP247\Core\Models\Languages;
use Illuminate\Support\Facades\DB;
class AppConfig extends ExtensionConfigDefault
{
    public function __construct()
    {
        //Read config from gp247.json
        $config = file_get_contents(__DIR__.'/gp247.json');
        $config = json_decode($config, true);
    	$this->configGroup = $config['configGroup'];
        $this->configKey = $config['configKey'];
        $this->configCode = $config['configCode'] ?? $this->configKey;
        $this->requireCore = $config['requireCore'] ?? [];
        // Core 2.1 manifest keys (requirePackages/requireExtensions kept as legacy fallback)
        $this->requirePackages = $config['requireComposerPackages'] ?? $config['requirePackages'] ?? [];
        $this->requireExtensions = $config['requireGp247Extensions'] ?? $config['requireExtensions'] ?? [];
        //Path
        $this->appPath = $this->configGroup . '/' . $this->configKey;
        //Language
        $this->title = trans($this->appPath.'::lang.title');
        //Image logo or thumb
        $this->image = $this->appPath.'/'.$config['image'];
        //
        $this->version = $config['version'];
        $this->auth = $config['auth'];
        $this->link = $config['link'];
    }

    /**
     * Install the plugin: refuse when a multi-vendor family plugin is present
     * (mutual exclusion), refuse when already installed, then write the
     * config, menu and language rows.
     *
     * @return array ['error' => 0|1, 'msg' => string]
     *
     * @aidlc-unit multi-store-free
     * @aidlc-story US-multi-store-free-vendor-exclusion
     * @aidlc-adr multi-store_vendor-exclusion
     */
    public function install()
    {
        // WHY: multi-store and the multi-vendor family are mutually exclusive
        // business models sharing admin_store with conflicting semantics. The
        // core helper owns the partner key list (MultiVendorPro / MultiVendor /
        // Pmo247), so this guard follows it automatically. It must return
        // BEFORE any write so a refused install leaves zero half-installed rows
        // (NFR-AVAIL-install-guard-atomic).
        if (gp247_store_check_multi_partner_installed()) {
            // WHY trans() on the file namespace: this plugin's DB language
            // strings are only seeded during install, so they cannot exist yet.
            return ['error' => 1, 'msg' => trans($this->appPath.'::lang.conflict_multi_vendor')];
        }

        $check = AdminConfig::where('key', $this->configKey)
            ->where('group', $this->configGroup)->first();
        if ($check) {
            //Check Plugin key exist
            $return = ['error' => 1, 'msg' =>  gp247_language_render('admin.extension.plugin_exist')];
        } else {
            //Insert plugin to config
            $dataInsert = [
                [
                    'group'  => $this->configGroup,
                    'code'    => $this->configCode,
                    'key'    => $this->configKey,
                    'sort'   => 0,
                    'store_id' => GP247_STORE_ID_GLOBAL,
                    'value'  => self::ON, //Enable extension
                    'detail' => $this->appPath.'::lang.title',
                ],
                [
                    'group'  => '',
                    'code'   => $this->configKey.'_config',
                    'key'    => 'domain_strict',
                    'sort'   => 0,
                    'store_id' => GP247_STORE_ID_GLOBAL,
                    'value'  => 0,
                    'detail' => $this->appPath.'::lang.config.domain_strict',
                ],
            ];
            try {
                AdminConfig::insert(
                    $dataInsert
                );

                // Seed language strings BEFORE the menu so the item titles render.
                Languages::insertOrIgnore(
                    $this->languageStrings()
                );

                // One shared "Multi-store" menu block owned by Free, holding the Free
                // screen AND the Pro-feature teasers (idempotent).
                $this->ensureMenu();

                (new ExtensionModel)->installExtension();
                $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.install_success')];
            } catch (\Throwable $e) {
                $return = ['error' => 1, 'msg' => $e->getMessage()];
            }
        }

        return $return;
    }

    /**
     * Effective store quota for this edition (ROOT store included).
     *
     * WHY a runtime-config query instead of a constant: the limit is a
     * commercial boundary; the Pro plugin raises it by writing a higher value
     * into the config registry at boot — without editing this public package
     * (registry pattern, ADR-015).
     *
     * @return int Maximum number of admin_store rows this edition may hold.
     *
     * @aidlc-unit multi-store-free
     * @aidlc-story US-multi-store-free-store-quota
     * @aidlc-adr multi-store_free-store-quota
     */
    public static function storeQuota(): int
    {
        return (int) config('gp247-config.multi_store.store_quota', 3);
    }

    /**
     * Whether the store quota is unlimited. A configured value of 0 or less means
     * "no limit" — the Pro edition writes 0 into the config registry at boot to lift
     * the Free cap without editing this public package (registry pattern, ADR-015).
     *
     * @return bool
     *
     * @aidlc-unit multi-store-free
     * @aidlc-story US-multi-store-free-store-quota
     * @aidlc-adr multi-store_free-store-quota
     */
    public static function isStoreQuotaUnlimited(): bool
    {
        return self::storeQuota() <= 0;
    }

    /**
     * DB language strings owned by this plugin (position `multi_store`).
     * Single source shared by install() and update() so sites installed
     * before a key existed receive it on plugin update (insertOrIgnore).
     *
     * @return array<int, array<string, string>>
     *
     * @aidlc-unit multi-store-free
     * @aidlc-story GP247-v2-compat
     */
    /**
     * The shared "Multi-store" menu block's children: the Free store list, then the
     * Pro-feature teasers (always present — the Free gateway route decides at click
     * time whether to open the real Pro screen or the upsell page). Pro-feature
     * titles carry a "(Pro)" suffix.
     *
     * @return array<int, array<string, mixed>>
     *
     * @aidlc-unit multi-store-free
     * @aidlc-story US-multi-store-free-pro-upsell
     */
    private function menuChildren(): array
    {
        // WHY: store the language CODE as the menu title (not a rendered string). The
        // sidebar renders it via gp247_language_render($node->title) at display time, so
        // the label translates per-locale and follows later lang-string edits — matching
        // core's own menu rows (e.g. admin.menu_titles.*). Freezing the rendered string
        // here would pin the label to the install-time locale and ignore later renames.
        return [
            [
                'sort'  => 1,
                'title' => 'multi_store.store_list',
                'icon'  => 'fab fa-shopify',
                'uri'   => 'admin::MultiStore',
            ],
            [
                'sort'  => 10,
                'title' => 'multi_store.pro_store_admin',
                'icon'  => 'fas fa-user-shield',
                'uri'   => 'admin::MultiStore/pro/store-admin',
            ],
            [
                'sort'  => 11,
                'title' => 'multi_store.pro_cross_catalog',
                'icon'  => 'fas fa-clone',
                'uri'   => 'admin::MultiStore/pro/cross-catalog',
            ],
            [
                // Highest sort → top of the group (the menu is ordered by sort DESC).
                'sort'  => 20,
                'title' => 'multi_store.pro_dashboard',
                'icon'  => 'fas fa-chart-line',
                'uri'   => 'admin::MultiStore/pro/dashboard',
            ],
            [
                'sort'  => 13,
                'title' => 'multi_store.pro_product_report',
                'icon'  => 'fas fa-boxes',
                'uri'   => 'admin::MultiStore/pro/product-report',
            ],
            // NOTE: store lock is NOT a menu teaser — it lives inline in the store
            // list (a Lock column, disabled + Pro-noted when Pro is absent).
        ];
    }

    /**
     * Idempotently ensure the shared menu block + all its children exist. Safe to run
     * on install AND update (existing sites gain the Pro teasers without duplicates).
     *
     * @return void
     *
     * @aidlc-unit multi-store-free
     * @aidlc-story US-multi-store-free-pro-upsell
     */
    private function ensureMenu(): void
    {
        $block = AdminMenu::where('key', $this->configKey)->first();
        $idBlock = $block
            ? $block->id
            : AdminMenu::insertGetId([
                'parent_id' => 0,
                'sort'      => 250,
                'title'     => 'multi_store.plugin_block',
                'icon'      => 'fab fa-shopify',
                'key'       => $this->configKey,
            ]);

        foreach ($this->menuChildren() as $child) {
            $exists = AdminMenu::where('parent_id', $idBlock)
                ->where('uri', $child['uri'])->exists();
            if (!$exists) {
                AdminMenu::insert(array_merge(['parent_id' => $idBlock], $child));
            }
        }
    }

    private function languageStrings(): array
    {
        return [
            ['code' => 'multi_store.select_store', 'text' => 'Select store', 'position' => 'multi_store', 'location' => 'en'],
            ['code' => 'multi_store.select_store', 'text' => 'Chọn cửa hàng', 'position' => 'multi_store', 'location' => 'vi'],
            ['code' => 'multi_store.plugin_block', 'text' => 'Multi-store', 'position' => 'multi_store', 'location' => 'en'],
            ['code' => 'multi_store.plugin_block', 'text' => 'Chuỗi cửa hàng', 'position' => 'multi_store', 'location' => 'vi'],
            ['code' => 'multi_store.top_count_order_store', 'text' => 'Top stores with the highest number of orders', 'position' => 'multi_store', 'location' => 'en'],
            ['code' => 'multi_store.top_count_order_store', 'text' => 'Top cửa hàng có số đơn hàng cao nhất', 'position' => 'multi_store', 'location' => 'vi'],
            ['code' => 'multi_store.export_order_list', 'text' => 'Export order list', 'position' => 'multi_store', 'location' => 'en'],
            ['code' => 'multi_store.export_order_list', 'text' => 'Export đơn hàng ', 'position' => 'multi_store', 'location' => 'vi'],
            ['code' => 'multi_store.store_empty', 'text' => 'Select store', 'position' => 'multi_store', 'location' => 'en'],
            ['code' => 'multi_store.store_empty', 'text' => 'Chọn cửa hàng ', 'position' => 'multi_store', 'location' => 'vi'],
            ['code' => 'multi_store.store_list', 'text' => 'Store list', 'position' => 'multi_store', 'location' => 'en'],
            ['code' => 'multi_store.store_list', 'text' => 'Hệ thống cửa hàng', 'position' => 'multi_store', 'location' => 'vi'],
            // Pro-feature teaser menu titles (always shown in Free; the gateway opens
            // the real Pro screen when Pro is installed, else the upsell page).
            ['code' => 'multi_store.pro_store_admin', 'text' => 'Store administrators (Pro)', 'position' => 'multi_store', 'location' => 'en'],
            ['code' => 'multi_store.pro_store_admin', 'text' => 'Quản trị cửa hàng (Pro)', 'position' => 'multi_store', 'location' => 'vi'],
            ['code' => 'multi_store.pro_cross_catalog', 'text' => 'Sync products (Pro)', 'position' => 'multi_store', 'location' => 'en'],
            ['code' => 'multi_store.pro_cross_catalog', 'text' => 'Đồng bộ sản phẩm (Pro)', 'position' => 'multi_store', 'location' => 'vi'],
            ['code' => 'multi_store.pro_dashboard', 'text' => 'Unified dashboard (Pro)', 'position' => 'multi_store', 'location' => 'en'],
            ['code' => 'multi_store.pro_dashboard', 'text' => 'Dashboard hợp nhất (Pro)', 'position' => 'multi_store', 'location' => 'vi'],
            ['code' => 'multi_store.pro_product_report', 'text' => 'Product revenue (Pro)', 'position' => 'multi_store', 'location' => 'en'],
            ['code' => 'multi_store.pro_product_report', 'text' => 'Doanh thu sản phẩm (Pro)', 'position' => 'multi_store', 'location' => 'vi'],
            ['code' => 'multi_store.cannot_delete', 'text' => 'Cannot delete the store currently in use', 'position' => 'multi_store', 'location' => 'en'],
            ['code' => 'multi_store.cannot_delete', 'text' => 'Không thể xóa cửa hàng đang được sử dụng', 'position' => 'multi_store', 'location' => 'vi'],
            ['code' => 'multi_store.code_exist', 'text' => 'Store code already exists or is invalid', 'position' => 'multi_store', 'location' => 'en'],
            ['code' => 'multi_store.code_exist', 'text' => 'Mã cửa hàng đã tồn tại hoặc không hợp lệ', 'position' => 'multi_store', 'location' => 'vi'],
            ['code' => 'multi_store.delete_has_data', 'text' => 'Cannot delete this store: it still has :detail. Move or remove them first.', 'position' => 'multi_store', 'location' => 'en'],
            ['code' => 'multi_store.delete_has_data', 'text' => 'Không thể xóa cửa hàng này: vẫn còn :detail. Hãy chuyển hoặc xóa chúng trước.', 'position' => 'multi_store', 'location' => 'vi'],
        ];
    }

    /**
     * Re-seed the plugin's DB language strings after a version upgrade so
     * keys added in newer versions exist on already-installed sites.
     * insertOrIgnore keeps admin-edited translations untouched.
     *
     * @param string|null $fromVersion Version installed before the file replacement.
     * @return array ['error' => 0|1, 'msg' => string]
     *
     * @aidlc-unit multi-store-free
     * @aidlc-story GP247-v2-compat
     */
    public function update(?string $fromVersion = null)
    {
        try {
            Languages::insertOrIgnore($this->languageStrings());
            // Backfill the shared menu block + Pro teasers on already-installed sites.
            $this->ensureMenu();

            return ['error' => 0, 'msg' => ''];
        } catch (\Throwable $e) {
            return ['error' => 1, 'msg' => $e->getMessage()];
        }
    }

    public function uninstall()
    {
        //Please delete all values inserted in the installation step
        try {
            (new AdminConfig)
            ->where('key', $this->configKey)
            ->orWhere('code', $this->configKey.'_config')
            ->delete();

            //Delete menu
            $menuStoreBlock = AdminMenu::where('key', $this->configKey)->first();
            if ($menuStoreBlock) {
                AdminMenu::where('parent_id', $menuStoreBlock->id)->delete();
                $menuStoreBlock->delete();
            }
            Languages::where('position', 'multi_store')->delete();

            //Admin config home
            AdminHome::where('extension', $this->appPath)->delete();

            (new ExtensionModel)->uninstallExtension();

            $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.uninstall_success')];
        } catch (\Throwable $e) {
            $return = ['error' => 1, 'msg' => $e->getMessage()];
        }

        return $return;
    }
    
    public function enable()
    {
        $process = (new AdminConfig)
            ->where('group', $this->configGroup)
            ->where('key', $this->configKey)
            ->update(['value' => self::ON]);
        //Admin config home
        AdminHome::where('extension', $this->appPath)->update(['status' => 1]);

        if (!$process) {
            $return = ['error' => 1, 'msg' => gp247_language_render('admin.extension.action_error', ['action' => 'Enable'])];
        }
        $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.enable_success')];
        return $return;
    }

    public function disable()
    {
        $return = ['error' => 0, 'msg' => ''];
        $process = (new AdminConfig)
            ->where('group', $this->configGroup)
            ->where('key', $this->configKey)
            ->update(['value' => self::OFF]);
        if (!$process) {
            $return = ['error' => 1, 'msg' => gp247_language_render('admin.extension.action_error', ['action' => 'Disable'])];
        }
        //Admin config home
        AdminHome::where('extension', $this->appPath)->update(['status' => 0]);
        $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.disable_success')];
        return $return;
    }


    // Remove setup for store

    public function removeStore($storeId = null)
    {
        // code here
    }

    // Setup for store

    public function setupStore($storeId = null)
    {
       // code here
    }


    // Process when click button plugin in admin    
    
    public function clickApp()
    {
        return redirect(gp247_route_admin('admin_MultiStore.index'));
    }

    /**
     * Get info plugin
     *
     * @return  [type]  [return description]
     */
    public function getInfo()
    {
        $arrData = [
            'title' => $this->title,
            'key' => $this->configKey,
            'code' => $this->configCode,
            'image' => $this->image,
            'permission' => self::ALLOW,
            'version' => $this->version,
            'auth' => $this->auth,
            'link' => $this->link,
            'value' => 0, // this return need for plugin shipping
            'appPath' => $this->appPath
        ];

        return $arrData;
    }
}
