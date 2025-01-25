<?php

namespace MarceloEatWorld\FalAI\Requests;

use MarceloEatWorld\FalAI\Data\GenerationData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class CreateRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    protected function getBasePath(string $path): string 
    {
        return $path;
    }

    public function __construct(
        protected string $model,
        protected array $input,
        protected ?string $webhookUrl = null,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return $this->getBasePath($this->model);
    }

    protected function defaultBody(): array
    {
        return $this->input;
    }

    protected function defaultQuery(): array
    {
        return $this->webhookUrl ? ['fal_webhook' => $this->webhookUrl] : [];
    }

    public function createDtoFromResponse(Response $response): GenerationData
    {
        return GenerationData::fromResponse($response);
    }
}
