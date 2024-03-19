<?php

namespace MarceloEatWorld\FalAI;

use MarceloEatWorld\FalAI\Data\GenerationData;
use MarceloEatWorld\FalAI\Data\GenerationsData;
use MarceloEatWorld\FalAI\Requests\GenerateImage;
use MarceloEatWorld\FalAI\Requests\GetGeneration;
use MarceloEatWorld\FalAI\Requests\GetGenerations;
use MarceloEatWorld\FalAI\Requests\CancelGeneration;




class GenerationsResource extends Resource
{
    protected ?string $webhookUrl = null;

    public function list(): GenerationsData
    {
        $request = new GetGenerations();
        $request->withTokenAuth($this->connector->apiKey);

        $response = $this->connector->send($request);
        return GenerationsData::fromResponse($response);
    }
    public function get(string $id, bool $withLogs = false): GenerationData
    {
        $request = new GetGeneration($id, $withLogs);
        $request->withTokenAuth($this->connector->apiKey);
    
        $response = $this->connector->send($request);
    
        return GenerationData::fromResponse($response);
    }
    public function create(string $model, array $input): GenerationData
    {
        $request = new GenerateImage($model, $input, $this->webhookUrl);
        $request->withTokenAuth("{$this->connector->apiKeyId}:{$this->connector->apiKeySecret}", 'Key');
    
        $response = $this->connector->send($request);
        
        Log::info('Create generation response:', $response->json());
        
        return GenerationData::fromResponse($response);
    }
    public function cancel(string $id): void
    {
        $request = new CancelGeneration($id);
        $request->withTokenAuth($this->connector->apiKey);

        $this->connector->send($request);
    }

    public function withWebhook(string $url): self
    {
        $this->webhookUrl = $url;

        return $this;
    }
    
}