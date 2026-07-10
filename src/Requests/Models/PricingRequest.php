<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Requests\Models;

use MarceloEatWorld\FalAI\Support\QueryString;
use Psr\Http\Message\RequestInterface;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;

final class PricingRequest extends Request
{
    protected Method $method = Method::GET;

    /**
     * @param list<string> $endpointIds
     */
    public function __construct(
        private readonly array $endpointIds,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/models/pricing';
    }

    // Same repeated-key requirement as ListModelsRequest.
    public function handlePsrRequest(RequestInterface $request, PendingRequest $pendingRequest): RequestInterface
    {
        return $request->withUri(
            $request->getUri()->withQuery(QueryString::build(['endpoint_id' => $this->endpointIds]))
        );
    }
}
