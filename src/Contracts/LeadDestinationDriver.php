<?php

namespace AesirCloud\StatamicAiChatbot\Contracts;

use AesirCloud\StatamicAiChatbot\Models\LeadSubmission;

interface LeadDestinationDriver
{
    public function key(): string;

    public function deliver(LeadSubmission $lead): void;
}
