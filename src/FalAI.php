<?php
// src/FalAI.php

namespace MarceloEatWorld\FalAI;

use Saloon\Http\Connector;

class FalAI extends Connector
{
    public function __construct(public readonly string $apiKey) {}

    public function resolveBaseUrl(): string
    {
        return 'https://queue.fal.run/';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Key ' . $this->apiKey,
        ];
    }


    public function generations(): GenerationsResource
    {
        return new GenerationsResource($this);
    }

    /**
     * Get a synchronous client for immediate responses
     * Note: Only use for fast operations that complete quickly
     */
    public function synchronous(): FalAISynchronous
    {
        return new FalAISynchronous($this->apiKey);
    }
}