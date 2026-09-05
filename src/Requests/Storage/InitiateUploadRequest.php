<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Requests\Storage;

use MarceloEatWorld\FalAI\Data\ObjectLifecycle;
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
        private readonly ?ObjectLifecycle $lifecycle = null,
        private readonly bool $multipart = false,
    ) {}

    public function resolveEndpoint(): string
    {
        return $this->multipart
            ? '/storage/upload/initiate-multipart'
            : '/storage/upload/initiate';
    }

    protected function defaultHeaders(): array
    {
        if ($this->lifecycle === null) {
            return [];
        }

        // The official clients send both spellings: the CDN reads
        // X-Fal-Object-Lifecycle, the storage API reads the -Preference one.
        $header = $this->lifecycle->toHeader();

        return [
            'X-Fal-Object-Lifecycle' => $header,
            'X-Fal-Object-Lifecycle-Preference' => $header,
        ];
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
