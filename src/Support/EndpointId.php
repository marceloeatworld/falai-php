<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Support;

final class EndpointId
{
    /**
     * Namespaces that prefix the owner/alias pair (workflows/owner/alias).
     */
    private const NAMESPACES = ['workflows', 'comfy'];

    /**
     * Reduce a model id to the application id that the queue uses for
     * status, result and cancel URLs.
     *
     * Requests are submitted to the full endpoint id (fal-ai/flux/schnell)
     * but the queue tracks them per application (fal-ai/flux), so any
     * sub-path after owner/alias must be dropped, exactly like the
     * official fal clients do.
     */
    public static function appId(string $model): string
    {
        $parts = explode('/', trim($model, '/'));

        $length = in_array($parts[0], self::NAMESPACES, true) ? 3 : 2;

        // Unknown shapes are passed through untouched and left to the API.
        if (count($parts) < $length) {
            return $model;
        }

        return implode('/', array_slice($parts, 0, $length));
    }
}
