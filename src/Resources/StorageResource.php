<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Resources;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use MarceloEatWorld\FalAI\Data\ObjectLifecycle;
use MarceloEatWorld\FalAI\Requests\Storage\InitiateUploadRequest;
use Saloon\Http\BaseResource;

final class StorageResource extends BaseResource
{
    /**
     * Files above this size are uploaded in parts, like the official clients do.
     */
    public const MULTIPART_THRESHOLD = 90 * 1024 * 1024;

    public const MULTIPART_CHUNK_SIZE = 10 * 1024 * 1024;

    private const PART_UPLOAD_ATTEMPTS = 3;

    private ?Client $httpClient = null;

    /**
     * Upload a local file to the fal.ai CDN and return its URL.
     *
     * @param ObjectLifecycle|null $lifecycle Expiration and access control for the file
     */
    public function upload(string $filePath, ?string $contentType = null, ?ObjectLifecycle $lifecycle = null): string
    {
        if (! is_file($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $contentType ??= mime_content_type($filePath) ?: 'application/octet-stream';
        $fileName = basename($filePath);

        if (filesize($filePath) > self::MULTIPART_THRESHOLD) {
            $handle = fopen($filePath, 'rb');

            try {
                return $this->multipartUpload(
                    $fileName,
                    $contentType,
                    $lifecycle,
                    function () use ($handle): ?string {
                        $chunk = fread($handle, self::MULTIPART_CHUNK_SIZE);

                        return $chunk === '' || $chunk === false ? null : $chunk;
                    },
                );
            } finally {
                fclose($handle);
            }
        }

        return $this->singleUpload($fileName, $contentType, $lifecycle, fopen($filePath, 'rb'));
    }

    /**
     * Upload in-memory content (e.g. decoded or generated data) to the
     * fal.ai CDN and return its URL.
     *
     * @param ObjectLifecycle|null $lifecycle Expiration and access control for the file
     */
    public function uploadData(string $data, string $fileName, string $contentType, ?ObjectLifecycle $lifecycle = null): string
    {
        if (strlen($data) > self::MULTIPART_THRESHOLD) {
            $offset = 0;

            return $this->multipartUpload(
                $fileName,
                $contentType,
                $lifecycle,
                function () use ($data, &$offset): ?string {
                    if ($offset >= strlen($data)) {
                        return null;
                    }

                    $chunk = substr($data, $offset, self::MULTIPART_CHUNK_SIZE);
                    $offset += strlen($chunk);

                    return $chunk;
                },
            );
        }

        return $this->singleUpload($fileName, $contentType, $lifecycle, $data);
    }

    /**
     * @param resource|string $body
     */
    private function singleUpload(string $fileName, string $contentType, ?ObjectLifecycle $lifecycle, mixed $body): string
    {
        $data = $this->connector->send(new InitiateUploadRequest($fileName, $contentType, $lifecycle))->json();

        $this->httpClient()->put($data['upload_url'], [
            'body' => $body,
            'headers' => ['Content-Type' => $contentType],
        ]);

        return $data['file_url'];
    }

    /**
     * Multipart flow of the official clients: initiate, PUT each part to
     * {upload_url}/{partNumber}, then POST the collected etags to
     * {upload_url}/complete.
     *
     * @param callable(): ?string $nextChunk Returns the next chunk, or null when exhausted
     */
    private function multipartUpload(string $fileName, string $contentType, ?ObjectLifecycle $lifecycle, callable $nextChunk): string
    {
        $data = $this->connector->send(new InitiateUploadRequest($fileName, $contentType, $lifecycle, multipart: true))->json();

        $uploadUrl = $data['upload_url'];
        $query = parse_url($uploadUrl, PHP_URL_QUERY);
        $base = $query === null ? $uploadUrl : substr($uploadUrl, 0, -strlen($query) - 1);
        $suffix = $query === null ? '' : '?' . $query;

        $parts = [];
        $partNumber = 0;

        while (($chunk = $nextChunk()) !== null) {
            $partNumber++;
            $parts[] = $this->uploadPart("{$base}/{$partNumber}{$suffix}", $partNumber, $chunk);
        }

        $this->httpClient()->post("{$base}/complete{$suffix}", [
            'json' => ['parts' => $parts],
        ]);

        return $data['file_url'];
    }

    /**
     * @return array{partNumber: int, etag: string}
     */
    private function uploadPart(string $url, int $partNumber, string $chunk): array
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                $response = $this->httpClient()->put($url, ['body' => $chunk]);
                $result = json_decode((string) $response->getBody(), true);

                return [
                    'partNumber' => (int) ($result['partNumber'] ?? $partNumber),
                    'etag' => (string) ($result['etag'] ?? trim($response->getHeaderLine('ETag'), '"')),
                ];
            } catch (GuzzleException $exception) {
                if ($attempt >= self::PART_UPLOAD_ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }

    private function httpClient(): Client
    {
        return $this->httpClient ??= new Client();
    }
}
