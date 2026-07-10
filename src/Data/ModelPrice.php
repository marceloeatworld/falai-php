<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Data;

final readonly class ModelPrice
{
    public function __construct(
        public string $endpointId,
        public float $unitPrice,
        public string $unit,
        public string $currency,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            endpointId: $data['endpoint_id'],
            unitPrice: (float) $data['unit_price'],
            unit: $data['unit'],
            currency: $data['currency'],
        );
    }
}
