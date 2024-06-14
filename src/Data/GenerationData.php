<?php

namespace MarceloEatWorld\FalAI\Data;

use Exception;
use Illuminate\Support\Facades\Log;
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

        Log::info('API response: ' . json_encode($data));
    
        if (!isset($data['request_id'])) {
            throw new Exception('Missing request_id in API response');
        }

        return new self(
            requestId: $data['request_id'],
            responseUrl: $data['response_url'] ?? '',
            statusUrl: $data['status_url'] ?? '',
            cancelUrl: $data['cancel_url'] ?? '',
            status: $data['status'] ?? '',
            payload: $data['payload'] ?? [],
            error: $data['error'] ?? '',
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