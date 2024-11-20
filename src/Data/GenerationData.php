<?php

namespace MarceloEatWorld\FalAI\Data;

use Exception;
use Saloon\Http\Response;

final class GenerationData
{
    public function __construct(
        public ?string $requestId,
        public ?string $responseUrl,
        public ?string $statusUrl,
        public ?string $cancelUrl,
        public ?string $status,
        public ?array $payload,
        public ?string $error,
    ) {
    }

    public static function fromResponse(Response $response): self
    {
        try {
            // On essaie d'abord de décoder la réponse brute
            $rawBody = $response->body();
            if (empty($rawBody)) {
                throw new Exception("Empty response body");
            }

            // On tente de parser le JSON avec un contrôle d'erreur
            $data = json_decode($rawBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON decode error: " . json_last_error_msg());
            }

            // Si la réponse contient une erreur directe
            if (isset($data['error'])) {
                return new self(
                    requestId: $data['request_id'] ?? null,
                    responseUrl: $data['response_url'] ?? null,
                    statusUrl: $data['status_url'] ?? null,
                    cancelUrl: $data['cancel_url'] ?? null,
                    status: 'ERROR',
                    payload: null,
                    error: $data['error']
                );
            }

            // Pour les réponses de statut
            if (isset($data['status'])) {
                return new self(
                    requestId: $data['request_id'] ?? null,
                    responseUrl: $data['response_url'] ?? null,
                    statusUrl: $data['status_url'] ?? null,
                    cancelUrl: $data['cancel_url'] ?? null,
                    status: $data['status'],
                    payload: $data['response'] ?? null,
                    error: null
                );
            }

            // Si on a un request_id, c'est probablement une réponse de création
            if (isset($data['request_id'])) {
                return new self(
                    requestId: $data['request_id'],
                    responseUrl: $data['response_url'] ?? null,
                    statusUrl: $data['status_url'] ?? null,
                    cancelUrl: $data['cancel_url'] ?? null,
                    status: isset($data['status']) ? $data['status'] : null,
                    payload: null,
                    error: null
                );
            }

            // Si on arrive ici, c'est probablement une réponse de résultat direct
            return new self(
                requestId: null,
                responseUrl: null,
                statusUrl: null,
                cancelUrl: null,
                status: 'COMPLETED',
                payload: $data,
                error: null
            );

        } catch (Exception $e) {
            // Log pour le débogage si nécessaire
            error_log("Error parsing response: " . $e->getMessage());
            error_log("Raw response body: " . $response->body());
            
            // On retourne un objet avec l'erreur
            return new self(
                requestId: null,
                responseUrl: null,
                statusUrl: null,
                cancelUrl: null,
                status: 'ERROR',
                payload: null,
                error: $e->getMessage()
            );
        }
    }

    public function toArray(): array
    {
        return [
            'requestId' => $this->requestId,
            'responseUrl' => $this->responseUrl,
            'statusUrl' => $this->statusUrl,
            'cancelUrl' => $this->cancelUrl,
            'status' => $this->status,
            'payload' => $this->payload,
            'error' => $this->error,
        ];
    }
}