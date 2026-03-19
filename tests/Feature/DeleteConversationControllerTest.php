<?php

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Models\ChatConversation;
use AesirCloud\StatamicAiChatbot\Models\ChatMessage;
use AesirCloud\StatamicAiChatbot\Models\LeadSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes conversations and keeps linked leads intact', function () {
    $this->withoutMiddleware();

    $profile = BotProfile::query()->create([
        'handle' => 'support',
        'name' => 'Support Bot',
        'is_default' => true,
        'active' => true,
    ]);

    $conversation = ChatConversation::query()->create([
        'bot_profile_id' => $profile->id,
        'site' => 'default',
        'locale' => 'en',
        'session_id' => 'session-123',
        'visitor_name' => 'Jane Example',
        'visitor_email' => 'jane@example.test',
        'metadata' => ['origin' => 'widget'],
    ]);

    ChatMessage::query()->create([
        'chat_conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'What are your hours?',
    ]);

    $lead = LeadSubmission::query()->create([
        'bot_profile_id' => $profile->id,
        'chat_conversation_id' => $conversation->id,
        'name' => 'Jane Example',
        'email' => 'jane@example.test',
        'message' => 'Please follow up.',
        'status' => 'new',
    ]);

    $this->postJson('/cp/aesircloud/statamic-ai-chatbot/conversations/delete', [
        'id' => $conversation->id,
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Conversation deleted.')
        ->assertJsonPath('data.stats.conversations', 0)
        ->assertJsonPath('data.stats.leads', 1)
        ->assertJsonPath('data.leads.0.chat_conversation_id', null);

    expect(ChatConversation::query()->whereKey($conversation->id)->exists())->toBeFalse();
    expect(ChatMessage::query()->count())->toBe(0);
    expect($lead->fresh()?->chat_conversation_id)->toBeNull();
});
