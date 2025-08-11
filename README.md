# FAL AI PHP Client

A lightweight PHP client for [FAL.AI](https://fal.ai) built with Saloon v3. Create AI-powered content with ease.

[![Join FAL.AI Discord](data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTcwIiBoZWlnaHQ9IjE3MSIgdmlld0JveD0iMCAwIDE3MCAxNzEiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0xMDkuNTcxIDAuNjkwMDQyQzExMi41MTUgMC42OTAwNDIgMTE0Ljg3NCAzLjA4MzQ4IDExNS4xNTUgNi4wMTM1MkMxMTcuNjY1IDMyLjE0OSAxMzguNDY2IDUyLjk0OCAxNjQuNjAzIDU1LjQ1OEMxNjcuNTM0IDU1LjczOTQgMTY5LjkyNyA1OC4wOTg1IDE2OS45MjcgNjEuMDQyVjExMC4yNTVDMTY5LjkyNyAxMTMuMTk4IDE2Ny41MzQgMTE1LjU1NyAxNjQuNjAzIDExNS44MzlDMTM4LjQ2NiAxMTguMzQ5IDExNy42NjUgMTM5LjE0OCAxMTUuMTU1IDE2NS4yODNDMTE0Ljg3NCAxNjguMjEzIDExMi41MTUgMTcwLjYwNyAxMDkuNTcxIDE3MC42MDdINTAuMzU1M0M1Ny40MTE2IDE3MC42MDcgNTUuMDUyNCAxNjguMjEzIDU0Ljc3MDkgMTY1LjI4M0M1Mi4yNjA4IDEzOS4xNDggMzEuNDYwMSAxMTguMzQ5IDUuMzIyODkgMTE1LjgzOUMyLjM5MjY2IDExNS41NTctMC4wMDA5NzY1NjIgMTEzLjE5OCAtMC4wMDA5NzY1NjIgMTEwLjI1NVY2MS4wNDJDLTAuMDAwOTc2NTYyIDU4LjA5ODUgMi4zOTI2NyA1NS43Mzk0IDUuMzIyOSA1NS40NThDMzEuNDYwMSA1Mi45NDggNTIuMjYwOCAzMi4xNDkgNTQuNzcwOSA2LjAxMzUxQzU1LjA1MjQgMy4wODM0OCA1Ny40MTE2IDAuNjkwMDQyIDYwLjM1NTMgMC42OTAwNDJIMTA5LjU3MVpNMzQuMTE4MiA4NS41MDQ1QzM0LjExODIgMTEzLjc3NiA1Ny4wMTI0IDEzNi42OTQgODUuMjUzOSAxMzYuNjk0QzExMy40OTUgMTM2LjY5NCAxMzYuMzkgMTEzLjc3NiAxMzYuMzkgODUuNTA0NUMxMzYuMzkgNTcuMjMzMiAxMTMuNDk1IDM0LjMxNDcgODUuMjUzOSAzNC4zMTQ3QzU3LjAxMjQgMzQuMzE0NyAzNC4xMTgyIDU3LjIzMzIgMzQuMTE4MiA4NS41MDQ1WiIgZmlsbD0iYmxhY2siLz48L3N2Zz4=)](https://discord.gg/fal-ai)

## Features

* 🎨 Support for all FAL AI models (Recraft, Flux Pro, etc.)
* 🔄 Full queue system with status tracking
* 📡 Webhook support for async notifications
* 🛠️ ComfyUI & Workflows support
* ⚡ Synchronous requests for fast operations
* 📊 Streaming status updates
* 📝 Request logs and metrics

## Installation

```bash
composer require marceloeatworld/falai-php
```

## Quick Start

```php
use MarceloEatWorld\FalAI\FalAI;

$falAI = new FalAI('your-api-key');

// Generate an image (queue-based)
$result = $falAI->generations()->create('fal-ai/recraft-v3', [
    'prompt' => 'A beautiful landscape',
    'negative_prompt' => 'low quality',
    'image_size' => 'square_hd',
    'seed' => 42
]);

// Check generation status
$status = $falAI->generations()->checkStatus('fal-ai/recraft-v3', $result->requestId);

// Get final result when completed
if ($status->isSuccess()) {
    $finalResult = $falAI->generations()->getResult('fal-ai/recraft-v3', $result->requestId);
    $images = $finalResult->payload['images'] ?? [];
}
```

## Queue API Usage

### Create and Track Generation

```php
// Submit to queue
$result = $falAI->generations()->create('fal-ai/flux-pro/v1.1-ultra', [
    'prompt' => 'A futuristic city at night',
    'image_size' => 'landscape_16_9',
    'num_images' => 2
]);

$requestId = $result->requestId;

// Check status with logs
$status = $falAI->generations()->checkStatus('fal-ai/flux-pro/v1.1-ultra', $requestId, true);

// Status types: IN_QUEUE, IN_PROGRESS, COMPLETED, ERROR
if ($status->status->value === 'IN_QUEUE') {
    echo "Queue position: " . $status->queuePosition;
}

if ($status->isProcessing()) {
    echo "Still processing...";
}

if ($status->isSuccess()) {
    $result = $falAI->generations()->getResult('fal-ai/flux-pro/v1.1-ultra', $requestId);
    // Access generated content
}

if ($status->hasError()) {
    echo "Error: " . $status->error;
}
```

### Stream Status Updates

```php
// Stream status updates until completion
$stream = $falAI->generations()->streamStatus('fal-ai/recraft-v3', $requestId, true);

foreach ($stream as $update) {
    echo "Status: " . $update['status'] . "\n";
    
    if (isset($update['logs'])) {
        foreach ($update['logs'] as $log) {
            echo $log['message'] . "\n";
        }
    }
    
    if ($update['status'] === 'COMPLETED') {
        // Access final result
        $images = $update['output']['images'] ?? [];
        break;
    }
}
```

### Cancel a Request

```php
// Cancel a queued request
$cancelResult = $falAI->generations()->cancel('fal-ai/recraft-v3', $requestId);

if ($cancelResult->status->value === 'CANCELLATION_REQUESTED') {
    echo "Cancellation requested";
}
```

## Synchronous Requests

For fast operations that complete quickly, use synchronous mode:

```php
// Get synchronous client
$syncClient = $falAI->synchronous();

// Execute immediately (no queue)
$result = $syncClient->generations()->run('fal-ai/fast-sdxl', [
    'prompt' => 'A cute cat',
    'image_size' => 'square'
]);

// Result is immediately available
$images = $result->payload['images'] ?? [];
```

## Webhook Support

Use webhooks for async notifications:

```php
// Set webhook URL
$result = $falAI->generations()
    ->withWebhook('https://your-site.com/webhook')
    ->create('fal-ai/recraft-v3', [
        'prompt' => 'A serene lake at sunset'
    ]);

// The webhook will receive:
// - request_id
// - gateway_request_id
// - status (OK or ERROR)
// - payload (the result)
```

## Models with Subpaths

Some models expose different capabilities at subpaths:

```php
// Using Flux Dev variant
$result = $falAI->generations()->create('fal-ai/flux/dev', [
    'prompt' => 'A magical forest',
    'image_size' => 'square_hd'
]);

// Status/result URLs automatically handle the subpath
$status = $falAI->generations()->checkStatus('fal-ai/flux/dev', $result->requestId);
```

## ComfyUI Workflows

```php
// Use custom ComfyUI workflows
$result = $falAI->generations()->create('comfy/youraccount/workflow-name', [
    'loadimage_1' => 'https://example.com/input.jpg',
    'prompt' => 'Transform to anime style',
    'steps' => 30
]);

// Track like any other generation
$status = $falAI->generations()->checkStatus('comfy/youraccount/workflow-name', $result->requestId);
```

## Response Structure

The `GenerationData` object provides:

```php
// Check status
if ($data->isProcessing()) { /* Still in queue or processing */ }
if ($data->isSuccess()) { /* Completed successfully */ }
if ($data->hasError()) { /* Has error */ }

// Access properties
$data->requestId;        // Unique request identifier
$data->status;           // RequestStatus enum
$data->payload;          // Result data when completed
$data->error;            // Error message if failed
$data->queuePosition;    // Position in queue
$data->logs;             // Processing logs (if requested)
$data->metrics;          // Performance metrics
```

## Status Types

```php
use MarceloEatWorld\FalAI\Enums\RequestStatus;

// Available statuses
RequestStatus::IN_QUEUE              // Waiting in queue
RequestStatus::IN_PROGRESS           // Currently processing
RequestStatus::COMPLETED             // Successfully completed
RequestStatus::ERROR                 // Failed with error
RequestStatus::CANCELLATION_REQUESTED // Cancellation requested
RequestStatus::ALREADY_COMPLETED     // Already completed (can't cancel)
```

## Laravel Integration

Add to `config/services.php`:

```php
'falai' => [
    'api_key' => env('FAL_API_KEY'),
],
```

Register in a service provider:

```php
use MarceloEatWorld\FalAI\FalAI;

public function register()
{
    $this->app->singleton(FalAI::class, function () {
        return new FalAI(config('services.falai.api_key'));
    });
}
```

Use in controllers:

```php
use MarceloEatWorld\FalAI\FalAI;

class ImageController extends Controller
{
    public function generate(Request $request, FalAI $falAI)
    {
        $result = $falAI->generations()->create('fal-ai/recraft-v3', [
            'prompt' => $request->input('prompt'),
            'image_size' => 'square_hd'
        ]);
        
        // Store request ID in session or database
        session(['generation_id' => $result->requestId]);
        
        return response()->json([
            'request_id' => $result->requestId,
            'status_url' => $result->statusUrl
        ]);
    }
    
    public function status(FalAI $falAI)
    {
        $requestId = session('generation_id');
        $status = $falAI->generations()->checkStatus('fal-ai/recraft-v3', $requestId, true);
        
        return response()->json($status->toArray());
    }
}
```

## Error Handling

```php
try {
    $result = $falAI->generations()->create('fal-ai/recraft-v3', [
        'prompt' => 'A beautiful scene'
    ]);
} catch (\Saloon\Exceptions\Request\RequestException $e) {
    // Handle API errors
    $response = $e->getResponse();
    $error = $response->json();
    
    if ($response->status() === 422) {
        // Validation error
        $details = $error['detail'] ?? [];
    }
}

// Check for errors in response
if ($result->hasError()) {
    echo "Generation failed: " . $result->error;
}
```

## Available Models

**✅ Confirmed Working Models (100% Tested):**
- `fal-ai/fast-sdxl` - Fast image generation (1-3s)
- `fal-ai/recraft-v3` - High quality illustrations (2-4s)
- `fal-ai/flux-pro` - Professional quality images (3-6s)
- `fal-ai/stable-diffusion-v3-medium` - Stable, reliable generation (4-8s)
- `fal-ai/aura-flow` - Artistic style generation (3-7s)
- `fal-ai/pixart-sigma` - High-resolution imagery (4-8s)

**⚠️ Models with Known Issues:**
- `fal-ai/imagen4/preview` - Status endpoints return 405 (server-side)
- `fal-ai/any-llm/enterprise` - Status endpoints return 405 (server-side)
- `fal-ai/flux/dev` - Status endpoints return 405 (server-side)
- `fal-ai/flux/schnell` - Status endpoints return 405 (server-side)

*Note: Use confirmed working models for production applications.*

See [FAL.AI Models](https://fal.ai/models) for the complete list.

## Support & Security

For security issues, please email diagngo@gmail.com.

## License

MIT License - see LICENSE.

## Credits

- Built with [Saloon v3](https://github.com/saloonphp/saloon)
- Inspired by [replicate-php](https://github.com/replicate-php)