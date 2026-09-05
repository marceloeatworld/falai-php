<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Resources;

use MarceloEatWorld\FalAI\Data\CostEstimate;
use MarceloEatWorld\FalAI\Data\Model;
use MarceloEatWorld\FalAI\Data\ModelPrice;
use MarceloEatWorld\FalAI\Data\ModelsPage;
use MarceloEatWorld\FalAI\Enums\ModelStatus;
use MarceloEatWorld\FalAI\Requests\Models\EstimateCostRequest;
use MarceloEatWorld\FalAI\Requests\Models\ListModelsRequest;
use MarceloEatWorld\FalAI\Requests\Models\PricingRequest;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\BaseResource;

final class ModelsResource extends BaseResource
{
    /**
     * Search the model catalog. All filters are optional; paginate with
     * the cursor from the previous page.
     *
     * @param list<string> $endpointIds Filter to specific models (max 50)
     * @param list<string> $expand Extra fields: "openapi-3.0", "enterprise_status"
     */
    public function list(
        ?string $query = null,
        ?string $category = null,
        ?ModelStatus $status = null,
        ?int $limit = null,
        ?string $cursor = null,
        array $endpointIds = [],
        array $expand = [],
    ): ModelsPage {
        $response = $this->connector->send(new ListModelsRequest(
            search: $query,
            category: $category,
            status: $status,
            limit: $limit,
            cursor: $cursor,
            endpointIds: $endpointIds,
            expand: $expand,
        ));

        return ModelsPage::fromResponse($response);
    }

    /**
     * Fetch a single model by endpoint id, or null when unknown.
     *
     * @param list<string> $expand Extra fields: "openapi-3.0", "enterprise_status"
     */
    public function get(string $endpointId, array $expand = []): ?Model
    {
        try {
            $page = $this->list(endpointIds: [$endpointId], expand: $expand);
        } catch (RequestException $exception) {
            // The API answers 404 for unknown endpoint ids.
            if ($exception->getResponse()->status() === 404) {
                return null;
            }

            throw $exception;
        }

        $model = $page->models[0] ?? null;

        // Older API versions echoed unknown ids back without metadata.
        if ($model === null || $model->metadata === []) {
            return null;
        }

        return $model;
    }

    /**
     * Unit prices for up to 50 endpoints, keyed by endpoint id.
     *
     * @return array<string, ModelPrice>
     */
    public function pricing(string ...$endpointIds): array
    {
        $response = $this->connector->send(new PricingRequest(array_values($endpointIds)));

        $prices = [];

        foreach ($response->json('prices') ?? [] as $price) {
            $price = ModelPrice::fromArray($price);
            $prices[$price->endpointId] = $price;
        }

        return $prices;
    }

    /**
     * Estimate cost from expected API call counts, based on historical prices.
     *
     * @param array<string, int> $calls Endpoint id => number of calls
     */
    public function estimateByCalls(array $calls): CostEstimate
    {
        $endpoints = array_map(
            fn (int $quantity): array => ['call_quantity' => $quantity],
            $calls,
        );

        return CostEstimate::fromResponse(
            $this->connector->send(new EstimateCostRequest('historical_api_price', $endpoints))
        );
    }

    /**
     * Estimate cost from expected billing units (images, videos, ...).
     *
     * @param array<string, float|int> $units Endpoint id => number of units
     */
    public function estimateByUnits(array $units): CostEstimate
    {
        $endpoints = array_map(
            fn (float|int $quantity): array => ['unit_quantity' => $quantity],
            $units,
        );

        return CostEstimate::fromResponse(
            $this->connector->send(new EstimateCostRequest('unit_price', $endpoints))
        );
    }
}
