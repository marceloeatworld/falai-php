<?php

namespace MarceloEatWorld\FalAI\Requests;

use MarceloEatWorld\FalAI\Data\GenerationData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class SynchronousRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $model,
        protected array $input,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return $this->model;
    }

    protected function defaultBody(): array
    {
        return $this->input;
    }

    public function createDtoFromResponse(Response $response): GenerationData
    {
        return GenerationData::fromResponse($response);
    }
}