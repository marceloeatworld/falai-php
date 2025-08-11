<?php

namespace MarceloEatWorld\FalAI\Requests;

use HosmelQ\SSE\Saloon\Traits\HasServerSentEvents;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class StreamRequest extends Request implements HasBody
{
    use HasJsonBody;
    use HasServerSentEvents {
        defaultHeaders as defaultSSEHeaders;
    }

    protected Method $method = Method::POST;

    public function __construct(
        protected string $model,
        protected array $input,
        protected ?string $webhookUrl = null,
    ) {
    }

    public function resolveEndpoint(): string
    {
        // For workflows, use the full path
        if (str_starts_with($this->model, 'workflows/')) {
            return $this->model;
        }
        
        // For regular models, extract first two segments
        $segments = explode('/', $this->model);
        return implode('/', array_slice($segments, 0, 2));
    }

    protected function defaultBody(): array
    {
        $body = $this->input;

        if ($this->webhookUrl) {
            $body['webhook_url'] = $this->webhookUrl;
        }

        // Enable streaming
        $body['stream'] = true;

        return $body;
    }

    protected function defaultHeaders(): array
    {
        return array_merge($this->defaultSSEHeaders(), [
            'Content-Type' => 'application/json',
        ]);
    }
}