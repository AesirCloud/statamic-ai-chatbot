<?php

namespace AesirCloud\StatamicAiChatbot\Support\Leads;

use AesirCloud\StatamicAiChatbot\Contracts\LeadDestinationDriver;
use AesirCloud\StatamicAiChatbot\Models\LeadSubmission;

class LeadDestinationManager
{
    /**
     * @param  array<int, LeadDestinationDriver>  $drivers
     */
    public function __construct(protected array $drivers)
    {
    }

    public function dispatch(LeadSubmission $lead): void
    {
        foreach ($this->drivers as $driver) {
            $driver->deliver($lead);
        }
    }
}
