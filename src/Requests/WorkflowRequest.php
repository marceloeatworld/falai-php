<?php
// src/Requests/WorkflowRequest.php

namespace MarceloEatWorld\FalAI\Requests;

use MarceloEatWorld\FalAI\Data\GenerationData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class WorkflowRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $workflowId,
        protected array $input,
        protected ?string $webhookUrl = null,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return $this->workflowId;
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

