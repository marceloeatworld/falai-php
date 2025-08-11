<?php

namespace MarceloEatWorld\FalAI\Requests;

use MarceloEatWorld\FalAI\Data\GenerationData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class CheckStatus extends Request
{
    protected Method $method = Method::GET;


    public function __construct(
        protected string $model,
        protected string $requestId,
        protected bool $includeLogs = false,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return sprintf('%s/requests/%s/status', $this->model, $this->requestId);
    }

    protected function defaultQuery(): array
    {
        return $this->includeLogs ? ['logs' => '1'] : [];
    }

    public function createDtoFromResponse(Response $response): GenerationData
    {
        return GenerationData::fromResponse($response);
    }
}