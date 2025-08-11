<?php

namespace MarceloEatWorld\FalAI\Requests;

use Generator;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class StreamStatus extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $model,
        protected string $requestId,
        protected bool $includeLogs = false,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return sprintf('%s/requests/%s/status/stream', $this->model, $this->requestId);
    }

    protected function defaultQuery(): array
    {
        return $this->includeLogs ? ['logs' => '1'] : [];
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'text/event-stream',
        ];
    }

    /**
     * Process the SSE stream response
     */
    public function processStream(Response $response): Generator
    {
        $body = $response->body();
        
        // Check if it's actually streaming content
        if (!str_contains($response->header('Content-Type') ?? '', 'event-stream')) {
            // If not streaming, try to parse as JSON
            $data = json_decode($body, true);
            if ($data !== null) {
                yield $data;
            }
            return;
        }
        
        // Process SSE stream
        $lines = explode("\n", $body);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and pings
            if (empty($line) || $line === ': ping') {
                continue;
            }
            
            // Extract data from SSE format
            if (str_starts_with($line, 'data: ')) {
                $jsonData = substr($line, 6);
                $data = json_decode($jsonData, true);
                
                if ($data !== null) {
                    yield $data;
                    
                    // Stop if status is COMPLETED
                    if (isset($data['status']) && $data['status'] === 'COMPLETED') {
                        return;
                    }
                }
            }
        }
    }
}