@php
    $payload = json_encode($config, JSON_THROW_ON_ERROR);
    $widgetAssetVersion = static function (string $asset): ?int {
        $publishedPath = public_path('vendor/statamic-ai-chatbot/build/'.$asset);
        $packagePath = realpath(__DIR__.'/../../public/build/'.$asset) ?: null;

        if (is_file($publishedPath)) {
            return filemtime($publishedPath) ?: null;
        }

        if ($packagePath && is_file($packagePath)) {
            return filemtime($packagePath) ?: null;
        }

        return null;
    };
@endphp

@once
    <link rel="stylesheet" href="{{ asset('vendor/statamic-ai-chatbot/build/widget.css').($widgetAssetVersion('widget.css') ? '?v='.$widgetAssetVersion('widget.css') : '') }}">
    <script type="module" src="{{ asset('vendor/statamic-ai-chatbot/build/widget.js').($widgetAssetVersion('widget.js') ? '?v='.$widgetAssetVersion('widget.js') : '') }}"></script>
@endonce

<div
    class="aesircloud-statamic-ai-chatbot-widget"
    data-aesircloud-statamic-ai-chatbot='{{ $payload }}'
></div>
