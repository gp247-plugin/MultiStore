<?php
#App\GP247\Plugins\MultiStore\Admin\Livewire\ProGateway.php

namespace App\GP247\Plugins\MultiStore\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

/**
 * Pro-feature gateway (Free edition). Every Pro-feature menu item points here. When
 * the Pro plugin is installed it redirects straight to the real Pro screen; when it
 * is NOT installed it renders a "this is a Pro feature" upsell page — so the Free
 * edition advertises the paid features in place, without depending on Pro's classes
 * at boot.
 *
 * @aidlc-unit multi-store-free
 * @aidlc-story US-multi-store-free-pro-upsell
 * @aidlc-adr multi-store_free-pro-split
 */
class ProGateway extends GP247AdminComponent
{
    /** @var string Gated as a Free store screen (site admin). */
    protected ?string $permission = 'admin_MultiStore';

    /** @var string The Pro feature slug from the route (e.g. store-admin). */
    public string $feature = '';

    /**
     * Feature slug → real Pro route + upsell copy keys. Adding a Pro feature means
     * one row here + one menu teaser (AppConfig::menuChildren()).
     *
     * @var array<string, array<string, string>>
     */
    private const FEATURES = [
        'store-admin' => [
            'route' => 'admin_MultiStorePro.index',
            'title' => 'multi_store.pro_store_admin',
            'desc'  => 'pro.store_admin_desc',
        ],
        'cross-catalog' => [
            'route' => 'admin_MultiStorePro.crossCatalog',
            'title' => 'multi_store.pro_cross_catalog',
            'desc'  => 'pro.cross_catalog_desc',
        ],
        'dashboard' => [
            'route' => 'admin_MultiStorePro.dashboard',
            'title' => 'multi_store.pro_dashboard',
            'desc'  => 'pro.dashboard_desc',
        ],
        'product-report' => [
            'route' => 'admin_MultiStorePro.productReport',
            'title' => 'multi_store.pro_product_report',
            'desc'  => 'pro.product_report_desc',
        ],
        // Store lock is NOT a gateway feature — it is an inline column in the store
        // list (disabled + Pro-noted when Pro is absent).
    ];

    /**
     * @param string $feature Route slug.
     * @return void
     */
    public function mount(string $feature = ''): void
    {
        parent::mount();
        $this->feature = $feature;

        // Pro installed + its route exists → open the real screen.
        $map = self::FEATURES[$feature] ?? null;
        if ($map !== null
            && function_exists('gp247_store_check_multi_store_installed')
            && gp247_extension_check_active('Plugins', 'MultiStorePro')
            && Route::has($map['route'])) {
            $this->redirect(gp247_route_admin($map['route']), navigate: true);
        }
    }

    /**
     * @return View
     */
    public function render(): View
    {
        $map = self::FEATURES[$this->feature] ?? null;
        $pathPlugin = 'Plugins/MultiStore';

        return view('Plugins/MultiStore::Admin.livewire.pro_gateway', [
            'featureTitle' => $map ? gp247_language_render($map['title']) : gp247_language_render($pathPlugin.'::lang.pro.heading'),
            'featureDesc'  => $map ? gp247_language_render($pathPlugin.'::lang.'.$map['desc']) : '',
            'pathPlugin'   => $pathPlugin,
        ])->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render($pathPlugin.'::lang.pro.heading'),
        ]);
    }
}
