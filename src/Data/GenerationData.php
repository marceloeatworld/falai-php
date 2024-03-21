<?php

namespace MarceloEatWorld\FalAI\Data;

use Exception;
use Saloon\Http\Response;

final class GenerationData
{
    public function __construct(
        public ?string $requestId,
        public ?string $responseUrl,
        public ?string $statusUrl,
        public ?string $cancelUrl,
        public ?string $status,
        public ?array $payload,
        public ?string $error,
    ) {
    }

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        return new self(
            requestId: $data['request_id'] ?? null,
            responseUrl: $data['response_url'] ?? null,
            statusUrl: $data['status_url'] ?? null,
            cancelUrl: $data['cancel_url'] ?? null,
            status: $data['status'] ?? null,
            payload: $data['payload'] ?? null,
            error: $data['error'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'requestId' => $this->requestId,
            'responseUrl' => $this->responseUrl,
            'statusUrl' => $this->statusUrl,
            'cancelUrl' => $this->cancelUrl,
            'status' => $this->status,
            'payload' => $this->payload,
            'error' => $this->error,
        ];
    }
}