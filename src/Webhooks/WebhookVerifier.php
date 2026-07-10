<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Webhooks;

use GuzzleHttp\Client;
use MarceloEatWorld\FalAI\Exceptions\WebhookVerificationException;
use SodiumException;

/**
 * Verifies the ED25519 signature that fal.ai attaches to every webhook
 * request via the X-Fal-Webhook-* headers.
 *
 * Public keys are fetched from the fal.ai JWKS endpoint once per instance,
 * so reuse the same instance (e.g. as a singleton) across requests.
 */
final class WebhookVerifier
{
    public const JWKS_URL = 'https://rest.fal.ai/.well-known/jwks.json';

    private const TIMESTAMP_TOLERANCE = 300;

    /** @var list<string>|null */
    private ?array $publicKeys = null;

    public function __construct(
        private readonly Client $client = new Client(['timeout' => 10]),
        private readonly string $jwksUrl = self::JWKS_URL,
        private readonly int $timestampTolerance = self::TIMESTAMP_TOLERANCE,
    ) {}

    /**
     * Verify a webhook from its raw body and HTTP headers. Header names are
     * matched case-insensitively; values may be strings or arrays of strings
     * (as returned by getallheaders(), PSR-7 or Laravel requests).
     *
     * @param array<string, string|list<string>> $headers
     * @throws WebhookVerificationException When the webhook cannot be authenticated
     */
    public function verify(string $body, array $headers): void
    {
        $this->verifySignature(
            body: $body,
            requestId: $this->header($headers, 'X-Fal-Webhook-Request-Id'),
            userId: $this->header($headers, 'X-Fal-Webhook-User-Id'),
            timestamp: $this->header($headers, 'X-Fal-Webhook-Timestamp'),
            signature: $this->header($headers, 'X-Fal-Webhook-Signature'),
        );
    }

    /**
     * Same as verify() but returns false instead of throwing.
     *
     * @param array<string, string|list<string>> $headers
     */
    public function isValid(string $body, array $headers): bool
    {
        try {
            $this->verify($body, $headers);

            return true;
        } catch (WebhookVerificationException) {
            return false;
        }
    }

    /**
     * @throws WebhookVerificationException When the webhook cannot be authenticated
     */
    public function verifySignature(
        string $body,
        string $requestId,
        string $userId,
        string $timestamp,
        string $signature,
    ): void {
        if (! extension_loaded('sodium')) {
            throw new \RuntimeException('The sodium extension is required to verify fal.ai webhook signatures.');
        }

        if (! is_numeric($timestamp) || abs(time() - (int) $timestamp) > $this->timestampTolerance) {
            throw new WebhookVerificationException(
                "Webhook timestamp is invalid or outside the allowed {$this->timestampTolerance}s tolerance"
            );
        }

        if ($signature === '' || strlen($signature) % 2 !== 0 || ! ctype_xdigit($signature)) {
            throw new WebhookVerificationException('Webhook signature is not valid hexadecimal');
        }

        $signatureBytes = hex2bin($signature);

        if (strlen($signatureBytes) !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw new WebhookVerificationException('Webhook signature has an unexpected length');
        }

        $message = implode("\n", [$requestId, $userId, $timestamp, hash('sha256', $body)]);

        foreach ($this->publicKeys() as $publicKey) {
            if (sodium_crypto_sign_verify_detached($signatureBytes, $message, $publicKey)) {
                return;
            }
        }

        throw new WebhookVerificationException('Webhook signature does not match any fal.ai public key');
    }

    /**
     * @return list<string>
     */
    private function publicKeys(): array
    {
        if ($this->publicKeys !== null) {
            return $this->publicKeys;
        }

        try {
            $response = $this->client->get($this->jwksUrl);
            $jwks = json_decode((string) $response->getBody(), true);
        } catch (\Throwable $exception) {
            throw new WebhookVerificationException(
                'Unable to fetch the fal.ai JWKS: ' . $exception->getMessage(),
                previous: $exception,
            );
        }

        $keys = [];

        foreach ($jwks['keys'] ?? [] as $key) {
            if (! is_array($key) || ! isset($key['x'])) {
                continue;
            }

            try {
                $decoded = sodium_base642bin(rtrim($key['x'], '='), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
            } catch (SodiumException) {
                continue;
            }

            if (strlen($decoded) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
                $keys[] = $decoded;
            }
        }

        if ($keys === []) {
            throw new WebhookVerificationException('No ED25519 public key found in the fal.ai JWKS');
        }

        return $this->publicKeys = $keys;
    }

    /**
     * @param array<string, string|list<string>> $headers
     */
    private function header(array $headers, string $name): string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                $value = is_array($value) ? ($value[0] ?? '') : $value;

                if ($value !== '') {
                    return (string) $value;
                }
            }
        }

        throw new WebhookVerificationException("Missing webhook header: {$name}");
    }
}
