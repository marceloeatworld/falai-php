<?php

namespace MarceloEatWorld\FalAI\Requests;

use MarceloEatWorld\FalAI\Data\GenerationData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetGeneration extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $model,
        protected string $id,
        protected string $apiKey,
        protected bool $withLogs = false,
    ) {
    }


    public function resolveEndpoint(): string
    {
        return sprintf('%s/requests/%s', $this->model, $this->id);
    }

    protected function defaultQuery(): array
    {
        return $this->withLogs ? ['logs' => '1'] : [];
    }

    public function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Key ' . $this->apiKey,
        ];
    }

    public function createDtoFromResponse(Response $response): GenerationData
    {
        return GenerationData::fromResponse($response);
    }
}