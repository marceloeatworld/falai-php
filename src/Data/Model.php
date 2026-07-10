<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Data;

use MarceloEatWorld\FalAI\Enums\ModelStatus;

final readonly class Model
{
    public function __construct(
        public string $endpointId,
        public ?string $displayName = null,
        public ?string $category = null,
        public ?string $description = null,
        public ?ModelStatus $status = null,
        public array $tags = [],
        public ?string $thumbnailUrl = null,
        public ?string $modelUrl = null,
        public ?string $licenseType = null,
        public ?string $updatedAt = null,
        public array $metadata = [],
        public ?array $openapi = null,
        public ?string $enterpriseStatus = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $metadata = $data['metadata'] ?? [];

        return new self(
            endpointId: $data['endpoint_id'],
            displayName: $metadata['display_name'] ?? null,
            category: $metadata['category'] ?? null,
            description: $metadata['description'] ?? null,
            status: isset($metadata['status']) ? ModelStatus::tryFrom($metadata['status']) : null,
            tags: $metadata['tags'] ?? [],
            thumbnailUrl: $metadata['thumbnail_url'] ?? null,
            modelUrl: $metadata['model_url'] ?? null,
            licenseType: $metadata['license_type'] ?? null,
            updatedAt: $metadata['updated_at'] ?? null,
            metadata: $metadata,
            openapi: $data['openapi'] ?? null,
            enterpriseStatus: $data['enterprise_status'] ?? null,
        );
    }
}
