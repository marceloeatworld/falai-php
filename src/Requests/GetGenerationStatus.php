<?php
namespace MarceloEatWorld\FalAI\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetGenerationStatus extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $model,
        protected string $requestId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "{$this->model}/requests/{$this->requestId}/status";
    }
}