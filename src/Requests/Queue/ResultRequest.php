<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Requests\Queue;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class ResultRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $model,
        private readonly string $requestId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/' . $this->model . '/requests/' . $this->requestId;
    }
}
