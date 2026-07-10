<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Resources;

use GuzzleHttp\Client;
use MarceloEatWorld\FalAI\Requests\Storage\InitiateUploadRequest;
use Saloon\Http\BaseResource;

final class StorageResource extends BaseResource
{
    private ?Client $httpClient = null;

    /**
     * Upload a local file to fal.ai storage and return its CDN URL.
     */
    public function upload(string $filePath, ?string $contentType = null): string
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $contentType ??= mime_content_type($filePath) ?: 'application/octet-stream';

        return $this->performUpload(basename($filePath), $contentType, fopen($filePath, 'r'));
    }

    /**
     * Upload in-memory content (e.g. decoded or generated data) to fal.ai
     * storage and return its CDN URL.
     */
    public function uploadData(string $data, string $fileName, string $contentType): string
    {
        return $this->performUpload($fileName, $contentType, $data);
    }

    /**
     * @param resource|string $body
     */
    private function performUpload(string $fileName, string $contentType, mixed $body): string
    {
        $response = $this->connector->send(new InitiateUploadRequest($fileName, $contentType));
        $data = $response->json();

        $this->httpClient ??= new Client();
        $this->httpClient->put($data['upload_url'], [
            'body' => $body,
            'headers' => ['Content-Type' => $contentType],
        ]);

        return $data['file_url'];
    }
}
