<?php

namespace AesirCloud\StatamicAiChatbot\Support\Leads;

use AesirCloud\StatamicAiChatbot\Contracts\LeadDestinationDriver;
use AesirCloud\StatamicAiChatbot\Models\LeadSubmission;
use Illuminate\Support\Facades\Mail;

class EmailLeadDestination implements LeadDestinationDriver
{
    public function key(): string
    {
        return 'email';
    }

    public function deliver(LeadSubmission $lead): void
    {
        if (! config('statamic-ai-chatbot.lead_destinations.email.enabled') || blank(config('statamic-ai-chatbot.lead_destinations.email.to'))) {
            return;
        }

        Mail::raw($this->content($lead), function ($message) {
            $message
                ->to((string) config('statamic-ai-chatbot.lead_destinations.email.to'))
                ->subject('New Statamic AI Chatbot Lead');
        });
    }

    protected function content(LeadSubmission $lead): string
    {
        return implode("\n", [
            'A new lead was captured by the Statamic AI Chatbot.',
            '',
            'Name: '.($lead->name ?: 'N/A'),
            'Email: '.($lead->email ?: 'N/A'),
            'Phone: '.($lead->phone ?: 'N/A'),
            'Message: '.($lead->message ?: 'N/A'),
        ]);
    }
}
