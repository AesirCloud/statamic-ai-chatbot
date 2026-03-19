@php($payload = json_encode($config, JSON_THROW_ON_ERROR))

@once
    <link rel="stylesheet" href="{{ asset('vendor/statamic-ai-chatbot/build/widget.css') }}">
    <script type="module" src="{{ asset('vendor/statamic-ai-chatbot/build/widget.js') }}"></script>
@endonce

<div
    class="aesircloud-statamic-ai-chatbot-widget"
    data-aesircloud-statamic-ai-chatbot='{{ $payload }}'
></div>
