<?php

namespace App\GP247\Plugins\MultiStore\Admin;

use App\GP247\Plugins\MultiStore\AppConfig;
use RuntimeException;

/**
 * The site already has as many stores as the Free edition allows (ROOT included).
 *
 * @aidlc-unit multi-store-free
 * @aidlc-story US-multi-store-free-store-provisioning-service
 * @aidlc-adr multi-store_free-store-quota
 */
class StoreQuotaReachedException extends RuntimeException
{
    /**
     * @param int $quota The store limit that was reached.
     */
    public function __construct(public readonly int $quota)
    {
        parent::__construct(trans((new AppConfig)->appPath . '::lang.quota_reached', ['quota' => $quota]));
    }
}
