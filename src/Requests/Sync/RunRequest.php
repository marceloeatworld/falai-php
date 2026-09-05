<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Requests\Sync;

use MarceloEatWorld\FalAI\Data\ObjectLifecycle;
use MarceloEatWorld\FalAI\Support\FalHeaders;
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
        private readonly ?ObjectLifecycle $lifecycle = null,
        private readonly ?bool $storeIo = null,
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
        );
    }

    protected function defaultBody(): array
    {
        return $this->input;
    }
}
