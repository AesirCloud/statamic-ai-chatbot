<?php

namespace AesirCloud\StatamicAiChatbot\Support\Leads;

use AesirCloud\StatamicAiChatbot\Contracts\LeadDestinationDriver;
use AesirCloud\StatamicAiChatbot\Models\LeadSubmission;

class DatabaseLeadDestination implements LeadDestinationDriver
{
    public function key(): string
    {
        return 'database';
    }

    public function deliver(LeadSubmission $lead): void
    {
        $lead->forceFill([
            'status' => 'stored',
        ])->save();
    }
}
