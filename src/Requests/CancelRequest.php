<?php

namespace MarceloEatWorld\FalAI\Requests;

use MarceloEatWorld\FalAI\Data\GenerationData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class CancelRequest extends Request
{
    protected Method $method = Method::PUT;


    public function __construct(
        protected string $model,
        protected string $requestId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return sprintf('%s/requests/%s/cancel', $this->model, $this->requestId);
    }

    public function createDtoFromResponse(Response $response): GenerationData
    {
        return GenerationData::fromResponse($response);
    }
}