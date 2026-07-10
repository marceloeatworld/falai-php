<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Data;

use Saloon\Http\Response;

final readonly class ModelsPage
{
    /**
     * @param Model[] $models
     */
    public function __construct(
        public array $models,
        public ?string $nextCursor,
        public bool $hasMore,
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        return new self(
            models: array_map(Model::fromArray(...), $data['models'] ?? []),
            nextCursor: $data['next_cursor'] ?? null,
            hasMore: $data['has_more'] ?? false,
        );
    }
}
