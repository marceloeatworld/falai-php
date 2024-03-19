<?php

namespace MarceloEatWorld\FalAI;

use Saloon\Http\Connector;

class FalAI extends Connector
{
    public function __construct(
        public string $apiKey,
    ) {
    }

    public function resolveBaseUrl(): string
    {
        return 'https://queue.fal.run/fal-ai/';
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