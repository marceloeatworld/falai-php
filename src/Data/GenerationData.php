<?php

namespace MarceloEatWorld\FalAI\Data;

use Saloon\Http\Response;
use Illuminate\Support\Facades\Log;

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
        try {
            $data = $response->json();
        } catch (\JsonException $e) {
            Log::error('Error parsing JSON response:', ['error' => $e->getMessage()]);

            return new self(
                id: null,
                status: 'error',
                responseUrl: null,
                logs: null,
                response: null,
                error: 'Invalid JSON response from API',
                requestId: null,
                statusUrl: null,
                cancelUrl: null,
            );
        }

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