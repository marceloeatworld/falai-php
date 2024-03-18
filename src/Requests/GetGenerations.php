<?php

namespace MarceloEatWorld\FalAI\Requests;

use MarceloEatWorld\FalAI\Data\GenerationsData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetGenerations extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/fal-ai/%s/requests/';
    }

    public function createDtoFromResponse(Response $response): GenerationsData
    {
        return GenerationsData::fromResponse($response);
    }
}