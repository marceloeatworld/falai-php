<?php
// src/GenerationsResource.php

namespace MarceloEatWorld\FalAI;

use MarceloEatWorld\FalAI\Data\GenerationData;
use MarceloEatWorld\FalAI\Requests\GenerateImage;
use MarceloEatWorld\FalAI\Requests\WorkflowRequest;
use Illuminate\Support\Facades\Log;
use Saloon\Enums\Method;
use Saloon\Http\Request;



class GenerationsResource extends Resource
{
    protected ?string $webhookUrl = null;

    public function workflow(string $workflowId, array $input): GenerationData
    {
        $request = new WorkflowRequest($workflowId, $input, $this->webhookUrl);

        $response = $this->connector->send($request);

        return GenerationData::fromResponse($response);
    }
    
    public function create(string $model, array $input): GenerationData
    {
        $request = new GenerateImage($model, $input, $this->webhookUrl);

        $response = $this->connector->send($request);

        return GenerationData::fromResponse($response);
    }


    public function withWebhook(string $url): self
    {
        $this->webhookUrl = $url;

        return $this;
    }


}