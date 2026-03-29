<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Data;

use Saloon\Http\Response;

final readonly class QueuedJob
{
    public function __construct(
        public string $requestId,
        public string $responseUrl,
        public string $statusUrl,
        public string $cancelUrl,
        public ?int $queuePosition = null,
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        return new self(
            requestId: $data['request_id'],
            responseUrl: $data['response_url'],
            statusUrl: $data['status_url'],
            cancelUrl: $data['cancel_url'],
            queuePosition: $data['queue_position'] ?? null,
        );
    }
}
