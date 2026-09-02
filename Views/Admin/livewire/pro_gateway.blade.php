{{--
    Pro-feature gateway page (Free edition). Shown only when the Pro plugin is NOT
    installed (otherwise ProGateway::mount redirects to the real screen). Renders the
    reusable upsell block full-page.

    @aidlc-unit multi-store-free
    @aidlc-story US-multi-store-free-pro-upsell

    Variables: $pathPlugin, $featureTitle, $featureDesc.
--}}
<div class="py-8">
    @include('Plugins/MultiStore::Admin.partials.pro_upsell', [
        'pathPlugin'   => $pathPlugin,
        'featureTitle' => $featureTitle,
        'featureDesc'  => $featureDesc,
        'variant'      => 'screen',
    ])
</div>
