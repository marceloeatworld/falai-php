<?php

namespace MarceloEatWorld\FalAI;

use MarceloEatWorld\FalAI\Data\GenerationData;
use MarceloEatWorld\FalAI\Requests\CreateRequest;
use MarceloEatWorld\FalAI\Requests\CheckStatus;
use MarceloEatWorld\FalAI\Requests\GetResult;
use MarceloEatWorld\FalAI\Requests\CancelRequest;

class GenerationsResource extends Resource
{
    protected ?string $webhookUrl = null;

    public function create(string $model, array $input): GenerationData
    {
        $request = new CreateRequest($model, $input, $this->webhookUrl);
        $response = $this->connector->send($request);
        return GenerationData::fromResponse($response);
    }

    public function checkStatus(string $model, string $requestId): GenerationData
    {
        $request = new CheckStatus($model, $requestId);
        $response = $this->connector->send($request);
        return GenerationData::fromResponse($response);
    }

    public function getResult(string $model, string $requestId): GenerationData
    {
        $request = new GetResult($model, $requestId);
        $response = $this->connector->send($request);
        return GenerationData::fromResponse($response);
    }

    public function cancel(string $model, string $requestId): GenerationData
    {
        $request = new CancelRequest($model, $requestId);
        $response = $this->connector->send($request);
        return GenerationData::fromResponse($response);
    }

    public function withWebhook(string $url): self
    {
        $this->webhookUrl = $url;
        return $this;
    }
}