<?php
// src/GenerationsResource.php

namespace MarceloEatWorld\FalAI;

use MarceloEatWorld\FalAI\Data\GenerationData;
use MarceloEatWorld\FalAI\Requests\GenerateImage;
use MarceloEatWorld\FalAI\Requests\GetGeneration;
use MarceloEatWorld\FalAI\Requests\CancelGeneration;
use MarceloEatWorld\FalAI\Requests\GetGenerationStatus;
use MarceloEatWorld\FalAI\Requests\GetGenerationResult;
use Illuminate\Support\Facades\Log;
use Saloon\Enums\Method;
use Saloon\Http\Request;



class GenerationsResource extends Resource
{
    protected ?string $webhookUrl = null;

    public function getStatus(string $model, string $requestId): GenerationData
    {
        $model = explode('/', $model)[0];
    
        $request = new GetGenerationStatus($model, $requestId);
    
        $response = $this->connector->send($request);
    
        return GenerationData::fromResponse($response);
    }
    
    public function getResult(string $model, string $requestId): GenerationData
    {
        $model = explode('/', $model)[0];
    
        $request = new GetGenerationResult($model, $requestId);
    
        $response = $this->connector->send($request);
    
        return GenerationData::fromResponse($response);
    }

    public function create(string $model, array $input): GenerationData
    {
        $request = new GenerateImage($model, $input, $this->webhookUrl);

        $response = $this->connector->send($request);

        return GenerationData::fromResponse($response);
    }

    public function cancel(string $id): void
    {
        $request = new CancelGeneration($id);

        $this->connector->send($request);
    }

    public function withWebhook(string $url): self
    {
        $this->webhookUrl = $url;

        return $this;
    }


}