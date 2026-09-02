{{--
    Per-store configuration screen (v2 port of the legacy store_config view):
    four tabs — store info (fields + multilingual descriptions), email
    (send-mail toggles + SMTP), captcha, and list limits — persisting live
    through the Livewire component. Labels come from each config row's own
    `detail` i18n key, exactly like the legacy screens.

    @aidlc-unit multi-store-free
    @aidlc-story GP247-v2-compat
    @aidlc-adr ADR-005

    Variables: $pathPlugin, $languages, $mediaFields, $fields,
      $languageOptions / $currencyOptions / $templateOptions,
      $captchaMethods / $captchaPages, $smtpMethodOptions,
      $emailActionRows / $smtpRows / $captchaRows / $displayRows.
--}}
@php
    $meta = [
        'logo' => ['label' => 'store.logo', 'icon' => 'far fa-image'],
        'icon' => ['label' => 'store.icon', 'icon' => 'far fa-image'],
        'og_image' => ['label' => 'store.og_image', 'icon' => 'far fa-image'],
        'phone' => ['label' => 'store.phone', 'icon' => 'fas fa-phone-alt'],
        'long_phone' => ['label' => 'store.long_phone', 'icon' => 'fas fa-phone-square'],
        'email' => ['label' => 'store.email', 'icon' => 'fas fa-envelope'],
        'time_active' => ['label' => 'store.time_active', 'icon' => 'far fa-calendar-alt'],
        'address' => ['label' => 'store.address', 'icon' => 'fas fa-map-marked'],
        'office' => ['label' => 'store.office', 'icon' => 'fas fa-location-arrow'],
        'warehouse' => ['label' => 'store.warehouse', 'icon' => 'fas fa-warehouse'],
        'domain' => ['label' => 'admin.store.domain', 'icon' => 'fab fa-chrome'],
        'code' => ['label' => 'admin.store.code', 'icon' => 'fas fa-code'],
        'language' => ['label' => 'store.language', 'icon' => 'fas fa-language'],
        'currency' => ['label' => 'store.currency', 'icon' => 'far fa-money-bill-alt'],
        'template' => ['label' => 'admin.store.template', 'icon' => 'fas fa-object-ungroup'],
    ];

    $rows = [];
    foreach ($mediaFields as $f) { $rows[] = ['field' => $f, 'type' => 'media']; }
    foreach ($fields as $f) { $rows[] = ['field' => $f, 'type' => $f === 'time_active' ? 'textarea' : 'text']; }
    $rows[] = ['field' => 'domain', 'type' => 'text'];
    $rows[] = ['field' => 'code', 'type' => 'text'];
    $rows[] = ['field' => 'language', 'type' => 'select', 'options' => $languageOptions];
    if (!empty($currencyOptions)) { $rows[] = ['field' => 'currency', 'type' => 'select', 'options' => $currencyOptions]; }
    if (!empty($templateOptions)) { $rows[] = ['field' => 'template', 'type' => 'select', 'options' => $templateOptions]; }

    $labelCell = 'w-2/5 border-r border-gray-200 px-5 py-3.5 align-middle text-sm font-medium text-gray-600 dark:border-gray-700 dark:text-gray-300';
    $valueCell = 'px-5 py-3 align-middle';
    $input = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
    $toggle = 'relative h-6 w-11 rounded-full bg-gray-300 transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-all peer-checked:bg-blue-600 peer-checked:after:translate-x-5 dark:bg-gray-600';
@endphp

