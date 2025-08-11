<?php

namespace MarceloEatWorld\FalAI;

use MarceloEatWorld\FalAI\Data\GenerationData;
use MarceloEatWorld\FalAI\Requests\SynchronousRequest;

class SynchronousGenerationsResource extends Resource
{
    /**
     * Execute a synchronous generation request
     * 
     * @param string $model The model identifier (e.g., 'fal-ai/fast-sdxl')
     * @param array $input The input parameters for the generation
     * @return GenerationData The generation result
     */
    public function run(string $model, array $input): GenerationData
    {
        $request = new SynchronousRequest($model, $input);
        $response = $this->connector->send($request);
        return GenerationData::fromResponse($response);
    }
}