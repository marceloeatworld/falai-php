<?php

namespace MarceloEatWorld\FalAI;

use MarceloEatWorld\FalAI\Data\GenerationData;
use MarceloEatWorld\FalAI\Data\GenerationsData;
use MarceloEatWorld\FalAI\Requests\GenerateImage;
use MarceloEatWorld\FalAI\Requests\GetGeneration;
use MarceloEatWorld\FalAI\Requests\GetGenerations;
use MarceloEatWorld\FalAI\Requests\CancelGeneration;
use Illuminate\Support\Facades\Log;

class GenerationsResource extends Resource
{
    protected ?string $webhookUrl = null;

    public function list(): GenerationsData
    {
        $request = new GetGenerations($this->connector->apiKey);

        $response = $this->connector->send($request);
        return GenerationsData::fromResponse($response);
    }

    public function get(string $model, string $id, bool $withLogs = false): GenerationData
    {
        $model = explode('/', $model)[0];

        $request = new GetGeneration($model, $id, $this->connector->apiKey, $withLogs);

        $response = $this->connector->send($request);
        Log::info('Raw response:', [$request->body()]);

        Log::info('Raw response:', [$response->body()]);

        return GenerationData::fromResponse($response);
    }

    public function create(string $model, array $input): GenerationData
    {
        $request = new GenerateImage($model, $input, $this->connector->apiKey, $this->webhookUrl);

        $response = $this->connector->send($request);

        return GenerationData::fromResponse($response);
    }

    public function cancel(string $id): void
    {
        $request = new CancelGeneration($id, $this->connector->apiKey);

        $this->connector->send($request);
    }

    public function withWebhook(string $url): self
    {
        $this->webhookUrl = $url;
    
        return $this;
    }
    public function subscribeToUpdates(string $webhookUrl, callable $callback): void
    {
        $request = new GenerateImage($model, [], $this->connector->apiKey, $webhookUrl);

        $response = $this->connector->send($request);

        $data = GenerationData::fromResponse($response);

        $callback($data);
    }
}