{{--
    Store list screen (v2 port of the legacy AdminLTE store_list view): global
    "domain strict" toggle + create button in the toolbar, then one row per
    store with website link, Active toggle (sub-stores only) and
    Configure / Delete actions. All text via gp247_language_render.

    @aidlc-unit multi-store-free
    @aidlc-story GP247-v2-compat
    @aidlc-adr ADR-005

    Variables: $stories (AdminStore collection keyed by id), $pathPlugin.
--}}
<div class="space-y-5">
    {{-- Free-edition store quota indicator: used / limit + progress + Pro awareness.
         The 3-store cap is enforced server-side in StoreCreateForm::save() (ADR
         multi-store_free-store-quota); this is the at-a-glance status. --}}
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        @if ($quotaUnlimited)
            {{-- Pro edition: the quota is lifted (config store_quota <= 0). --}}
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                        {{ gp247_language_render($pathPlugin.'::lang.quota.title') }}
                    </p>
                    <p class="mt-1 text-gray-800 dark:text-gray-100" style="font-size:22px;font-weight:700;line-height:1.1">
                        {{ $storeUsed }} <span class="text-gray-400 dark:text-gray-500">/ &infin;</span>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                        <i class="fas fa-crown mr-1"></i>{{ gp247_language_render($pathPlugin.'::lang.quota.unlimited') }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ gp247_language_render($pathPlugin.'::lang.quota.pro_active') }}
                    </p>
                </div>
            </div>
            <div class="mt-3 w-full rounded-full bg-gray-200 dark:bg-gray-700" style="height:8px">
                <div class="rounded-full bg-emerald-500" style="height:8px;width:100%"></div>
            </div>
        @else
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                        {{ gp247_language_render($pathPlugin.'::lang.quota.title') }}
                    </p>
                    <p class="mt-1 text-gray-800 dark:text-gray-100" style="font-size:22px;font-weight:700;line-height:1.1">
                        {{ $storeUsed }} <span class="text-gray-400 dark:text-gray-500">/ {{ $storeQuota }}</span>
                    </p>
                </div>
                <div class="text-right">
                    @if ($quotaReached)
                        <p class="text-sm font-semibold text-amber-500">
                            <i class="fas fa-crown mr-1"></i>{{ gp247_language_render($pathPlugin.'::lang.quota.reached_short') }}
                        </p>
                    @else
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ gp247_language_render($pathPlugin.'::lang.quota.remaining', ['n' => $storeRemaining]) }}
                        </p>
                    @endif
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ gp247_language_render($pathPlugin.'::lang.quota.free_limit') }}
                    </p>
                </div>
            </div>
            <div class="mt-3 w-full rounded-full bg-gray-200 dark:bg-gray-700" style="height:8px">
                <div class="rounded-full {{ $quotaReached ? 'bg-amber-500' : 'bg-blue-600' }}"
                    style="height:8px;width:{{ $storeQuota > 0 ? min(100, round($storeUsed / $storeQuota * 100)) : 0 }}%"></div>
            </div>
        @endif
    </div>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <label class="inline-flex cursor-pointer items-center gap-3"
                title="{{ gp247_language_render($pathPlugin.'::lang.config.domain_strict') }}">
                <input type="checkbox" wire:model.live="domainStrict" class="peer sr-only">
                <span class="relative h-6 w-11 rounded-full bg-gray-300 transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-all peer-checked:bg-blue-600 peer-checked:after:translate-x-5 dark:bg-gray-600"></span>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render($pathPlugin.'::lang.config.domain_strict') }}</span>
            </label>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                <i class="fas fa-exclamation-triangle mr-1 text-amber-500"></i>{{ gp247_language_render($pathPlugin.'::lang.config.domain_strict_help') }}
            </p>
        </div>

        @if ($quotaReached)
            {{-- UX hint only — the quota is enforced server-side in save() --}}
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                <i class="fas fa-exclamation-triangle mr-1 text-amber-500"></i>{{ gp247_language_render($pathPlugin.'::lang.quota_reached', ['quota' => $storeQuota]) }}
            </p>
        @else
            <x-gp247::button href="{{ gp247_route_admin('admin_MultiStore.create') }}" variant="primary" size="sm">
                <i class="fa fa-plus"></i> {{ gp247_language_render('admin.store.add_new') }}
            </x-gp247::button>
        @endif
    </div>

    <x-gp247::table :empty="$stories->isEmpty() ? gp247_language_render('admin.display.data_not_found') : null">
        <x-slot:head>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.store.title') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render($pathPlugin.'::lang.admin.store_url') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.store.state') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render($pathPlugin.'::lang.lock.column') }}</th>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.actions') }}</th>
            </tr>
        </x-slot:head>

        @foreach ($stories as $key => $store)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50" wire:key="store-{{ $store->id }}">
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                    <i class="fas fa-home mr-1.5 text-gray-400"></i>{{ $store->getTitle() }} (#{{ $store->code }})
                </td>
                <td class="px-4 py-3 text-sm">
                    <a href="//{{ $store->domain }}" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-1.5 text-blue-600 hover:underline dark:text-blue-400"
                        title="{{ gp247_language_render($pathPlugin.'::lang.admin.store_website') }}">
                        <i class="fas fa-globe"></i>{{ $store->domain }}
                    </a>
                </td>
                {{-- Site online / maintenance state (the store `active` flag). Toggle
                     for sub-stores; the root store's state is managed on Website info. --}}
                <td class="px-4 py-3">
                    @if ($key != GP247_STORE_ID_ROOT)
                        <span class="inline-flex items-center gap-2">
                            <label class="inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model.live="active.{{ $store->id }}" class="peer sr-only">
                                <span class="relative h-6 w-11 rounded-full bg-gray-300 transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-all peer-checked:bg-blue-600 peer-checked:after:translate-x-5 dark:bg-gray-600"></span>
                            </label>
                            @if (!empty($active[$store->id]))
                                <x-gp247::badge color="green">{{ gp247_language_render('admin.store.state_live') }}</x-gp247::badge>
                            @else
                                <x-gp247::badge color="amber">{{ gp247_language_render('admin.store.state_maintenance') }}</x-gp247::badge>
                            @endif
                        </span>
                    @else
                        @if ($store->active)
                            <x-gp247::badge color="green">{{ gp247_language_render('admin.store.state_live') }}</x-gp247::badge>
                        @else
                            <x-gp247::badge color="amber">{{ gp247_language_render('admin.store.state_maintenance') }}</x-gp247::badge>
                        @endif
                    @endif
                </td>
                {{-- Store lock (Pro): platform-level status vs the owner's `active` above.
                     Disabled + "Pro" note when the Pro plugin is not installed. --}}
                <td class="px-4 py-3">
                    @if ($key == GP247_STORE_ID_ROOT)
                        <span class="text-xs text-gray-400">{{ gp247_language_render($pathPlugin.'::lang.lock.root_protected_short') }}</span>
                    @elseif (!$proInstalled)
                        <span class="inline-flex items-center gap-1.5">
                            <x-gp247::button size="sm" variant="secondary" disabled
                                title="{{ gp247_language_render($pathPlugin.'::lang.lock.pro_only') }}">
                                <i class="fas fa-lock"></i> {{ gp247_language_render($pathPlugin.'::lang.lock.lock') }}
                            </x-gp247::button>
                            <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-600 dark:bg-amber-900/40 dark:text-amber-300">Pro</span>
                        </span>
                    @elseif ($store->status)
                        <x-gp247::button size="sm" variant="secondary" wire:click="toggleLock('{{ $store->id }}')"
                            wire:confirm="{{ gp247_language_render($pathPlugin.'::lang.lock.confirm_lock') }}"
                            data-testid="multi-store-lock-toggle">
                            <i class="fas fa-lock"></i> {{ gp247_language_render($pathPlugin.'::lang.lock.lock') }}
                        </x-gp247::button>
                    @else
                        <span class="inline-flex items-center gap-2">
                            <x-gp247::badge color="red">{{ gp247_language_render($pathPlugin.'::lang.lock.locked_state') }}</x-gp247::badge>
                            <x-gp247::button size="sm" variant="primary" wire:click="toggleLock('{{ $store->id }}')"
                                wire:confirm="{{ gp247_language_render($pathPlugin.'::lang.lock.confirm_unlock') }}"
                                data-testid="multi-store-lock-toggle">
                                <i class="fas fa-lock-open"></i> {{ gp247_language_render($pathPlugin.'::lang.lock.unlock') }}
                            </x-gp247::button>
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <x-gp247::row-actions
                        :delete-id="$key != GP247_STORE_ID_ROOT ? $store->id : null"
                        :locked="$key == GP247_STORE_ID_ROOT"
                        :delete-confirm="gp247_language_render('action.delete_confirm').' #'.$store->id">
                        <x-gp247::button size="sm" variant="ghost"
                            href="{{ gp247_route_admin('admin_MultiStore.config', ['id' => $store->id]) }}"
                            title="{{ gp247_language_render($pathPlugin.'::lang.admin.store_config') }}">
                            <i class="fas fa-cogs"></i>
                        </x-gp247::button>
                    </x-gp247::row-actions>
                </td>
            </tr>
        @endforeach
    </x-gp247::table>
</div>
