<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Requests\Models;

use MarceloEatWorld\FalAI\Enums\ModelStatus;
use MarceloEatWorld\FalAI\Support\QueryString;
use Psr\Http\Message\RequestInterface;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;

final class ListModelsRequest extends Request
{
    protected Method $method = Method::GET;

    /**
     * @param list<string> $endpointIds
     * @param list<string> $expand
     */
    public function __construct(
        // Named $search because Saloon's Request already owns a $query property.
        private readonly ?string $search = null,
        private readonly ?string $category = null,
        private readonly ?ModelStatus $status = null,
        private readonly ?int $limit = null,
        private readonly ?string $cursor = null,
        private readonly array $endpointIds = [],
        private readonly array $expand = [],
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/models';
    }

    // The API expects repeated keys (endpoint_id=a&endpoint_id=b) and ignores
    // the indexed form produced by Saloon's query store, so the query string
    // is written directly onto the PSR request.
    public function handlePsrRequest(RequestInterface $request, PendingRequest $pendingRequest): RequestInterface
    {
        $params = array_filter([
            'q' => $this->search,
            'category' => $this->category,
            'status' => $this->status?->value,
            'limit' => $this->limit,
            'cursor' => $this->cursor,
            'endpoint_id' => $this->endpointIds,
            'expand' => $this->expand,
        ], fn (mixed $value): bool => $value !== null && $value !== []);

        return $request->withUri(
            $request->getUri()->withQuery(QueryString::build($params))
        );
    }
}
