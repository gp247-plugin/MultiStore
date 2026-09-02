<?php
#App\GP247\Plugins\MultiStore\Admin\Livewire\ResolvesStackOptions.php

namespace App\GP247\Plugins\MultiStore\Admin\Livewire;

use Illuminate\Support\Facades\Schema;

/**
 * Currency/template option lists for the store screens, inherited from the
 * core store_info screen (WebsiteInfo::shopCurrencyAvailable() /
 * frontTemplateAvailable()): `gp247/shop` and `gp247/front` are composer
 * dependencies, so their classes/helpers are always autoloadable even when
 * the site never ran `gp247:shop-install` / `gp247:front-install`. Checking
 * only class_exists()/function_exists() would crash on such a site — the
 * table check is the only reliable install signal (NFR-MAINT-001).
 *
 * @aidlc-unit multi-store-free
 * @aidlc-story GP247-v2-compat
 */
trait ResolvesStackOptions
{
    /**
     * Active currency codes, or [] when gp247/shop is not installed at DB level.
     *
     * @return array<string, string>
     */
    protected function currencyOptions(): array
    {
        if (!class_exists(\GP247\Shop\Models\ShopCurrency::class)
            || !Schema::connection(GP247_DB_CONNECTION)->hasTable(GP247_DB_PREFIX . 'shop_currency')
        ) {
            return [];
        }

        return (array) \GP247\Shop\Models\ShopCurrency::getCodeActive();
    }

    /**
     * Installed storefront templates, or [] when gp247/front is not installed
     * at DB level.
     *
     * @return array<string, mixed>
     */
    protected function templateOptions(): array
    {
        if (!function_exists('gp247_front_get_all_template_installed')
            || !Schema::connection(GP247_DB_CONNECTION)->hasTable(GP247_DB_PREFIX . 'front_layout_block')
        ) {
            return [];
        }

        return (array) gp247_front_get_all_template_installed();
    }
}
