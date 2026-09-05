<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Requests\Queue;

use MarceloEatWorld\FalAI\Support\EndpointId;
use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * GET {app}/requests/{id}/status/stream: text/event-stream of status
 * updates pushed by the queue until the request completes.
 */
final class StatusStreamRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $model,
        private readonly string $requestId,
        private readonly bool $logs = true,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/' . EndpointId::appId($this->model) . '/requests/' . $this->requestId . '/status/stream';
    }

    protected function defaultHeaders(): array
    {
        return ['Accept' => 'text/event-stream'];
    }

    protected function defaultConfig(): array
    {
        return ['stream' => true];
    }

    protected function defaultQuery(): array
    {
        return ['logs' => $this->logs ? '1' : '0'];
    }
}
