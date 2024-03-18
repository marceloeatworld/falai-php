<?php

namespace MarceloEatWorld\FalAI\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class CancelGeneration extends Request
{
    protected Method $method = Method::PUT;

    public function __construct(
        protected string $id,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/queue.fal.run/fal-ai/requests/' . $this->id . '/cancel';
    }
}