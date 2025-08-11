<?php

namespace MarceloEatWorld\FalAI;

use Saloon\Http\Connector;

class FalAISynchronous extends Connector
{
    protected string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function resolveBaseUrl(): string
    {
        return 'https://fal.run';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Key ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Get the synchronous generations resource
     */
    public function generations(): SynchronousGenerationsResource
    {
        return new SynchronousGenerationsResource($this);
    }
}