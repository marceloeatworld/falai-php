<?php

namespace MarceloEatWorld\FalAI\Data;

use Saloon\Http\Response;

final class GenerationsData
{
    /**
     * @param  array<int, GenerationData>  $results
     */
    public function __construct(
        public array $results
    ) {
    }

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        $results = [];
        foreach ($data as $result) {
            $results[] = new GenerationData(
                id: $result['request_id'],
                status: $result['status'],
                responseUrl: $result['response_url'] ?? null,
                logs: $result['logs'] ?? null,
                response: $result['response'] ?? null,
                error: $result['error'] ?? null,
            );
        }

        return new self(
            results: $results,
        );
    }
}