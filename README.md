# fal.ai PHP Client

Professional PHP client for the [fal.ai](https://fal.ai) serverless AI platform, built on [Saloon v4](https://docs.saloon.dev).

## Requirements

- PHP 8.2+

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
], pollInterval: 500, timeout: 300, onStatus: function (QueueStatus $status) {
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

## File Upload

Upload local files to fal.ai storage for use with image-to-image models.

```php
$url = $fal->storage->upload('/path/to/image.png');

$result = $fal->run('fal-ai/imageutils/rembg', [
    'image_url' => $url,
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

The client throws Saloon exceptions on HTTP errors (4xx/5xx). Queue subscribe also throws on job failures and timeouts.

```php
use Saloon\Exceptions\Request\RequestException;

try {
    $result = $fal->run('fal-ai/flux/schnell', ['prompt' => 'test']);
} catch (RequestException $e) {
    echo $e->getResponse()->status();
    echo $e->getResponse()->body();
}

try {
    $result = $fal->queue->subscribe('fal-ai/flux/schnell', ['prompt' => 'test']);
} catch (\RuntimeException $e) {
    echo $e->getMessage(); // timeout or job failure
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
  Resources/
    QueueResource.php                    # submit, status, result, cancel, subscribe
    StorageResource.php                  # upload
  Requests/
    Queue/SubmitRequest.php
    Queue/StatusRequest.php
    Queue/ResultRequest.php
    Queue/CancelRequest.php
    Sync/RunRequest.php
    Storage/InitiateUploadRequest.php
  Data/
    QueuedJob.php                        # Submit response DTO
    QueueStatus.php                      # Status check DTO
  Enums/
    Status.php                           # IN_QUEUE, IN_PROGRESS, COMPLETED
    Priority.php                         # normal, low
```

## License

MIT

## Credits

- Built with [Saloon v4](https://github.com/saloonphp/saloon)
- [fal.ai API Documentation](https://docs.fal.ai)
