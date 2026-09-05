<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Requests\Queue;

use MarceloEatWorld\FalAI\Support\EndpointId;
use Saloon\Enums\Method;
use Saloon\Http\Request;

final class CancelRequest extends Request
{
    protected Method $method = Method::PUT;

    public function __construct(
        private readonly string $model,
        private readonly string $requestId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/' . EndpointId::appId($this->model) . '/requests/' . $this->requestId . '/cancel';
    }
}
