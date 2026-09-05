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

## Streaming

Models that expose a `/stream` endpoint (check the model page, or `stream_url` in its catalog metadata) push partial results as server-sent events. `stream()` yields each event as an array; the last event is the final result and is also returned by the generator.

```php
$stream = $fal->stream('fal-ai/flux/dev', [
    'prompt' => 'a sunset over mountains',
]);

foreach ($stream as $event) {
    // Event shape depends on the model. flux/dev sends intermediate previews as
    // data: URIs in $event['images'][0]['url'], then the final CDN URL.
    echo count($event['images']), " image(s) so far\n";
}

$final = $stream->getReturn();    // same payload as run(): images, seed, timings, ...
$url = $final['images'][0]['url'];
```

Models without a `/stream` endpoint answer 404 (`Path /stream not found`). `fal-ai/flux/schnell`, for example, has none; `fal-ai/flux/dev` does.

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

// Cancel a job (202 CANCELLATION_REQUESTED; throws on 400 ALREADY_COMPLETED or 404 NOT_FOUND)
$fal->queue->cancel('fal-ai/flux/schnell', $job->requestId);
```

Status, result and cancel are addressed by application id (`fal-ai/flux`), not by the full endpoint id (`fal-ai/flux/schnell`). The client derives it automatically, so pass the same model id you submitted with. The URLs returned by `submit()` (`statusUrl`, `responseUrl`, `cancelUrl`) are also exposed on the `QueuedJob` DTO.

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

### Status Streaming

Instead of polling, the queue can push status updates as server-sent events until the request completes. The last yielded status is the `COMPLETED` one.

```php
$job = $fal->queue->submit('fal-ai/flux/schnell', ['prompt' => 'a sunset over mountains']);

foreach ($fal->queue->streamStatus('fal-ai/flux/schnell', $job->requestId) as $status) {
    echo "{$status->status->value}\n";
}

$result = $fal->queue->result('fal-ai/flux/schnell', $job->requestId);
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

Public keys are fetched from the fal.ai JWKS endpoint (`https://rest.fal.ai/.well-known/jwks.json`) and cached per instance for 24 hours, so reuse the verifier (singleton) across requests. If a signature matches none of the cached keys, the JWKS is fetched again once to pick up rotated keys. `verify()` throws with a reason, `isValid()` returns a boolean. Timestamps more than 5 minutes away from the server clock are rejected.

Webhook payloads carry `status` (`"OK"` or `"ERROR"`), `request_id`, `gateway_request_id` and `payload`. On error an `error` field is added; if the output could not be serialized, `payload` is `null` and `payload_error` explains why. fal.ai retries deliveries that do not answer with a 2xx (redirects count as failures), so keep handlers idempotent.

## File Upload

Upload local files to the fal.ai CDN for use with image-to-image models. Files above 90 MB are uploaded in 10 MB parts (multipart flow of the official clients, each part retried up to 3 times); smaller files use a single PUT.

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

Both methods accept a `lifecycle` to control expiration and access (see below):

```php
use MarceloEatWorld\FalAI\Data\ObjectLifecycle;

$url = $fal->storage->upload('/path/to/input.png', lifecycle: ObjectLifecycle::expiresIn(3600));
```

## File Expiration and Access Control

By default generated and uploaded files stay on the CDN forever and are publicly readable (unless your account settings say otherwise). Pass an `ObjectLifecycle` to `run()`, `stream()`, `queue->submit()`, `queue->subscribe()`, `storage->upload()` or `storage->uploadData()` to change that per request. It is sent as the `X-Fal-Object-Lifecycle-Preference` header.

```php
use MarceloEatWorld\FalAI\Data\ObjectLifecycle;

// Delete generated files after one hour
$result = $fal->run('fal-ai/flux/schnell', ['prompt' => 'a sunset'], lifecycle: ObjectLifecycle::expiresIn(3600));

// Private: only the owner (and the listed fal.ai nicknames) can read the files
$result = $fal->run('fal-ai/flux/schnell', ['prompt' => 'a sunset'], lifecycle: ObjectLifecycle::private(['alice']));

// Full control over the initial ACL: "default" is "allow", "forbid" (403) or "hide" (404)
$lifecycle = new ObjectLifecycle(
    expirationSeconds: 86400,
    initialAcl: ['default' => 'forbid', 'rules' => [['user' => 'alice', 'decision' => 'allow']]],
);
```

