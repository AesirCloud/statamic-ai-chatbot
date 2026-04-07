<?php

use AesirCloud\StatamicAiChatbot\Models\LeadSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects lead submissions when the chatbot is turned off', function () {
    $this->withoutMiddleware();

    config()->set('statamic-ai-chatbot.enabled', false);

    $this->postJson('/aesircloud/statamic-ai-chatbot/lead', [
        'name' => 'Jane Example',
        'email' => 'jane@example.test',
    ])
        ->assertStatus(503)
        ->assertJsonPath('status', 'disabled')
        ->assertJsonPath('error_code', 'chatbot_disabled')
        ->assertJsonPath('message', 'The chatbot is currently turned off.');

    expect(LeadSubmission::query()->count())->toBe(0);
});
