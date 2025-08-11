<?php

namespace MarceloEatWorld\FalAI\Enums;

enum RequestStatus: string
{
    case IN_QUEUE = 'IN_QUEUE';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case ERROR = 'ERROR';
    case CANCELLATION_REQUESTED = 'CANCELLATION_REQUESTED';
    case ALREADY_COMPLETED = 'ALREADY_COMPLETED';

    /**
     * Check if the request is still processing
     */
    public function isProcessing(): bool
    {
        return in_array($this, [self::IN_QUEUE, self::IN_PROGRESS], true);
    }

    /**
     * Check if the request has finished (successfully or with error)
     */
    public function isFinished(): bool
    {
        return in_array($this, [self::COMPLETED, self::ERROR, self::ALREADY_COMPLETED], true);
    }

    /**
     * Check if the request completed successfully
     */
    public function isSuccess(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if the request has an error
     */
    public function hasError(): bool
    {
        return $this === self::ERROR;
    }
}