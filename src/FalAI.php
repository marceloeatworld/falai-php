<?php

namespace MarceloEatWorld\FalAI;

use Saloon\Http\Connector;

class FalAI extends Connector
{
    public function __construct(
        public string $apiKey,
        public string $apiKeyId,
        public string $apiKeySecret,
    ) {
    }

    public function resolveBaseUrl(): string
    {
        return 'https://api.fal.ai/v1';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function defaultConfig(): array
    {
        return [
            'timeout' => 30,
        ];
    }

    public function generations(): GenerationsResource
    {
        return new GenerationsResource($this);
    }
}