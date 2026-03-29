<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Data;

use MarceloEatWorld\FalAI\Enums\Status;
use Saloon\Http\Response;

final readonly class QueueStatus
{
    public function __construct(
        public Status $status,
        public string $requestId,
        public ?int $queuePosition = null,
        public ?string $responseUrl = null,
        public array $logs = [],
        public ?float $inferenceTime = null,
        public ?string $error = null,
        public ?string $errorType = null,
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        return new self(
            status: Status::from($data['status']),
            requestId: $data['request_id'],
            queuePosition: $data['queue_position'] ?? null,
            responseUrl: $data['response_url'] ?? null,
            logs: $data['logs'] ?? [],
            inferenceTime: $data['metrics']['inference_time'] ?? null,
            error: $data['error'] ?? null,
            errorType: $data['error_type'] ?? null,
        );
    }

    public function isCompleted(): bool
    {
        return $this->status === Status::Completed;
    }

    public function hasFailed(): bool
    {
        return $this->isCompleted() && $this->error !== null;
    }
}
