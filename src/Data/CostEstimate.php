<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Data;

use Saloon\Http\Response;

final readonly class CostEstimate
{
    public function __construct(
        public string $estimateType,
        public float $totalCost,
        public string $currency,
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        return new self(
            estimateType: $data['estimate_type'],
            totalCost: (float) $data['total_cost'],
            currency: $data['currency'],
        );
    }
}
