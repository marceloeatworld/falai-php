<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Connectors;

final class QueueConnector extends FalConnector
{
    public function __construct(
        string $apiKey,
        private readonly string $baseUrl = 'https://queue.fal.run',
    ) {
        parent::__construct($apiKey);
    }

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
