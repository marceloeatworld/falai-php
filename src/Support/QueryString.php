<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Support;

final class QueryString
{
    /**
     * Build a query string with repeated keys for list values
     * (?endpoint_id=a&endpoint_id=b), as expected by api.fal.ai.
     * http_build_query() cannot produce this format: it appends
     * array indexes ([0], [1]) that the API silently ignores.
     *
     * @param array<string, string|int|list<string>> $params
     */
    public static function build(array $params): string
    {
        $parts = [];

        foreach ($params as $key => $value) {
            foreach ((array) $value as $item) {
                $parts[] = rawurlencode($key) . '=' . rawurlencode((string) $item);
            }
        }

        return implode('&', $parts);
    }
}
