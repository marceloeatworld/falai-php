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
    ) {
    }

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();
    
        return new self(
            id: $data['request_id'] ?? null,
            status: $data['status'] ?? null,
            responseUrl: $data['response_url'] ?? null,
            logs: $data['logs'] ?? null,
            response: $data['response'] ?? null,
            error: $data['error'] ?? null,
        );
    }

    
    public static function fromWebhook(Request $request): self
    {
        $data = $request->json()->all();

        return new self(
            id: $data['request_id'] ?? null,
            status: $data['status'] ?? null,
            responseUrl: $data['payload']['response_url'] ?? null,
            logs: $data['payload']['logs'] ?? null,
            response: $data['payload'] ?? null,
            error: $data['error'] ?? null,
        );
    }
}