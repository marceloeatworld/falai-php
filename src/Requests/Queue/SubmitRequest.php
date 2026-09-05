<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Requests\Queue;

use MarceloEatWorld\FalAI\Data\ObjectLifecycle;
use MarceloEatWorld\FalAI\Enums\Priority;
use MarceloEatWorld\FalAI\Support\FalHeaders;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

final class SubmitRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $model,
        private readonly array $input,
        private readonly ?string $webhook = null,
        private readonly ?int $timeout = null,
        private readonly ?Priority $priority = null,
        private readonly ?string $runnerHint = null,
        private readonly bool $noRetry = false,
        private readonly ?ObjectLifecycle $lifecycle = null,
        private readonly ?bool $storeIo = null,
        private readonly ?int $maxQueueLength = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/' . $this->model;
    }

    protected function defaultHeaders(): array
    {
        return FalHeaders::build(
            timeout: $this->timeout,
            noRetry: $this->noRetry,
            lifecycle: $this->lifecycle,
            storeIo: $this->storeIo,
            priority: $this->priority,
            runnerHint: $this->runnerHint,
        );
    }

    protected function defaultBody(): array
    {
        return $this->input;
    }

    protected function defaultQuery(): array
    {
        $query = [];

        if ($this->webhook !== null) {
            $query['fal_webhook'] = $this->webhook;
        }

        if ($this->maxQueueLength !== null) {
            $query['fal_max_queue_length'] = $this->maxQueueLength;
        }

        return $query;
    }
}
