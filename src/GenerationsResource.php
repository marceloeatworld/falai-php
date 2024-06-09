<?php
// src/GenerationsResource.php

namespace MarceloEatWorld\FalAI;

use MarceloEatWorld\FalAI\Data\GenerationData;
use MarceloEatWorld\FalAI\Requests\GenerateImage;
use MarceloEatWorld\FalAI\Requests\GetGeneration;
use MarceloEatWorld\FalAI\Requests\CancelGeneration;
use MarceloEatWorld\FalAI\Requests\GetGenerationStatus;
use MarceloEatWorld\FalAI\Requests\GetGenerationResult;
use MarceloEatWorld\FalAI\Requests\GetWorkflow;
use Illuminate\Support\Facades\Log;
use Saloon\Enums\Method;
use Saloon\Http\Request;



class GenerationsResource extends Resource
{
    protected ?string $webhookUrl = null;
    
    public function getWorkflow(string $workflowId, array $input, ?string $webhookUrl = null, int $maxRetries = 5, int $initialDelay = 1): GenerationData
    {
        $retries = 0;
        $retryDelay = $initialDelay;
    
        while ($retries < $maxRetries) {
            try {
                $request = new GetWorkflow($workflowId, $input);
    
                if ($webhookUrl) {
                    $request->withQuery(['fal_webhook' => $webhookUrl]);
                }
    
                $response = $this->connector->send($request);
    
                $data = $response->json();
    
                if (!isset($data['request_id'])) {
                    throw new \Exception('Missing required key "request_id" in FAL AI API response');
                }
    
                return new GenerationData(
                    requestId: $data['request_id'],
                    responseUrl: $data['response_url'] ?? null,
                    statusUrl: $data['status_url'] ?? null,
                    cancelUrl: $data['cancel_url'] ?? null,
                    status: $data['status'] ?? null,
                    payload: $data['payload'] ?? null,
                    error: $data['error'] ?? null,
                );
            } catch (\Exception $e) {
                $retries++;
    
                if ($retries >= $maxRetries) {
                    Log::error('Max retries reached. Error submitting FAL AI workflow: ' . $e->getMessage(), [
                        'workflowId' => $workflowId,
                        'input' => $input,
                        'webhookUrl' => $webhookUrl,
                        'exception' => [
                            'message' => $e->getMessage(),
                            'code' => $e->getCode(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString(),
                        ],
                    ]);
                    throw $e;
                }
    
                Log::warning('Retry ' . $retries . ': ' . $e->getMessage(), [
                    'workflowId' => $workflowId,
                    'input' => $input,
                    'webhookUrl' => $webhookUrl,
                    'exception' => [
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ],
                ]);
    
                sleep($retryDelay);
                $retryDelay *= 2;
            }
        }
    
        throw new \Exception('All retries failed. Unable to submit FAL AI workflow.');
    }

    // Private method to extract the model
    private function extractModel(string $model): string
    {
        if (strpos($model, 'workflows/') !== false) {
            return explode('/', $model)[2]; 
        } else {
            return strpos($model, '/') !== false ? explode('/', $model)[0] : $model;
        }
    }

    public function getStatus(string $model, string $requestId): GenerationData
    {
        $model = strpos($model, '/') !== false ? explode('/', $model)[0] : $model;
    
        $request = new GetGenerationStatus($model, $requestId);
    
        $response = $this->connector->send($request);
    
        return GenerationData::fromResponse($response);
    }
    
    public function getResult(string $model, string $requestId): GenerationData
    {
        $model = strpos($model, '/') !== false ? explode('/', $model)[0] : $model;
    
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