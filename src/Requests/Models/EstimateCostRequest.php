<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Requests\Models;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

final class EstimateCostRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param array<string, array{call_quantity?: int, unit_quantity?: float|int}> $endpoints
     */
    public function __construct(
        private readonly string $estimateType,
        private readonly array $endpoints,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/models/pricing/estimate';
    }

    protected function defaultBody(): array
    {
        return [
            'estimate_type' => $this->estimateType,
            'endpoints' => $this->endpoints,
        ];
    }
}
