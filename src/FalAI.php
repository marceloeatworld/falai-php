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
}