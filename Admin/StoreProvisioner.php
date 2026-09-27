<?php

namespace App\GP247\Plugins\MultiStore\Admin;

use App\GP247\Plugins\MultiStore\AppConfig;
use GP247\Core\Models\AdminLanguage;
use GP247\Core\Models\AdminStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Create a store: the Free quota, domain normalisation, unique code/domain, the
 * store row, one description per active language and the default per-store data
 * (AdminStore::setUpDataDefault), in one transaction.
 *
 * WHY a service: this used to live only in the "Add new store" Livewire form, so a
 * script had to copy it (and could skip the quota). The form and the CLI now share
 * this one path.
 *
 * @aidlc-unit multi-store-free
 * @aidlc-story US-multi-store-free-store-provisioning-service, US-multi-store-free-store-quota
 * @aidlc-adr multi-store_free-store-quota, multi-store_store-status-vs-active
 */
class StoreProvisioner
{
    /** Store columns a caller may set; anything else is ignored. */
    private const STORE_FIELDS = [
        'logo', 'phone', 'long_phone', 'email', 'time_active', 'address', 'office', 'warehouse',
        'language', 'currency', 'template', 'domain', 'code',
    ];

    /** Description fields per language; `title` is stored in the `name` column (core 2.x rename). */
    private const DESCRIPTION_FIELDS = ['title', 'keyword', 'description', 'maintain_content', 'maintain_note'];

    /**
     * Whether no further store may be created (ROOT counts; an unlimited quota never fills).
     *
     * @return bool
     */
    public function quotaReached(): bool
    {
        return !AppConfig::isStoreQuotaUnlimited() && AdminStore::count() >= AppConfig::storeQuota();
    }

    /**
     * Create a store with its descriptions and default data.
     *
     * WHY no `status`: locking a store is the Pro tier's concern; a new store keeps
     * the DB default status=1 so it is always reachable (ADR multi-store_store-status-vs-active).
     *
     * @param array<string, string>                $store        code, domain, language, currency, template (required)
     *                                                           + optional logo, phone, long_phone, email, time_active,
     *                                                           address, office, warehouse.
     * @param array<string, array<string, string>> $descriptions lang => {title, keyword?, description?, maintain_content?, maintain_note?};
     *                                                           an active language left out gets empty text.
     * @return AdminStore
     * @throws StoreQuotaReachedException When the Free quota is reached.
     * @throws ValidationException        When a required field is missing or code/domain is taken.
     */
    public function create(array $store, array $descriptions): AdminStore
    {
        // WHY first: the quota is a commercial boundary of the Free edition and must
        // hold for every caller, not only for the button on the form.
        if ($this->quotaReached()) {
            throw new StoreQuotaReachedException(AppConfig::storeQuota());
        }

        $data = array_intersect_key($store, array_flip(self::STORE_FIELDS));
        $data['domain'] = gp247_store_process_domain((string) ($data['domain'] ?? ''));

        Validator::make($data, [
            'code'     => 'required|string|max:20|unique:"' . AdminStore::class . '",code',
            'domain'   => 'required|string|max:200|unique:"' . AdminStore::class . '",domain',
            'language' => 'required',
            'currency' => 'required',
            'template' => 'required',
        ])->validate();

        return DB::connection(GP247_DB_CONNECTION)->transaction(function () use ($data, $descriptions) {
            $created = AdminStore::create(array_map(fn ($v) => (string) $v, $data));

            $rows = [];
            foreach (array_keys(AdminLanguage::getListActive()->all()) as $lang) {
                $text = array_intersect_key($descriptions[$lang] ?? [], array_flip(self::DESCRIPTION_FIELDS));
                $rows[] = [
                    'store_id'    => $created->id,
                    'lang'        => $lang,
                    'name'        => $text['title'] ?? '',
                    'keyword'     => $text['keyword'] ?? '',
                    'description' => $text['description'] ?? '',
                    // WHY raw: admin-authored rich HTML (TinyMCE), stored like core WebsiteInfo::RICH_FIELDS.
                    'maintain_content' => $text['maintain_content'] ?? '',
                    'maintain_note'    => $text['maintain_note'] ?? '',
                ];
            }
            AdminStore::insertDescription($rows);
            AdminStore::setUpDataDefault($created);

            return $created;
        });
    }
}
