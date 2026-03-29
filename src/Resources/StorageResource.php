<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Resources;

use GuzzleHttp\Client;
use MarceloEatWorld\FalAI\Requests\Storage\InitiateUploadRequest;
use Saloon\Http\BaseResource;

final class StorageResource extends BaseResource
{
    /**
     * Upload a local file to fal.ai storage and return its CDN URL.
     */
    public function upload(string $filePath, ?string $contentType = null): string
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $contentType ??= mime_content_type($filePath) ?: 'application/octet-stream';
        $fileName = basename($filePath);

        $response = $this->connector->send(new InitiateUploadRequest($fileName, $contentType));
        $data = $response->json();

        $client = new Client();
        $client->put($data['upload_url'], [
            'body' => fopen($filePath, 'r'),
            'headers' => ['Content-Type' => $contentType],
        ]);

        return $data['file_url'];
    }
}
