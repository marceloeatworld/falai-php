<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI;

use MarceloEatWorld\FalAI\Connectors\PlatformConnector;
use MarceloEatWorld\FalAI\Data\ObjectLifecycle;
use MarceloEatWorld\FalAI\Connectors\QueueConnector;
use MarceloEatWorld\FalAI\Connectors\StorageConnector;
use MarceloEatWorld\FalAI\Connectors\SyncConnector;
use MarceloEatWorld\FalAI\Requests\Sync\RunRequest;
use MarceloEatWorld\FalAI\Requests\Sync\StreamRequest;
use MarceloEatWorld\FalAI\Resources\ModelsResource;
use MarceloEatWorld\FalAI\Resources\QueueResource;
use MarceloEatWorld\FalAI\Resources\StorageResource;
use MarceloEatWorld\FalAI\Support\ServerSentEvents;
use MarceloEatWorld\FalAI\Webhooks\WebhookVerifier;
use Saloon\Http\Response;

final class FalAI
{
    public readonly QueueResource $queue;
    public readonly StorageResource $storage;
    public readonly ModelsResource $models;

    private readonly SyncConnector $syncConnector;

    private ?WebhookVerifier $webhookVerifier = null;

    public function __construct(
        string $apiKey,
        string $queueBaseUrl = 'https://queue.fal.run',
        string $syncBaseUrl = 'https://fal.run',
        string $storageBaseUrl = 'https://rest.fal.ai',
        string $platformBaseUrl = 'https://api.fal.ai',
    ) {
        $queueConnector = new QueueConnector($apiKey, $queueBaseUrl);
        $this->syncConnector = new SyncConnector($apiKey, $syncBaseUrl);
        $storageConnector = new StorageConnector($apiKey, $storageBaseUrl);
        $platformConnector = new PlatformConnector($apiKey, $platformBaseUrl);

        $this->queue = new QueueResource($queueConnector);
        $this->storage = new StorageResource($storageConnector);
        $this->models = new ModelsResource($platformConnector);
    }

    /**
     * Run a model synchronously and return the response.
     *
     * @param int|null $timeout Server-side deadline in seconds before processing starts (X-Fal-Request-Timeout)
     * @param bool $noRetry Disable automatic retries (X-Fal-No-Retry)
     * @param ObjectLifecycle|null $lifecycle Expiration and access control of generated files (X-Fal-Object-Lifecycle-Preference)
     * @param bool|null $storeIo false stops fal.ai from storing the request payloads (X-Fal-Store-IO)
     */
    public function run(
        string $model,
        array $input,
        ?int $timeout = null,
        bool $noRetry = false,
        ?ObjectLifecycle $lifecycle = null,
        ?bool $storeIo = null,
    ): Response {
        return $this->syncConnector->send(new RunRequest(
            model: $model,
            input: $input,
            timeout: $timeout,
            noRetry: $noRetry,
            lifecycle: $lifecycle,
            storeIo: $storeIo,
        ));
    }

    /**
     * Run a model on its /stream endpoint and yield each event as it
     * arrives. The final result is the last event; it is also available as
     * the generator's return value. Only models with a /stream endpoint
     * support this.
     *
     * @return \Generator<int, array, mixed, array|null>
     */
    public function stream(
        string $model,
        array $input,
        ?int $timeout = null,
        bool $noRetry = false,
        ?ObjectLifecycle $lifecycle = null,
        ?bool $storeIo = null,
    ): \Generator {
        $response = $this->syncConnector->send(new StreamRequest(
            model: $model,
            input: $input,
            timeout: $timeout,
            noRetry: $noRetry,
            lifecycle: $lifecycle,
            storeIo: $storeIo,
        ));

        $last = null;

        foreach (ServerSentEvents::parse($response->stream()) as $event) {
            $last = json_decode($event['data'], true, 512, JSON_THROW_ON_ERROR);

            yield $last;
        }

        return $last;
    }

    /**
     * Verifier for incoming fal.ai webhook signatures.
     */
    public function webhooks(): WebhookVerifier
    {
        return $this->webhookVerifier ??= new WebhookVerifier();
    }
}
