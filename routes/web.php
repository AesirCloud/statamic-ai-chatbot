<?php

use Illuminate\Support\Facades\Route;

Route::get('aesircloud/statamic-ai-chatbot/ping', fn () => response()->json(['ok' => true]))
    ->name('aesircloud.statamic-ai-chatbot.ping');
