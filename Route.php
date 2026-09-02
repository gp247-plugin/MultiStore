<?php
use Illuminate\Support\Facades\Route;
use App\GP247\Plugins\MultiStore\Admin\Livewire\ProGateway;
use App\GP247\Plugins\MultiStore\Admin\Livewire\StoreConfigManager;
use App\GP247\Plugins\MultiStore\Admin\Livewire\StoreCreateForm;
use App\GP247\Plugins\MultiStore\Admin\Livewire\StoreListManager;

$config = file_get_contents(__DIR__.'/gp247.json');
$config = json_decode($config, true);

if(gp247_extension_check_active($config['configGroup'], $config['configKey'])) {


    Route::group(
    [
        'middleware' => GP247_FRONT_MIDDLEWARE,
        'prefix'    => 'plugin/multistore',
        'namespace' => 'App\GP247\Plugins\MultiStore\Controllers',
    ],
    function () {
        Route::get('index', 'FrontController@index')
        ->name('multistore.index');
    }
);

    // v2 (Livewire + TailAdmin) — replaces the legacy AdminLTE controller screens.
    // Route names use the Free-edition key `MultiStore` (renamed from the legacy
    // `MultiStorePro` in P0.5); the AdminMenu row installed by AppConfig::install()
    // ('admin::MultiStore') and clickApp() both resolve against these names.
    // Updates run inside the components over livewire/update, so the legacy POST
    // endpoints (create/delete) are removed with their screens.
    if (class_exists(\Livewire\Livewire::class)) {
        Route::group(
            [
                'prefix' => GP247_ADMIN_PREFIX.'/MultiStore',
                'middleware' => GP247_ADMIN_MIDDLEWARE,
            ],
            function () {
                Route::get('/', StoreListManager::class)
                ->name('admin_MultiStore.index');
                Route::get('/create', StoreCreateForm::class)
                ->name('admin_MultiStore.create');
                Route::get('/config/{id}', StoreConfigManager::class)
                ->name('admin_MultiStore.config');
                // Pro-feature gateway: redirects to the real Pro screen when Pro is
                // installed, else shows the "Pro feature" upsell page.
                Route::get('/pro/{feature}', ProGateway::class)
                ->name('admin_MultiStore.pro');
            }
        );
    }
}
