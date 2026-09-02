<?php
#App\GP247\Plugins\MultiStore\Controllers\FrontController.php
namespace App\GP247\Plugins\MultiStore\Controllers;

use App\GP247\Plugins\MultiStore\AppConfig;
use GP247\Front\Controllers\RootFrontController;

class FrontController extends RootFrontController
{
    public $plugin;

    public function __construct()
    {
        parent::__construct();
        $this->plugin = new AppConfig;
    }

    public function index() {
        return view($this->plugin->appPath.'::Front',
            [
                //
            ]
        );
    }
}
