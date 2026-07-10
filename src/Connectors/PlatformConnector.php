<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Connectors;

final class PlatformConnector extends FalConnector
{
    public function __construct(
        string $apiKey,
        private readonly string $baseUrl = 'https://api.fal.ai',
    ) {
        parent::__construct($apiKey);
    }

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
