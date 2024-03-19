<?php

namespace MarceloEatWorld\FalAI\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class CancelGeneration extends Request
{
    protected Method $method = Method::PUT;

    public function __construct(
        protected string $id,
        protected string $apiKey,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return sprintf('/requests/%s/cancel', $this->id);
    }

    public function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Key ' . $this->apiKey,
        ];
    }
}