<?php

namespace AesirCloud\StatamicAiChatbot\Support\Leads;

use AesirCloud\StatamicAiChatbot\Contracts\LeadDestinationDriver;
use AesirCloud\StatamicAiChatbot\Models\LeadSubmission;
use Illuminate\Support\Facades\Http;

class WebhookLeadDestination implements LeadDestinationDriver
{
    public function key(): string
    {
        return 'webhook';
    }

    public function deliver(LeadSubmission $lead): void
    {
        if (! config('statamic-ai-chatbot.lead_destinations.webhook.enabled') || blank(config('statamic-ai-chatbot.lead_destinations.webhook.url'))) {
            return;
        }

        $secret = config('statamic-ai-chatbot.lead_destinations.webhook.secret');

        $response = Http::withHeaders([
            'X-Statamic-AI-Chatbot-Signature' => $secret ? hash_hmac('sha256', $lead->toJson(), (string) $secret) : '',
        ])->post((string) config('statamic-ai-chatbot.lead_destinations.webhook.url'), [
            'id' => $lead->id,
            'site' => $lead->site,
            'locale' => $lead->locale,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'message' => $lead->message,
            'payload' => $lead->payload,
        ]);

        $lead->forceFill([
            'delivery_log' => array_merge($lead->delivery_log ?? [], [[
                'driver' => $this->key(),
                'successful' => $response->successful(),
                'status' => $response->status(),
                'at' => now()->toIso8601String(),
            ]]),
        ])->save();
    }
}
