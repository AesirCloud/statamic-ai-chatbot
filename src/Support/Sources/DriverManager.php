<?php

namespace AesirCloud\StatamicAiChatbot\Support\Sources;

use AesirCloud\StatamicAiChatbot\Contracts\KnowledgeSourceDriver;
use InvalidArgumentException;

class DriverManager
{
    /**
     * @param  array<int, KnowledgeSourceDriver>  $drivers
     */
    public function __construct(protected array $drivers)
    {
    }

    public function driver(string $key): KnowledgeSourceDriver
    {
        foreach ($this->drivers as $driver) {
            if ($driver->key() === $key) {
                return $driver;
            }
        }

        throw new InvalidArgumentException("Unknown source driver [{$key}].");
    }

    /**
     * @return array<int, array{key:string,label:string}>
     */
    public function options(): array
    {
        return collect($this->drivers)
            ->map(fn (KnowledgeSourceDriver $driver) => [
                'key' => $driver->key(),
                'label' => $driver->label(),
            ])
            ->values()
            ->all();
    }
}
