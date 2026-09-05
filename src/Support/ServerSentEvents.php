<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Support;

use Psr\Http\Message\StreamInterface;

/**
 * Minimal text/event-stream parser: yields one event per blank-line
 * separated block, with multi-line "data:" fields joined by newlines.
 */
final class ServerSentEvents
{
    private const CHUNK_SIZE = 8192;

    /**
     * @return \Generator<int, array{event: string|null, data: string}>
     */
    public static function parse(StreamInterface $stream): \Generator
    {
        $buffer = '';

        while (! $stream->eof()) {
            $buffer .= $stream->read(self::CHUNK_SIZE);
            $buffer = str_replace("\r\n", "\n", $buffer);

            while (($separator = strpos($buffer, "\n\n")) !== false) {
                $block = substr($buffer, 0, $separator);
                $buffer = substr($buffer, $separator + 2);

                $event = self::parseBlock($block);

                if ($event !== null) {
                    yield $event;
                }
            }
        }

        $event = self::parseBlock($buffer);

        if ($event !== null) {
            yield $event;
        }
    }

    /**
     * @return array{event: string|null, data: string}|null
     */
    private static function parseBlock(string $block): ?array
    {
        $event = null;
        $data = [];

        foreach (explode("\n", $block) as $line) {
            if ($line === '' || $line[0] === ':') {
                continue;
            }

            [$field, $value] = array_pad(explode(':', $line, 2), 2, '');
            $value = ltrim($value, ' ');

            if ($field === 'data') {
                $data[] = $value;
            } elseif ($field === 'event') {
                $event = $value;
            }
        }

        if ($data === []) {
            return null;
        }

        return ['event' => $event, 'data' => implode("\n", $data)];
    }
}
