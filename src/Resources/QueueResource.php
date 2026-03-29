<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Resources;

use MarceloEatWorld\FalAI\Data\QueuedJob;
use MarceloEatWorld\FalAI\Data\QueueStatus;
use MarceloEatWorld\FalAI\Enums\Priority;
use MarceloEatWorld\FalAI\Requests\Queue\CancelRequest;
use MarceloEatWorld\FalAI\Requests\Queue\ResultRequest;
use MarceloEatWorld\FalAI\Requests\Queue\StatusRequest;
use MarceloEatWorld\FalAI\Requests\Queue\SubmitRequest;
use Saloon\Http\BaseResource;
use Saloon\Http\Response;

final class QueueResource extends BaseResource
{
    public function submit(
        string $model,
        array $input,
        ?string $webhook = null,
        ?int $timeout = null,
        ?Priority $priority = null,
        ?string $runnerHint = null,
        bool $noRetry = false,
    ): QueuedJob {
        $response = $this->connector->send(new SubmitRequest(
            model: $model,
            input: $input,
            webhook: $webhook,
            timeout: $timeout,
            priority: $priority,
            runnerHint: $runnerHint,
            noRetry: $noRetry,
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
     * @param callable(QueueStatus): void|null $onStatus
     * @throws \RuntimeException When the request times out or the job fails
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
    ): Response {
        $job = $this->submit(
            model: $model,
            input: $input,
            webhook: $webhook,
            timeout: $requestTimeout,
            priority: $priority,
        );

        $start = microtime(true);

        while (true) {
            usleep($pollInterval * 1000);

            if (microtime(true) - $start > $timeout) {
                throw new \RuntimeException(
                    "Queue subscribe timed out after {$timeout}s for request {$job->requestId}"
                );
            }

            $status = $this->status($model, $job->requestId);

            if ($onStatus !== null) {
                $onStatus($status);
            }

            if ($status->isCompleted()) {
                if ($status->hasFailed()) {
                    throw new \RuntimeException(
                        "Request {$job->requestId} failed: {$status->error}"
                    );
                }

                return $this->result($model, $job->requestId);
            }
        }
    }
}
