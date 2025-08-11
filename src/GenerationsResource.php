<?php

namespace MarceloEatWorld\FalAI;

use MarceloEatWorld\FalAI\Data\GenerationData;
use MarceloEatWorld\FalAI\Requests\CreateRequest;
use MarceloEatWorld\FalAI\Requests\CheckStatus;
use MarceloEatWorld\FalAI\Requests\GetResult;
use MarceloEatWorld\FalAI\Requests\CancelRequest;
use MarceloEatWorld\FalAI\Requests\StreamStatus;
use Generator;

class GenerationsResource extends Resource
{
    protected ?string $webhookUrl = null;

    public function create(string $model, array $input): GenerationData
    {
        $request = new CreateRequest($model, $input, $this->webhookUrl);
        $response = $this->connector->send($request);
        return GenerationData::fromResponse($response);
    }

    public function checkStatus(string $model, string $requestId, bool $includeLogs = false): GenerationData
    {
        $request = new CheckStatus($model, $requestId, $includeLogs);
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

    /**
     * Stream the status of a generation request until completion
     * 
     * @param string $model The model identifier (e.g., 'fal-ai/fast-sdxl')
     * @param string $requestId The request ID from the create response
     * @param bool $includeLogs Whether to include logs in the stream
     * @return Generator Yields status updates as arrays
     */
    public function streamStatus(string $model, string $requestId, bool $includeLogs = false): Generator
    {
        $request = new StreamStatus($model, $requestId, $includeLogs);
        $response = $this->connector->send($request);
        
        return $request->processStream($response);
    }
}