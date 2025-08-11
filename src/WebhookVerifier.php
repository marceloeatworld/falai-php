<?php

namespace MarceloEatWorld\FalAI;

use MarceloEatWorld\FalAI\Exceptions\WebhookVerificationException;
use Psr\SimpleCache\CacheInterface;

class WebhookVerifier
{
    private const PUBLIC_KEY_URL = 'https://fal.run/webhook/keys';
    private const SIGNATURE_TOLERANCE_SECONDS = 300; // 5 minutes

    public function __construct(
        private ?CacheInterface $cache = null
    ) {}

    /**
     * Verify webhook signature from fal.ai
     * 
     * @param string $body Raw webhook body
     * @param array $headers HTTP headers
     * @return bool True if signature is valid
     * @throws WebhookVerificationException
     */
    public function verify(string $body, array $headers): bool
    {
        // Extract signature and timestamp from headers
        $signature = $this->extractHeader($headers, 'x-fal-signature');
        $timestamp = $this->extractHeader($headers, 'x-fal-timestamp');

        if (!$signature || !$timestamp) {
            throw new WebhookVerificationException('Missing required webhook headers');
        }

        // Check timestamp tolerance
        $currentTime = time();
        $webhookTime = (int) $timestamp;
        
        if (abs($currentTime - $webhookTime) > self::SIGNATURE_TOLERANCE_SECONDS) {
            throw new WebhookVerificationException('Webhook timestamp is outside tolerance window');
        }

        // Get public key
        $publicKey = $this->getPublicKey();
        if (!$publicKey) {
            throw new WebhookVerificationException('Could not retrieve public key');
        }

        // Create signed payload
        $signedPayload = $timestamp . '.' . $body;
        
        // Verify signature
        $isValid = sodium_crypto_sign_verify_detached(
            base64_decode($signature),
            $signedPayload,
            base64_decode($publicKey)
        );

        return $isValid;
    }

    /**
     * Extract header value case-insensitively
     */
    private function extractHeader(array $headers, string $name): ?string
    {
        $name = strtolower($name);
        
        foreach ($headers as $key => $value) {
            if (strtolower($key) === $name) {
                return is_array($value) ? $value[0] : $value;
            }
        }
        
        return null;
    }

    /**
     * Get public key with caching support
     */
    private function getPublicKey(): ?string
    {
        $cacheKey = 'fal_webhook_public_key';
        
        // Try cache first
        if ($this->cache) {
            $cached = $this->cache->get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        // Fetch from API
        $response = file_get_contents(self::PUBLIC_KEY_URL);
        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);
        $publicKey = $data['public_key'] ?? null;

        // Cache for 1 hour
        if ($publicKey && $this->cache) {
            $this->cache->set($cacheKey, $publicKey, 3600);
        }

        return $publicKey;
    }
}