# fal.ai PHP Client

#1 PHP client for the [fal.ai](https://fal.ai) serverless AI platform, compatible with Laravel and native PHP, built on [Saloon v4](https://docs.saloon.dev).

## Requirements

- PHP 8.2+
- ext-sodium (optional, only for webhook signature verification)

## Installation

```bash
composer require marceloeatworld/falai-php
```

## Quick Start

```php
use MarceloEatWorld\FalAI\FalAI;

$fal = new FalAI('your-api-key');

// Synchronous execution
$result = $fal->run('fal-ai/flux/schnell', [
    'prompt' => 'a sunset over mountains',
    'image_size' => 'landscape_16_9',
]);

$images = $result->json('images');
```

## Queue (Async Workflow)

For long-running models, use the queue to submit jobs and retrieve results later.

```php
// Submit a job
$job = $fal->queue->submit('fal-ai/flux/schnell', [
    'prompt' => 'a sunset over mountains',
]);

echo $job->requestId;

// Check status
$status = $fal->queue->status('fal-ai/flux/schnell', $job->requestId);

echo $status->status->value;       // IN_QUEUE, IN_PROGRESS, COMPLETED
echo $status->queuePosition;       // position in queue (if queued)

// Get result when completed
$result = $fal->queue->result('fal-ai/flux/schnell', $job->requestId);
$images = $result->json('images');

// Cancel a job
$fal->queue->cancel('fal-ai/flux/schnell', $job->requestId);
```

## Subscribe (Submit + Auto-Poll)

Submit a job and automatically poll until it completes.

```php
use MarceloEatWorld\FalAI\Data\QueueStatus;

$result = $fal->queue->subscribe('fal-ai/flux/schnell', [
    'prompt' => 'a sunset over mountains',
], pollInterval: 500, timeout: 300, requestTimeout: 120, onStatus: function (QueueStatus $status) {
    echo "Status: {$status->status->value}\n";
    foreach ($status->logs as $log) {
        echo "  {$log['message']}\n";
    }
});

$images = $result->json('images');
```

## Webhooks

Receive results via webhook instead of polling.

```php
$job = $fal->queue->submit('fal-ai/flux/schnell', [
    'prompt' => 'a sunset over mountains',
], webhook: 'https://your.app/webhook');
```

### Verifying Webhook Signatures

fal.ai signs every webhook with ED25519 (`X-Fal-Webhook-*` headers). Verify before trusting the payload:

```php
// Laravel controller
public function webhook(Request $request, FalAI $fal)
{
    if (! $fal->webhooks()->isValid($request->getContent(), $request->headers->all())) {
        abort(401);
    }

    $payload = $request->json()->all();
    // $payload['status'] is "OK" or "ERROR", $payload['payload'] holds the result
}
```

```php
// Native PHP
use MarceloEatWorld\FalAI\Webhooks\WebhookVerifier;
use MarceloEatWorld\FalAI\Exceptions\WebhookVerificationException;

$verifier = new WebhookVerifier();

try {
    $verifier->verify(file_get_contents('php://input'), getallheaders());
} catch (WebhookVerificationException $e) {
    http_response_code(401);
    exit;
}
```

Public keys are fetched from the fal.ai JWKS endpoint and cached per instance, so reuse the verifier (singleton) across requests. `verify()` throws with a reason, `isValid()` returns a boolean. Timestamps older than 5 minutes are rejected.

## File Upload

Upload local files to fal.ai storage for use with image-to-image models.

```php
$url = $fal->storage->upload('/path/to/image.png', 'image/png');

$result = $fal->run('fal-ai/imageutils/rembg', [
    'image_url' => $url,
]);
```

In-memory content works too:

```php
$url = $fal->storage->uploadData($binaryData, 'image.png', 'image/png');
```

## Model Catalog

Search the fal.ai model catalog (no API key required for this endpoint, but one is always sent).

```php
use MarceloEatWorld\FalAI\Enums\ModelStatus;

// Search with filters, cursor-based pagination
$page = $fal->models->list(query: 'flux', category: 'text-to-image', status: ModelStatus::Active, limit: 20);

foreach ($page->models as $model) {
    echo "{$model->endpointId}: {$model->displayName} ({$model->category})\n";
}

if ($page->hasMore) {
    $next = $fal->models->list(query: 'flux', cursor: $page->nextCursor);
}

// Single model (null when unknown)
$model = $fal->models->get('fal-ai/flux/dev');
echo $model->description;
echo $model->licenseType;      // commercial, research, ...
print_r($model->metadata);     // full raw metadata

// Include the model's OpenAPI schema (input/output parameters)
$model = $fal->models->get('fal-ai/flux/dev', expand: ['openapi-3.0']);
print_r($model->openapi);
```

## Pricing

Fetch unit prices and estimate costs (API key required).

