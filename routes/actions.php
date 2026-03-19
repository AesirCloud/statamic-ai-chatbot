<?php

use AesirCloud\StatamicAiChatbot\Http\Controllers\Api\ChatController;
use AesirCloud\StatamicAiChatbot\Http\Controllers\Api\LeadController;
use Illuminate\Support\Facades\Route;

Route::prefix('aesircloud/statamic-ai-chatbot')
    ->name('aesircloud.statamic-ai-chatbot.actions.')
    ->group(function () {
        Route::post('chat', ChatController::class)->name('chat');
        Route::post('lead', LeadController::class)->name('lead');
    });
