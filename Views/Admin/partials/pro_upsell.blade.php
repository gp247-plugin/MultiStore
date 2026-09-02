{{--
    Reusable "Pro feature" upsell block for the Free edition. Used full-page by the
    ProGateway screen, and inline (variant=block) inside a Free screen when only a
    single block is Pro-gated. UI text via gp247_language_render.

    @aidlc-unit multi-store-free
    @aidlc-story US-multi-store-free-pro-upsell

    Params:
      $pathPlugin   - 'Plugins/MultiStore'
      $featureTitle - name of the Pro feature (already localized)
      $featureDesc  - one-line description (already localized; may be '')
      $variant      - 'screen' (default, big centered card) | 'block' (compact inline)
--}}
@php($variant = $variant ?? 'screen')
@php($buyUrl = gp247_language_render($pathPlugin.'::lang.pro.buy_url'))

<div class="rounded-xl border border-amber-200 bg-gradient-to-b from-amber-50 to-white p-6 text-center dark:border-amber-900/50 dark:from-amber-900/10 dark:to-gray-800 {{ $variant === 'screen' ? 'mx-auto max-w-xl' : '' }}"
     data-testid="multi-store-pro-upsell">
    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300">
        <i class="fas fa-crown text-xl"></i>
    </div>

    <p class="text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">
        {{ gp247_language_render($pathPlugin.'::lang.pro.heading') }}
    </p>

    @if (!empty($featureTitle))
        <h2 class="mt-1 text-lg font-bold text-gray-800 dark:text-gray-100">{{ $featureTitle }}</h2>
    @endif

    @if (!empty($featureDesc))
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $featureDesc }}</p>
    @endif

    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
        {{ gp247_language_render($pathPlugin.'::lang.pro.blurb') }}
    </p>

    <a href="{{ $buyUrl }}" target="_blank" rel="noopener"
       class="mt-4 inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-amber-600"
       data-testid="multi-store-pro-upsell-cta">
        <i class="fas fa-arrow-up-right-from-square"></i>
        {{ gp247_language_render($pathPlugin.'::lang.pro.cta') }}
    </a>
</div>
