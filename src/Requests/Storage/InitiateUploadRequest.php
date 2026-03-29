<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Requests\Storage;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

final class InitiateUploadRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $fileName,
        private readonly string $contentType,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/storage/upload/initiate';
    }

    protected function defaultQuery(): array
    {
        return ['storage_type' => 'fal-cdn-v3'];
    }

    protected function defaultBody(): array
    {
        return [
            'file_name' => $this->fileName,
            'content_type' => $this->contentType,
        ];
    }
}