Private files answer 403 to anonymous requests. To read them, exchange your API key for a CDN token (`POST https://rest.fal.ai/storage/auth/token?storage_type=fal-cdn-v3`) and send it as `Authorization: Bearer <token>`, or create a signed URL from the fal.ai dashboard or Platform API.

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
echo $prices['fal-ai/flux/dev']->unit;        // "megapixels", "image", "video", "second", ...
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
use MarceloEatWorld\FalAI\Data\ObjectLifecycle;
use MarceloEatWorld\FalAI\Enums\Priority;

$job = $fal->queue->submit('fal-ai/flux/schnell', [
    'prompt' => 'test',
],
    webhook: 'https://your.app/webhook',     // ?fal_webhook=
    timeout: 300,                            // X-Fal-Request-Timeout (seconds before processing must start, else 504)
    priority: Priority::Low,                 // X-Fal-Queue-Priority: normal (default) or low
    runnerHint: 'session-abc',               // X-Fal-Runner-Hint: route to the same runner (session affinity)
    noRetry: true,                           // X-Fal-No-Retry: disable the automatic retries
    lifecycle: ObjectLifecycle::expiresIn(3600), // X-Fal-Object-Lifecycle-Preference
    storeIo: false,                          // X-Fal-Store-IO: 0, do not keep request payloads for 30 days
    maxQueueLength: 100,                     // ?fal_max_queue_length=: reject with 429 if more requests are already waiting
);
```

`subscribe()` accepts the same options (`timeout` there is the client-side polling limit, use `requestTimeout` for the server-side deadline). `run()` and `stream()` accept `timeout`, `noRetry`, `lifecycle` and `storeIo`.

Useful response headers on `run()` and `result()`: `x-fal-request-id`, `x-fal-billable-units`, and `x-fal-error-type` on failures.

## Custom Base URLs

Override default endpoints if needed.

```php
$fal = new FalAI(
    apiKey: 'your-api-key',
    queueBaseUrl: 'https://queue.fal.run',
    syncBaseUrl: 'https://fal.run',
    storageBaseUrl: 'https://rest.fal.ai',
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

The client throws Saloon exceptions on HTTP errors (4xx/5xx). Queue subscribe throws dedicated exceptions (both extend `\RuntimeException`) on timeouts and on jobs whose status reports an `error` (for example `client_cancelled`).

Input validation errors are not reported in the queue status: the job completes and the result endpoint answers 422 with a `detail` array, so `result()` and `subscribe()` throw a Saloon `RequestException` in that case. Catch both.

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
} catch (RequestException $e) {
    echo $e->getResponse()->json('detail.0.msg'); // validation error from the model
}
```

Status responses expose `error` and `error_type` (`QueueStatus::$error`, `QueueStatus::$errorType`); request-level error types include `request_timeout`, `startup_timeout`, `client_cancelled`, `runner_server_error` and `internal_error`.

## Architecture

```
src/
  FalAI.php                              # Entry point: run, stream, webhooks
  Auth/FalKeyAuthenticator.php           # Authorization: Key {token}
  Connectors/
    FalConnector.php                     # Abstract base (auth, headers, timeouts)
    QueueConnector.php                   # queue.fal.run
    SyncConnector.php                    # fal.run
    StorageConnector.php                 # rest.fal.ai
    PlatformConnector.php                # api.fal.ai
  Resources/
    QueueResource.php                    # submit, status, streamStatus, result, cancel, subscribe
    StorageResource.php                  # upload, uploadData (single PUT or multipart)
    ModelsResource.php                   # list, get, pricing, estimateByCalls, estimateByUnits
  Requests/
    Queue/SubmitRequest.php
    Queue/StatusRequest.php
    Queue/StatusStreamRequest.php
    Queue/ResultRequest.php
    Queue/CancelRequest.php
    Sync/RunRequest.php
    Sync/StreamRequest.php
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
    ObjectLifecycle.php                  # File expiration and ACL preference
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
    EndpointId.php                       # Model id -> application id for queue URLs
    FalHeaders.php                       # X-Fal-* request headers
    ServerSentEvents.php                 # text/event-stream parser
```

## License

MIT

## Credits

- Built with [Saloon v4](https://github.com/saloonphp/saloon)
- [fal.ai API Documentation](https://docs.fal.ai)
