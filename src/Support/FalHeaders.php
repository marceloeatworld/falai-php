<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Support;

use MarceloEatWorld\FalAI\Data\ObjectLifecycle;
use MarceloEatWorld\FalAI\Enums\Priority;

/**
 * Builds the platform-level X-Fal-* request headers shared by the sync,
 * streaming and queue endpoints.
 */
final class FalHeaders
{
    /**
     * @return array<string, string>
     */
    public static function build(
        ?int $timeout = null,
        bool $noRetry = false,
        ?ObjectLifecycle $lifecycle = null,
        ?bool $storeIo = null,
        ?Priority $priority = null,
        ?string $runnerHint = null,
    ): array {
        $headers = [];

        if ($timeout !== null) {
            $headers['X-Fal-Request-Timeout'] = (string) $timeout;
        }

        if ($priority !== null) {
            $headers['X-Fal-Queue-Priority'] = $priority->value;
        }

        if ($runnerHint !== null) {
            $headers['X-Fal-Runner-Hint'] = $runnerHint;
        }

        if ($noRetry) {
            $headers['X-Fal-No-Retry'] = '1';
        }

        if ($lifecycle !== null) {
            $headers['X-Fal-Object-Lifecycle-Preference'] = $lifecycle->toHeader();
        }

        if ($storeIo !== null) {
            $headers['X-Fal-Store-IO'] = $storeIo ? '1' : '0';
        }

        return $headers;
    }
}
