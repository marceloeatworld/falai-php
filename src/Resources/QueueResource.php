<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Resources;

use MarceloEatWorld\FalAI\Data\ObjectLifecycle;
use MarceloEatWorld\FalAI\Data\QueuedJob;
use MarceloEatWorld\FalAI\Data\QueueStatus;
use MarceloEatWorld\FalAI\Enums\Priority;
use MarceloEatWorld\FalAI\Exceptions\QueueFailedException;
use MarceloEatWorld\FalAI\Exceptions\QueueTimeoutException;
use MarceloEatWorld\FalAI\Requests\Queue\CancelRequest;
use MarceloEatWorld\FalAI\Requests\Queue\ResultRequest;
use MarceloEatWorld\FalAI\Requests\Queue\StatusRequest;
use MarceloEatWorld\FalAI\Requests\Queue\StatusStreamRequest;
use MarceloEatWorld\FalAI\Requests\Queue\SubmitRequest;
use MarceloEatWorld\FalAI\Support\ServerSentEvents;
use Saloon\Http\BaseResource;
use Saloon\Http\Response;

final class QueueResource extends BaseResource
{
    /**
     * Submit a request to the queue.
     *
     * @param string|null $webhook URL that receives the result (fal_webhook)
     * @param int|null $timeout Server-side deadline in seconds before processing starts (X-Fal-Request-Timeout)
     * @param Priority|null $priority Queue priority (X-Fal-Queue-Priority)
     * @param string|null $runnerHint Routing hint for runner affinity (X-Fal-Runner-Hint)
     * @param bool $noRetry Disable automatic retries (X-Fal-No-Retry)
     * @param ObjectLifecycle|null $lifecycle Expiration and access control of generated files (X-Fal-Object-Lifecycle-Preference)
     * @param bool|null $storeIo false stops fal.ai from storing the request payloads (X-Fal-Store-IO)
     * @param int|null $maxQueueLength Reject with 429 when more requests are already waiting (fal_max_queue_length)
     */
    public function submit(
        string $model,
        array $input,
        ?string $webhook = null,
        ?int $timeout = null,
        ?Priority $priority = null,
        ?string $runnerHint = null,
        bool $noRetry = false,
        ?ObjectLifecycle $lifecycle = null,
        ?bool $storeIo = null,
        ?int $maxQueueLength = null,
    ): QueuedJob {
        $response = $this->connector->send(new SubmitRequest(
            model: $model,
            input: $input,
            webhook: $webhook,
            timeout: $timeout,
            priority: $priority,
            runnerHint: $runnerHint,
            noRetry: $noRetry,
            lifecycle: $lifecycle,
            storeIo: $storeIo,
            maxQueueLength: $maxQueueLength,
        ));

        return QueuedJob::fromResponse($response);
    }

    public function status(string $model, string $requestId, bool $logs = true): QueueStatus
    {
        $response = $this->connector->send(new StatusRequest(
            model: $model,
            requestId: $requestId,
            logs: $logs,
        ));

        return QueueStatus::fromResponse($response);
    }

    /**
     * Stream status updates pushed by the queue (server-sent events) until
     * the request completes. The last yielded status is the COMPLETED one.
     *
     * @return \Generator<int, QueueStatus>
     */
    public function streamStatus(string $model, string $requestId, bool $logs = true): \Generator
    {
        $response = $this->connector->send(new StatusStreamRequest(
            model: $model,
            requestId: $requestId,
            logs: $logs,
        ));

        foreach (ServerSentEvents::parse($response->stream()) as $event) {
            $status = QueueStatus::fromArray(json_decode($event['data'], true, 512, JSON_THROW_ON_ERROR));

            yield $status;

            if ($status->isCompleted()) {
                return;
            }
        }
    }

    public function result(string $model, string $requestId): Response
    {
        return $this->connector->send(new ResultRequest(
            model: $model,
            requestId: $requestId,
        ));
    }

    public function cancel(string $model, string $requestId): Response
    {
        return $this->connector->send(new CancelRequest(
            model: $model,
            requestId: $requestId,
        ));
    }

    /**
     * Submit a request and poll until completion.
     *
     * @param int $pollInterval Milliseconds between status checks
     * @param int $timeout Client-side limit in seconds for the whole wait
     * @param callable(QueueStatus): void|null $onStatus Called after every status check
     * @param int|null $requestTimeout Server-side deadline in seconds (X-Fal-Request-Timeout)
     * @throws QueueTimeoutException When polling exceeds $timeout seconds
     * @throws QueueFailedException When the job completes with an error
     */
    public function subscribe(
        string $model,
        array $input,
        ?string $webhook = null,
        int $pollInterval = 500,
        int $timeout = 300,
        ?callable $onStatus = null,
        ?int $requestTimeout = null,
        ?Priority $priority = null,
        ?string $runnerHint = null,
        bool $noRetry = false,
        ?ObjectLifecycle $lifecycle = null,
        ?bool $storeIo = null,
        ?int $maxQueueLength = null,
    ): Response {
        $job = $this->submit(
            model: $model,
            input: $input,
            webhook: $webhook,
            timeout: $requestTimeout,
            priority: $priority,
            runnerHint: $runnerHint,
            noRetry: $noRetry,
            lifecycle: $lifecycle,
            storeIo: $storeIo,
            maxQueueLength: $maxQueueLength,
        );

        $start = microtime(true);

        while (true) {
            usleep($pollInterval * 1000);

            if (microtime(true) - $start > $timeout) {
                throw new QueueTimeoutException(
                    "Queue subscribe timed out after {$timeout}s for request {$job->requestId}",
                    requestId: $job->requestId,
                );
            }

            $status = $this->status($model, $job->requestId);

            if ($onStatus !== null) {
                $onStatus($status);
            }

            if ($status->isCompleted()) {
                if ($status->hasFailed()) {
                    throw new QueueFailedException(
                        "Request {$job->requestId} failed: {$status->error}",
                        requestId: $job->requestId,
                    );
                }

                return $this->result($model, $job->requestId);
            }
        }
    }
}
