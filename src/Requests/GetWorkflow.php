<?php
namespace MarceloEatWorld\FalAI\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class GetWorkflow extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $workflowId,
        protected array $input,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "workflows/{$this->workflowId}";
    }

    protected function defaultBody(): array
    {
        return $this->input;
    }
}