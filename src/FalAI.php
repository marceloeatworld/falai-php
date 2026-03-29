<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI;

use MarceloEatWorld\FalAI\Connectors\QueueConnector;
use MarceloEatWorld\FalAI\Connectors\StorageConnector;
use MarceloEatWorld\FalAI\Connectors\SyncConnector;
use MarceloEatWorld\FalAI\Requests\Sync\RunRequest;
use MarceloEatWorld\FalAI\Resources\QueueResource;
use MarceloEatWorld\FalAI\Resources\StorageResource;
use Saloon\Http\Response;

final class FalAI
{
    public readonly QueueResource $queue;
    public readonly StorageResource $storage;

    private readonly SyncConnector $syncConnector;

    public function __construct(
        string $apiKey,
        string $queueBaseUrl = 'https://queue.fal.run',
        string $syncBaseUrl = 'https://fal.run',
        string $storageBaseUrl = 'https://rest.alpha.fal.ai',
    ) {
        $queueConnector = new QueueConnector($apiKey, $queueBaseUrl);
        $this->syncConnector = new SyncConnector($apiKey, $syncBaseUrl);
        $storageConnector = new StorageConnector($apiKey, $storageBaseUrl);

        $this->queue = new QueueResource($queueConnector);
        $this->storage = new StorageResource($storageConnector);
    }

    /**
     * Run a model synchronously and return the response.
     */
    public function run(
        string $model,
        array $input,
        ?int $timeout = null,
        bool $noRetry = false,
    ): Response {
        return $this->syncConnector->send(new RunRequest(
            model: $model,
            input: $input,
            timeout: $timeout,
            noRetry: $noRetry,
        ));
    }
}
