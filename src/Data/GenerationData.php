<?php

namespace MarceloEatWorld\FalAI\Data;

use Exception;
use MarceloEatWorld\FalAI\Enums\RequestStatus;
use Saloon\Http\Response;

final class GenerationData
{
    public function __construct(
        public ?string $requestId,
        public ?string $responseUrl,
        public ?string $statusUrl,
        public ?string $cancelUrl,
        public ?RequestStatus $status,
        public ?array $payload,
        public ?string $error,
        public ?int $queuePosition = null,
        public ?array $logs = null,
        public ?array $metrics = null,
        public ?string $gatewayRequestId = null,
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

            // Check if response is HTML (error page) rather than JSON
            if (str_starts_with(trim($rawBody), '<') || str_contains($rawBody, '<!DOCTYPE')) {
                // Extract error message from HTML if possible
                if (preg_match('/<title>(\d+):\s*([^<]+)<\/title>/i', $rawBody, $matches)) {
                    throw new Exception("HTTP {$matches[1]}: {$matches[2]}");
                } else if (preg_match('/(\d{3}):\s*([^\n]+)/', $rawBody, $matches)) {
                    throw new Exception("HTTP {$matches[1]}: {$matches[2]}");
                }
                throw new Exception("Invalid response format (HTML received instead of JSON)");
            }

            // On tente de parser le JSON avec un contrôle d'erreur
            $data = json_decode($rawBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Try to extract error from raw body if it looks like an error message
                if (preg_match('/^(\d{3}):\s*(.+)$/s', trim($rawBody), $matches)) {
                    throw new Exception("HTTP {$matches[1]}: {$matches[2]}");
                }
                throw new Exception("JSON decode error: " . json_last_error_msg());
            }

            // Si la réponse contient une erreur directe
            if (isset($data['error'])) {
                return new self(
                    requestId: $data['request_id'] ?? null,
                    responseUrl: $data['response_url'] ?? null,
                    statusUrl: $data['status_url'] ?? null,
                    cancelUrl: $data['cancel_url'] ?? null,
                    status: RequestStatus::ERROR,
                    payload: $data['payload'] ?? null,
                    error: $data['error'],
                    gatewayRequestId: $data['gateway_request_id'] ?? null
                );
            }

            // Pour les réponses de statut
            if (isset($data['status'])) {
                $status = RequestStatus::tryFrom($data['status']) ?? RequestStatus::ERROR;
                
                return new self(
                    requestId: $data['request_id'] ?? null,
                    responseUrl: $data['response_url'] ?? null,
                    statusUrl: $data['status_url'] ?? null,
                    cancelUrl: $data['cancel_url'] ?? null,
                    status: $status,
                    payload: $data['response'] ?? $data['output'] ?? null,
                    error: null,
                    queuePosition: $data['queue_position'] ?? null,
                    logs: $data['logs'] ?? null,
                    metrics: $data['metrics'] ?? null
                );
            }

            // Si on a un request_id, c'est probablement une réponse de création
            if (isset($data['request_id'])) {
                $status = isset($data['status']) 
                    ? (RequestStatus::tryFrom($data['status']) ?? null)
                    : null;
                    
                return new self(
                    requestId: $data['request_id'],
                    responseUrl: $data['response_url'] ?? null,
                    statusUrl: $data['status_url'] ?? null,
                    cancelUrl: $data['cancel_url'] ?? null,
                    status: $status,
                    payload: null,
                    error: null,
                    gatewayRequestId: $data['gateway_request_id'] ?? null
                );
            }

            // Si on arrive ici, c'est probablement une réponse de résultat direct
            return new self(
                requestId: null,
                responseUrl: null,
                statusUrl: null,
                cancelUrl: null,
                status: RequestStatus::COMPLETED,
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
                status: RequestStatus::ERROR,
                payload: null,
                error: $e->getMessage()
            );
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'requestId' => $this->requestId,
            'responseUrl' => $this->responseUrl,
            'statusUrl' => $this->statusUrl,
            'cancelUrl' => $this->cancelUrl,
            'status' => $this->status?->value,
            'payload' => $this->payload,
            'error' => $this->error,
            'queuePosition' => $this->queuePosition,
            'logs' => $this->logs,
            'metrics' => $this->metrics,
            'gatewayRequestId' => $this->gatewayRequestId,
        ], fn($value) => $value !== null);
    }

    /**
     * Check if the request is still processing
     */
    public function isProcessing(): bool
    {
        return $this->status?->isProcessing() ?? false;
    }

    /**
     * Check if the request has finished
     */
    public function isFinished(): bool
    {
        return $this->status?->isFinished() ?? false;
    }

    /**
     * Check if the request completed successfully
     */
    public function isSuccess(): bool
    {
        return $this->status?->isSuccess() ?? false;
    }

    /**
     * Check if the request has an error
     */
    public function hasError(): bool
    {
        return $this->status?->hasError() ?? !empty($this->error);
    }
}