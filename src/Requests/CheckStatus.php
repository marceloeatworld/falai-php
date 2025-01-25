<?php

namespace MarceloEatWorld\FalAI\Requests;

use MarceloEatWorld\FalAI\Data\GenerationData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class CheckStatus extends Request
{
    protected Method $method = Method::GET;

    protected function getBasePath(string $path): string 
    {
        $parts = explode('/', $path);
        if (count($parts) > 2 && $parts[0] === 'fal-ai') {
            return implode('/', $parts);
        }
        return $path;
    }

    public function __construct(
        protected string $model,
        protected string $requestId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        $basePath = $this->getBasePath($this->model);
        return sprintf('%s/requests/%s/status', $basePath, $this->requestId);
    }

    protected function defaultQuery(): array
    {
        return ['logs' => '1'];
    }

    public function createDtoFromResponse(Response $response): GenerationData
    {
        return GenerationData::fromResponse($response);
    }
}