<div class="space-y-5">
    <div class="flex justify-end">
        <x-gp247::button href="{{ gp247_route_admin('admin_MultiStore.index') }}" variant="secondary" size="sm">
            <i class="fa fa-list"></i> {{ gp247_language_render('admin.back_list') }}
        </x-gp247::button>
    </div>

    <x-gp247::tabs :tabs="[
        'info' => gp247_language_render('admin.store.config_info'),
        'email' => gp247_language_render('admin.store.config_email'),
        'captcha' => gp247_language_render('admin.captcha.captcha_title'),
        'display' => gp247_language_render('admin.shop.config_limit_per_page'),
    ]">
        {{-- Tab: store information --}}
        <div x-show="tab === 'info'" x-cloak class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            {{-- Left: store fields --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700">
                <table class="min-w-full">
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($rows as $i => $row)
                            @php $field = $row['field']; @endphp
                            <tr class="{{ $i % 2 ? 'bg-gray-50/60 dark:bg-gray-800/40' : 'bg-white dark:bg-gray-800' }}">
                                <td class="{{ $labelCell }}">
                                    @if (!empty($meta[$field]['icon']))<i class="{{ $meta[$field]['icon'] }} mr-1.5 w-4 text-center text-gray-400"></i>@endif
                                    {{ gp247_language_render($meta[$field]['label']) }}
                                </td>
                                <td class="{{ $valueCell }}">
                                    @switch($row['type'])
                                        @case('media')
                                            <x-gp247::media-input :name="$field" type="logo" :working-store="$storeId ?? ''" wire:model.live="store.{{ $field }}" :value="$store[$field] ?? null" />
                                            @break
                                        @case('textarea')
                                            <textarea wire:model.live.blur="store.{{ $field }}" rows="2" class="{{ $input }}"></textarea>
                                            @break
                                        @case('select')
                                            <x-gp247::searchable-select
                                                model="store.{{ $field }}"
                                                :options="collect($row['options'])->map(fn ($label, $value) => ['id' => (string) $value, 'label' => $label])->values()->all()"
                                                :clearable="false"
                                            />
                                            @break
                                        @default
                                            <input type="text" wire:model.live.blur="store.{{ $field }}" class="{{ $input }}">
                                    @endswitch
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Right: multilingual descriptions, one tab per language --}}
            @php $langTabs = []; foreach ($languages as $code => $lang) { $langTabs[$code] = $lang->name; } @endphp
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <x-gp247::tabs :tabs="$langTabs">
                    @foreach ($languages as $code => $lang)
                        <div x-show="tab === @js((string) $code)" x-cloak class="space-y-4">
                            @php $descLabels = ['name' => 'store.title', 'keyword' => 'store.keyword', 'description' => 'store.description']; @endphp
                            @foreach (['name' => 'text', 'keyword' => 'text', 'description' => 'textarea'] as $df => $control)
                                <div class="space-y-1">
                                    <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-200">
                                        @if ($lang->icon)<img src="{{ gp247_file($lang->icon) }}" alt="{{ $lang->name }}" class="h-4 w-auto rounded-sm">@endif
                                        {{ gp247_language_render($descLabels[$df]) }}
                                    </label>
                                    @if ($control === 'textarea')
                                        <textarea wire:model.live.blur="desc.{{ $code }}.{{ $df }}" rows="4" class="{{ $input }}"></textarea>
                                    @else
                                        <input type="text" wire:model.live.blur="desc.{{ $code }}.{{ $df }}" class="{{ $input }}">
                                    @endif
                                </div>
                            @endforeach

                            {{-- Maintenance copy, folded in from the legacy store_maintain screen (mirrors store_info) --}}
                            <div class="space-y-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                    <i class="fas fa-tools"></i>
                                    {{ gp247_language_render('admin.maintain.title') }}
                                </div>
                                <x-gp247::rich-editor
                                    :model="'desc.' . $code . '.maintain_content'"
                                    :label="gp247_language_render('admin.maintain.description')" />
                                <div class="space-y-1">
                                    <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-200">
                                        @if ($lang->icon)<img src="{{ gp247_file($lang->icon) }}" alt="{{ $lang->name }}" class="h-4 w-auto rounded-sm">@endif
                                        {{ gp247_language_render('admin.maintain.description_note') }}
                                    </label>
                                    <input type="text" wire:model.live.blur="desc.{{ $code }}.maintain_note" class="{{ $input }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </x-gp247::tabs>
            </div>
        </div>

        {{-- Tab: email (send-mail toggles + SMTP) --}}
        <div x-show="tab === 'email'" x-cloak class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-gp247::card :title="gp247_language_render('admin.email.config_mode')">
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($emailActionRows as $config)
                        <div class="flex items-center justify-between gap-4 py-3">
                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ gp247_language_render($config->detail) }}</span>
                            <label class="inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model.live="emailAction.{{ $config->key }}" class="peer sr-only">
                                <span class="{{ $toggle }}"></span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </x-gp247::card>

            <x-gp247::card :title="gp247_language_render('admin.email.config_smtp')">
                <div class="space-y-4">
                    @foreach ($smtpRows as $config)
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render($config->detail) }}</label>
                            @if ($config->key === 'smtp_security')
                                <select wire:model.live="smtp.{{ $config->key }}" class="{{ $input }}">
                                    @foreach ($smtpMethodOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            @elseif ($config->key === 'smtp_port')
                                <input type="number" wire:model.live.blur="smtp.{{ $config->key }}" class="{{ $input }}">
                            @elseif (in_array($config->key, ['smtp_user', 'smtp_password'], true))
                                <input type="password" autocomplete="new-password" wire:model.live.blur="smtp.{{ $config->key }}" class="{{ $input }}">
                            @else
                                <input type="text" wire:model.live.blur="smtp.{{ $config->key }}" class="{{ $input }}">
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-gp247::card>
        </div>

        {{-- Tab: captcha --}}
        <div x-show="tab === 'captcha'" x-cloak>
            <x-gp247::card :title="gp247_language_render('admin.captcha.captcha_title')">
                <div class="space-y-5">
                    @foreach ($captchaRows as $config)
                        @if ($config->key === 'captcha_mode')
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-gray-600 dark:text-gray-300">{{ gp247_language_render($config->detail) }}</span>
                                <label class="inline-flex cursor-pointer items-center">
                                    <input type="checkbox" wire:model.live="captcha.captcha_mode" class="peer sr-only">
                                    <span class="{{ $toggle }}"></span>
                                </label>
                            </div>
                        @elseif ($config->key === 'captcha_method')
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render($config->detail) }}</label>
                                <select wire:model.live="captcha.captcha_method" class="{{ $input }}">
                                    <option value=""></option>
                                    @foreach ($captchaMethods as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif ($config->key === 'captcha_page')
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render($config->detail) }}</label>
                                <div class="flex flex-wrap gap-x-6 gap-y-2">
                                    @foreach ($captchaPages as $value => $label)
                                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                            <input type="checkbox" value="{{ $value }}" wire:model.live="captcha.captcha_page"
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render($config->detail) }}</label>
                                <input type="text" wire:model.live.blur="captcha.{{ $config->key }}" class="{{ $input }}">
                            </div>
                        @endif
                    @endforeach
                </div>
            </x-gp247::card>
        </div>

        {{-- Tab: list limits (display_config) --}}
        <div x-show="tab === 'display'" x-cloak>
            <x-gp247::card :title="gp247_language_render('admin.shop.config_limit_per_page')">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach ($displayRows as $config)
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render($config->detail) }}</label>
                            <input type="number" min="1" wire:model.live.blur="display.{{ $config->key }}" class="{{ $input }}">
                        </div>
                    @endforeach
                </div>
            </x-gp247::card>
        </div>
    </x-gp247::tabs>

    {{-- Confirm dialog before the destructive template switch (removeStore/setupStore).
         Same $wire-driven Alpine bridge as core WebsiteInfo: Livewire-dispatched
         open-modal params never match the modal's $event.detail === name check. --}}
    <div x-data x-effect="$wire.pendingTemplate
            ? $dispatch('open-modal', 'confirm-store-template')
            : $dispatch('close-modal', 'confirm-store-template')"></div>

    <x-gp247::modal name="confirm-store-template" :title="gp247_language_render('admin.store.template_switch_title')">
        <div class="flex items-start gap-3">
            <i class="fas fa-triangle-exclamation mt-0.5 text-xl text-amber-500"></i>
            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                {{ gp247_language_render('admin.store.template_switch_warning') }}
            </p>
        </div>

        <x-slot:footer>
            <x-gp247::button variant="secondary" size="sm" wire:click="cancelTemplateSwitch">
                {{ gp247_language_render('admin.store.template_switch_cancel') }}
            </x-gp247::button>
            <x-gp247::button variant="danger" size="sm" wire:click="confirmTemplateSwitch">
                {{ gp247_language_render('admin.store.template_switch_confirm') }}
            </x-gp247::button>
        </x-slot:footer>
    </x-gp247::modal>
</div>
