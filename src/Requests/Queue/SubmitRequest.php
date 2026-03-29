<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Requests\Queue;

use MarceloEatWorld\FalAI\Enums\Priority;
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
    ) {}

    public function resolveEndpoint(): string
    {
        return '/' . $this->model;
    }

    protected function defaultHeaders(): array
    {
        $headers = [];

        if ($this->timeout !== null) {
            $headers['X-Fal-Request-Timeout'] = (string) $this->timeout;
        }

        if ($this->priority !== null) {
            $headers['X-Fal-Queue-Priority'] = $this->priority->value;
        }

        if ($this->runnerHint !== null) {
            $headers['X-Fal-Runner-Hint'] = $this->runnerHint;
        }

        if ($this->noRetry) {
            $headers['X-Fal-No-Retry'] = '1';
        }

        return $headers;
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

        return $query;
    }
}
