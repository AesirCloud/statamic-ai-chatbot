<?php

it('defines provider defaults', function () {
    $config = require __DIR__.'/../../config/statamic-ai-chatbot.php';

    expect($config)
        ->toHaveKey('providers.text.driver')
        ->toHaveKey('providers.text_fallbacks')
        ->toHaveKey('providers.embeddings.enabled')
        ->toHaveKey('widget.position');
});
