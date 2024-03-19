<?php

namespace MarceloEatWorld\FalAI\Data;

use Saloon\Http\Response;

final class GenerationData
{
    public function __construct(
        public ?string $id,
        public ?string $status,
        public ?string $responseUrl,
        public ?array $logs,
        public ?array $response,
        public ?string $error,
        public ?string $requestId,
        public ?string $statusUrl,
        public ?string $cancelUrl,
    ) {
    }

    public static function fromResponse(Response $response): self
    {

        $data = $response->json();
        
        return new self(
            id: $data['id'] ?? $data['request_id'] ?? null,
            status: $data['status'] ?? null,
            responseUrl: $data['response_url'] ?? null,
            logs: $data['logs'] ?? null,
            response: $data['response'] ?? $data['payload'] ?? null,
            error: $data['error'] ?? null,
            requestId: $data['request_id'] ?? null,
            statusUrl: $data['status_url'] ?? null,
            cancelUrl: $data['cancel_url'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'responseUrl' => $this->responseUrl,
            'logs' => $this->logs,
            'response' => $this->response,
            'error' => $this->error,
            'requestId' => $this->requestId,
            'statusUrl' => $this->statusUrl,
            'cancelUrl' => $this->cancelUrl,
        ];
    }
}