```php
// Unit prices, keyed by endpoint id
$prices = $fal->models->pricing('fal-ai/flux/dev', 'fal-ai/flux/schnell');

echo $prices['fal-ai/flux/dev']->unitPrice;   // 0.025
echo $prices['fal-ai/flux/dev']->unit;        // "image"
echo $prices['fal-ai/flux/dev']->currency;    // "USD"

// Estimate from expected API calls (based on your historical usage)
$estimate = $fal->models->estimateByCalls([
    'fal-ai/flux/dev' => 100,
    'fal-ai/flux/schnell' => 500,
]);
echo $estimate->totalCost;                     // 5.75
echo $estimate->currency;                      // "USD"

// Estimate from billing units (images, videos, seconds, ...)
$estimate = $fal->models->estimateByUnits([
    'fal-ai/flux/dev' => 250,
]);
```

## Queue Options

Fine-tune queue behavior with named parameters.

```php
use MarceloEatWorld\FalAI\Enums\Priority;

$job = $fal->queue->submit('fal-ai/flux/schnell', [
    'prompt' => 'test',
],
    webhook: 'https://your.app/webhook',
    timeout: 300,
    priority: Priority::Normal,
    runnerHint: 'session-abc',
    noRetry: true,
);
```

## Custom Base URLs

Override default endpoints if needed.

```php
$fal = new FalAI(
    apiKey: 'your-api-key',
    queueBaseUrl: 'https://queue.fal.run',
    syncBaseUrl: 'https://fal.run',
    storageBaseUrl: 'https://rest.alpha.fal.ai',
    platformBaseUrl: 'https://api.fal.ai',
);
```

## Laravel Integration

Add to `config/services.php`:

```php
'falai' => [
    'api_key' => env('FAL_KEY'),
],
```

Register in a service provider:

```php
$this->app->singleton(\MarceloEatWorld\FalAI\FalAI::class, function () {
    return new \MarceloEatWorld\FalAI\FalAI(config('services.falai.api_key'));
});
```

Use via injection:

```php
use MarceloEatWorld\FalAI\FalAI;

public function generate(FalAI $fal)
{
    $result = $fal->queue->subscribe('fal-ai/flux/schnell', [
        'prompt' => 'A mountain landscape',
    ]);

    return $result->json('images');
}
```

## Error Handling

The client throws Saloon exceptions on HTTP errors (4xx/5xx). Queue subscribe throws dedicated exceptions (both extend `\RuntimeException`) on job failures and timeouts.

```php
use MarceloEatWorld\FalAI\Exceptions\QueueFailedException;
use MarceloEatWorld\FalAI\Exceptions\QueueTimeoutException;
use Saloon\Exceptions\Request\RequestException;

try {
    $result = $fal->run('fal-ai/flux/schnell', ['prompt' => 'test']);
} catch (RequestException $e) {
    echo $e->getResponse()->status();
    echo $e->getResponse()->body();
}

try {
    $result = $fal->queue->subscribe('fal-ai/flux/schnell', ['prompt' => 'test']);
} catch (QueueTimeoutException $e) {
    echo "Timed out: {$e->requestId}"; // the job may still complete server-side
} catch (QueueFailedException $e) {
    echo "Failed: {$e->getMessage()}";
}
```

## Architecture

```
src/
  FalAI.php                              # Entry point
  Auth/FalKeyAuthenticator.php           # Authorization: Key {token}
  Connectors/
    FalConnector.php                     # Abstract base (auth, headers, timeouts)
    QueueConnector.php                   # queue.fal.run
    SyncConnector.php                    # fal.run
    StorageConnector.php                 # rest.alpha.fal.ai
    PlatformConnector.php                # api.fal.ai
  Resources/
    QueueResource.php                    # submit, status, result, cancel, subscribe
    StorageResource.php                  # upload, uploadData
    ModelsResource.php                   # list, get, pricing, estimateByCalls, estimateByUnits
  Requests/
    Queue/SubmitRequest.php
    Queue/StatusRequest.php
    Queue/ResultRequest.php
    Queue/CancelRequest.php
    Sync/RunRequest.php
    Storage/InitiateUploadRequest.php
    Models/ListModelsRequest.php
    Models/PricingRequest.php
    Models/EstimateCostRequest.php
  Data/
    QueuedJob.php                        # Submit response DTO
    QueueStatus.php                      # Status check DTO
    Model.php                            # Catalog entry DTO
    ModelsPage.php                       # Paginated catalog results
    ModelPrice.php                       # Unit price DTO
    CostEstimate.php                     # Cost estimate DTO
  Enums/
    Status.php                           # InQueue, InProgress, Completed
    Priority.php                         # Normal, Low
    ModelStatus.php                      # Active, Deprecated
  Webhooks/
    WebhookVerifier.php                  # ED25519 signature verification (JWKS)
  Exceptions/
    QueueTimeoutException.php
    QueueFailedException.php
    WebhookVerificationException.php
  Support/
    QueryString.php                      # Repeated query keys for api.fal.ai
```

## License

MIT

## Credits

- Built with [Saloon v4](https://github.com/saloonphp/saloon)
- [fal.ai API Documentation](https://docs.fal.ai)
