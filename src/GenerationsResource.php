<?php

namespace MarceloEatWorld\FalAI;

use MarceloEatWorld\FalAI\Data\GenerationData;
use MarceloEatWorld\FalAI\Requests\CreateRequest;
use MarceloEatWorld\FalAI\Requests\CheckStatus;
use MarceloEatWorld\FalAI\Requests\GetResult;
use MarceloEatWorld\FalAI\Requests\CancelRequest;
use MarceloEatWorld\FalAI\Requests\StreamStatus;
use MarceloEatWorld\FalAI\Requests\StreamRequest;
use Generator;

class GenerationsResource extends Resource
{
    protected ?string $webhookUrl = null;

    public function create(string $model, array $input): GenerationData
    {
        // Normalize array fields automatically
        $normalizedInput = $this->normalizeArrayFields($input);
        
        $request = new CreateRequest($model, $normalizedInput, $this->webhookUrl);
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


    public function withWebhook(string $url): self
    {
        $this->webhookUrl = $url;
        return $this;
    }

    /**
     * Submit a request to the queue for asynchronous processing (like hosmelq/falai)
     * 
     * @param string $endpointId The model or workflow identifier
     * @param array $input Input parameters
     * @param string|null $webhookUrl Optional webhook URL for notifications
     * @param string|null $hint Performance hint for queue optimization
     * @param string|null $priority Queue priority (high, normal, low)
     * @return GenerationData Queue submission response with requestId
     */
    public function submit(string $endpointId, array $input, ?string $webhookUrl = null, ?string $hint = null, ?string $priority = null): GenerationData
    {
        // Use webhook URL if provided, otherwise use the resource-level one
        $webhook = $webhookUrl ?? $this->webhookUrl;
        
        // Normalize array fields automatically
        $normalizedInput = $this->normalizeArrayFields($input);
        
        // Add performance hints if provided
        if ($hint) {
            $normalizedInput['__hint'] = $hint;
        }
        
        if ($priority) {
            $normalizedInput['__priority'] = $priority;
        }
        
        $request = new CreateRequest($endpointId, $normalizedInput, $webhook);
        $response = $this->connector->send($request);
        return GenerationData::fromResponse($response);
    }

    /**
     * Check the status of a queued request
     * 
     * @param string $endpointId The model or workflow identifier
     * @param string $requestId The request ID from submit()
     * @param bool $includeLogs Whether to include logs in the response
     * @return GenerationData Status response
     */
    public function status(string $endpointId, string $requestId, bool $includeLogs = false): GenerationData
    {
        return $this->checkStatus($endpointId, $requestId, $includeLogs);
    }

    /**
     * Get the final result from a completed request
     * 
     * @param string $endpointId The model or workflow identifier  
     * @param string $requestId The request ID from submit()
     * @return GenerationData Final result
     */
    public function result(string $endpointId, string $requestId): GenerationData
    {
        return $this->getResult($endpointId, $requestId);
    }

    /**
     * Stream status updates until completion (like hosmelq/falai)
     * 
     * @param string $endpointId The model or workflow identifier
     * @param string $requestId The request ID from submit()
     * @param bool $includeLogs Whether to include logs
     * @return Generator Yields status updates
     */
    public function streamStatus(string $endpointId, string $requestId, bool $includeLogs = true): Generator
    {
        $request = new StreamStatus($endpointId, $requestId, $includeLogs);
        $response = $this->connector->send($request);
        
        return $request->processStream($response);
    }

    /**
     * Cancel a queued request before it starts processing
     * 
     * @param string $endpointId The model or workflow identifier
     * @param string $requestId The request ID from submit()
     * @return bool True if cancelled successfully
     */
    public function cancel(string $endpointId, string $requestId): bool
    {
        $request = new CancelRequest($endpointId, $requestId);
        $response = $this->connector->send($request);
        $result = GenerationData::fromResponse($response);
        
        return $result->status === 'CANCELLED' || isset($result->cancelled);
    }

    /**
     * Stream model or workflow execution with automatic array normalization
     * Similar to hosmelq/falai stream() method
     * 
     * @param string $endpoint The model or workflow identifier
     * @param array $input The input parameters
     * @return Generator Yields events from the stream
     */
    public function stream(string $endpoint, array $input): Generator
    {
        // Normalize array fields automatically
        $normalizedInput = $this->normalizeArrayFields($input);
        
        $request = new StreamRequest($endpoint, $normalizedInput, $this->webhookUrl);
        $response = $this->connector->send($request);
        
        try {
            foreach ($response->asEventSource()->events() as $event) {
                $data = json_decode($event->data, true);
                if ($data !== null) {
                    yield $data;
                    
                    // Stop streaming if completed
                    if (isset($data['status']) && $data['status'] === 'COMPLETED') {
                        break;
                    }
                }
            }
        } catch (\HosmelQ\SSE\SSEProtocolException $e) {
            // Fallback to regular request if streaming fails
            $fallbackRequest = new CreateRequest($endpoint, $normalizedInput, $this->webhookUrl);
            $fallbackResponse = $this->connector->send($fallbackRequest);
            $data = GenerationData::fromResponse($fallbackResponse);
            yield $data->toArray();
        }
    }


    /**
     * Normalize input fields that should be arrays
     * Automatically converts single values to arrays for specific fields
     * 
     * @param array $input The input array to normalize
     * @return array The normalized input array
     */
    private function normalizeArrayFields(array $input): array
    {
        // Fields that should always be arrays, even if single value provided
        $arrayFields = [
            'reference_image_urls',
            'reference_mask_urls', 
            'image_urls',
            'images',
            'input_images',
            'mask_urls',
            'style_reference_urls',
            'character_images',
            'pose_images',
            'uploaded_masks',
            'loadimage_1',  // Pour workflow newbest
            'loadimage_2',  // Au cas où
            'loadimage_3'   // Au cas où
        ];

        $normalized = $input;

        foreach ($arrayFields as $field) {
            if (isset($normalized[$field])) {
                // If it's not already an array, convert it to one
                if (!is_array($normalized[$field])) {
                    $normalized[$field] = [$normalized[$field]];
                }
                // If it's an associative array (not a list), convert to indexed array
                elseif ($this->isAssociativeArray($normalized[$field])) {
                    $normalized[$field] = array_values($normalized[$field]);
                }
            }
        }

        return $normalized;
    }

    /**
     * Check if an array is associative (has string keys)
     * 
     * @param array $array The array to check
     * @return bool True if associative, false if indexed
     */
    private function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        
        return array_keys($array) !== range(0, count($array) - 1);
    }
}