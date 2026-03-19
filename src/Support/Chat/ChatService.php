<?php

namespace AesirCloud\StatamicAiChatbot\Support\Chat;

use AesirCloud\StatamicAiChatbot\Contracts\SupportHandoffResolver;
use AesirCloud\StatamicAiChatbot\Models\ChatConversation;
use AesirCloud\StatamicAiChatbot\Models\ChatMessage;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\FaqMatcher;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\KnowledgeRetriever;
use AesirCloud\StatamicAiChatbot\Support\Profiles\BotProfileResolver;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ChatService
{
    public function __construct(
        protected BotProfileResolver $profileResolver,
        protected FaqMatcher $faqMatcher,
        protected KnowledgeRetriever $knowledgeRetriever,
        protected SupportAssistant $assistant,
        protected SupportHandoffResolver $handoffResolver,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handle(array $payload): array
    {
        $profile = $this->profileResolver->resolve(
            handle: Arr::get($payload, 'profile'),
            site: Arr::get($payload, 'site'),
            locale: Arr::get($payload, 'locale')
        );

        $conversation = $this->conversation($profile->id, $payload);
        $question = (string) Arr::get($payload, 'message', '');
        $site = Arr::get($payload, 'site') ?: $profile->site;
        $locale = Arr::get($payload, 'locale') ?: $profile->locale;

        $this->storeMessage($conversation, 'user', $question);

        $faq = $this->faqMatcher->match($profile, $question, $site, $locale);

        if ($faq) {
            $response = [
                'message' => $faq->answer,
                'intent' => 'faq',
                'confidence' => 96,
                'citations' => [[
                    'title' => $faq->question,
                    'type' => 'faq',
                ]],
                'next_actions' => $faq->cta_actions ?? [],
                'lead_capture_fields' => $faq->lead_capture_fields ?? [],
            ];
        } else {
            $chunks = $this->knowledgeRetriever->search($profile, $question, $site, $locale);
            $response = $this->assistant->respond($profile, $question, $chunks);
            $response['citations'] = $response['citations'] ?: $chunks->map(function ($chunk) {
                return [
                    'title' => data_get($chunk->metadata, 'title', 'Knowledge base'),
                    'url' => data_get($chunk->metadata, 'url'),
                    'score' => round((float) $chunk->score, 4),
                    'driver' => data_get($chunk->metadata, 'driver'),
                ];
            })->values()->all();
        }

        $response['next_actions'] = $response['next_actions'] ?: $this->handoffResolver->resolve(
            $profile,
            $response['intent'],
            (int) $response['confidence'],
            ['site' => $site, 'locale' => $locale]
        );

        $this->storeMessage($conversation, 'assistant', (string) $response['message'], $response);

        return [
            'conversation_id' => $conversation->id,
            'message' => $response['message'],
            'intent' => $response['intent'],
            'confidence' => $response['confidence'],
            'citations' => $response['citations'],
            'next_actions' => $response['next_actions'],
            'lead_capture_fields' => $response['lead_capture_fields'],
            'widget' => array_merge(config('statamic-ai-chatbot.widget'), $profile->widget_settings ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function conversation(int $profileId, array $payload): ChatConversation
    {
        return ChatConversation::firstOrCreate(
            [
                'bot_profile_id' => $profileId,
                'session_id' => (string) Arr::get($payload, 'session_id', (string) Str::uuid()),
            ],
            [
                'site' => Arr::get($payload, 'site'),
                'locale' => Arr::get($payload, 'locale'),
                'visitor_name' => Arr::get($payload, 'visitor.name'),
                'visitor_email' => Arr::get($payload, 'visitor.email'),
                'metadata' => Arr::only($payload, ['path', 'user_agent']),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $structured
     */
    protected function storeMessage(ChatConversation $conversation, string $role, string $content, array $structured = []): ChatMessage
    {
        if (config('statamic-ai-chatbot.retention.mode') === 'leads_only') {
            return new ChatMessage();
        }

        return $conversation->messages()->create([
            'role' => $role,
            'content' => $content,
            'structured_output' => $structured ?: null,
            'citations' => $structured['citations'] ?? null,
            'metadata' => Arr::only($structured, ['intent', 'confidence', 'next_actions']),
        ]);
    }
}
