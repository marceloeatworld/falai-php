<?php
// src/FalAI.php

namespace MarceloEatWorld\FalAI;

use Saloon\Http\Connector;

class FalAI extends Connector
{
    public function __construct(public readonly string $apiKey) {}

    /**
     * Create a new FalAI client instance
     * 
     * @param string|null $apiKey API key or null to read from FAL_KEY environment variable
     * @return static
     */
    public static function client(?string $apiKey = null): static
    {
        $key = $apiKey ?? $_ENV['FAL_KEY'] ?? getenv('FAL_KEY') ?? throw new \InvalidArgumentException('API key is required. Pass it directly or set FAL_KEY environment variable.');
        
        return new static($key);
    }

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
     * Create a queue resource for asynchronous processing (recommended)
     * 
     * @return GenerationsResource
     */
    public function queue(): GenerationsResource
    {
        return new GenerationsResource($this);
    }

    /**
     * Run a model synchronously (direct execution without queueing)
     * 
     * ⚠️ Warning: Queue-based processing is recommended for reliability.
     * If connection fails during synchronous requests, result cannot be retrieved.
     * 
     * @param string $endpoint The model endpoint
     * @param array $input Input parameters
     * @return array Result data
     */
    public function run(string $endpoint, array $input): array
    {
        return $this->synchronous()->run($endpoint, $input);
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