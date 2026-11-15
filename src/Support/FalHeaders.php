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
     * @param array<string, string> $headers Extra headers, overridden by the named options
     * @return array<string, string>
     */
    public static function build(
        int|float|null $timeout = null,
        bool $noRetry = false,
        ?ObjectLifecycle $lifecycle = null,
        ?bool $storeIo = null,
        ?Priority $priority = null,
        ?string $runnerHint = null,
        array $headers = [],
    ): array {
        if ($timeout !== null) {
            // Replaces the deprecated X-Fal-Request-Timeout.
            $headers['X-Fal-Request-Start-Timeout'] = (string) $timeout;
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
