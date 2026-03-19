<?php

use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\DeleteBotProfileController;
use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\DeleteConversationController;
use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\DeleteFaqController;
use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\DeleteLeadController;
use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\DeleteSourceController;
use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\RunSyncController;
use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\SaveSettingsController;
use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\SyncSourceController;
use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\UpsertBotProfileController;
use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\UpsertFaqController;
use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\UpsertLeadController;
use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\UpsertSourceController;
use Illuminate\Support\Facades\Route;

Route::middleware('can:access statamic_ai_chatbot utility')
    ->prefix('aesircloud/statamic-ai-chatbot')
    ->name('aesircloud.statamic-ai-chatbot.')
    ->group(function () {
        Route::post('settings/save', SaveSettingsController::class)->name('settings.save');
        Route::post('sync', RunSyncController::class)->name('sync');
        Route::post('profiles/save', UpsertBotProfileController::class)->name('profiles.save');
        Route::post('profiles/delete', DeleteBotProfileController::class)->name('profiles.delete');
        Route::post('faqs/save', UpsertFaqController::class)->name('faqs.save');
        Route::post('faqs/delete', DeleteFaqController::class)->name('faqs.delete');
        Route::post('sources/save', UpsertSourceController::class)->name('sources.save');
        Route::post('sources/delete', DeleteSourceController::class)->name('sources.delete');
        Route::post('sources/sync', SyncSourceController::class)->name('sources.sync');
        Route::post('conversations/delete', DeleteConversationController::class)->name('conversations.delete');
        Route::post('leads/save', UpsertLeadController::class)->name('leads.save');
        Route::post('leads/delete', DeleteLeadController::class)->name('leads.delete');
    });
