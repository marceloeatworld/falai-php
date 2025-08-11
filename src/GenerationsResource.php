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
            'uploaded_masks'
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