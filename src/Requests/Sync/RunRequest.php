<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Requests\Sync;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

final class RunRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $model,
        private readonly array $input,
        private readonly ?int $timeout = null,
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

        if ($this->noRetry) {
            $headers['X-Fal-No-Retry'] = '1';
        }

        return $headers;
    }

    protected function defaultBody(): array
    {
        return $this->input;
    }
}
