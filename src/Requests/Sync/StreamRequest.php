<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Requests\Sync;

use MarceloEatWorld\FalAI\Data\ObjectLifecycle;
use MarceloEatWorld\FalAI\Support\FalHeaders;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * POST {model}/stream: the model answers with a text/event-stream whose
 * last event is the final result. Only models exposing a /stream endpoint
 * support it.
 */
final class StreamRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $model,
        private readonly array $input,
        private readonly ?int $timeout = null,
        private readonly bool $noRetry = false,
        private readonly ?ObjectLifecycle $lifecycle = null,
        private readonly ?bool $storeIo = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/' . $this->model . '/stream';
    }

    protected function defaultHeaders(): array
    {
        return ['Accept' => 'text/event-stream'] + FalHeaders::build(
            timeout: $this->timeout,
            noRetry: $this->noRetry,
            lifecycle: $this->lifecycle,
            storeIo: $this->storeIo,
        );
    }

    protected function defaultConfig(): array
    {
        // Ask Guzzle not to buffer the body so events can be read as they arrive.
        return ['stream' => true];
    }

    protected function defaultBody(): array
    {
        return $this->input;
    }
}
