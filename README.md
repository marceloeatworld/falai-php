# FAL AI PHP Client

A lightweight PHP client for [FAL.AI](https://fal.ai) built with Saloon v3. Create AI-powered content with ease.

[![Join FAL.AI Discord](data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTcwIiBoZWlnaHQ9IjE3MSIgdmlld0JveD0iMCAwIDE3MCAxNzEiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0xMDkuNTcxIDAuNjkwMDQyQzExMi41MTUgMC42OTAwNDIgMTE0Ljg3NCAzLjA4MzQ4IDExNS4xNTUgNi4wMTM1MkMxMTcuNjY1IDMyLjE0OSAxMzguNDY2IDUyLjk0OCAxNjQuNjAzIDU1LjQ1OEMxNjcuNTM0IDU1LjczOTQgMTY5LjkyNyA1OC4wOTg1IDE2OS45MjcgNjEuMDQyVjExMC4yNTVDMTY5LjkyNyAxMTMuMTk4IDE2Ny41MzQgMTE1LjU1NyAxNjQuNjAzIDExNS44MzlDMTM4LjQ2NiAxMTguMzQ5IDExNy42NjUgMTM5LjE0OCAxMTUuMTU1IDE2NS4yODNDMTE0Ljg3NCAxNjguMjEzIDExMi41MTUgMTcwLjYwNyAxMDkuNTcxIDE3MC42MDdINTAuMzU1M0M1Ny40MTE2IDE3MC42MDcgNTUuMDUyNCAxNjguMjEzIDU0Ljc3MDkgMTY1LjI4M0M1Mi4yNjA4IDEzOS4xNDggMzEuNDYwMSAxMTguMzQ5IDUuMzIyODkgMTE1LjgzOUMyLjM5MjY2IDExNS41NTctMC4wMDA5NzY1NjIgMTEzLjE5OCAtMC4wMDA5NzY1NjIgMTEwLjI1NVY2MS4wNDJDLTAuMDAwOTc2NTYyIDU4LjA5ODUgMi4zOTI2NyA1NS43Mzk0IDUuMzIyOSA1NS40NThDMzEuNDYwMSA1Mi45NDggNTIuMjYwOCAzMi4xNDkgNTQuNzcwOSA2LjAxMzUxQzU1LjA1MjQgMy4wODM0OCA1Ny40MTE2IDAuNjkwMDQyIDYwLjM1NTMgMC42OTAwNDJIMTA5LjU3MVpNMzQuMTE4MiA4NS41MDQ1QzM0LjExODIgMTEzLjc3NiA1Ny4wMTI0IDEzNi42OTQgODUuMjUzOSAxMzYuNjk0QzExMy40OTUgMTM2LjY5NCAxMzYuMzkgMTEzLjc3NiAxMzYuMzkgODUuNTA0NUMxMzYuMzkgNTcuMjMzMiAxMTMuNDk1IDM0LjMxNDcgODUuMjUzOSAzNC4zMTQ3QzU3LjAxMjQgMzQuMzE0NyAzNC4xMTgyIDU3LjIzMzIgMzQuMTE4MiA4NS41MDQ1WiIgZmlsbD0iYmxhY2siLz48L3N2Zz4=)](https://discord.gg/fal-ai)

## Features

* 🎨 Support for all FAL AI models (Recraft, Flux Pro, etc.)
* 🔄 Full queue system with status tracking
* 📡 Webhook support
* 🛠️ ComfyUI & Workflows support
* ⚡ Simple, intuitive API

## Installation

```bash
composer require marceloeatworld/falai-php
```

## Quick Start

```php
use MarceloEatWorld\FalAI\FalAI;

$falAI = new FalAI('your-api-key');

// Generate an image
$result = $falAI->generations()->create('fal-ai/recraft-v3', [
    'prompt' => 'A beautiful landscape',
    'negative_prompt' => 'low quality',
    'image_size' => 'square_hd'
    'seed' => '42'
]);

// Check generation status using requestId
$status = $falAI->generations()->checkStatus($result->requestId);

// Get final result when completed
$finalResult = $falAI->generations()->getResult($result->requestId);
```

## Models & Workflows

```php
// FAL AI Models
$result = $falAI->generations()->create('fal-ai/flux-pro/v1.1-ultra', [
    'prompt' => 'A futuristic city',
    'negative_prompt' => 'low quality',
    'image_size' => 'square_hd'
    'seed' => '42'
]);

// ComfyUI Workflows
$result = $falAI->generations()->create('comfy/youraccount/workflow', [
    'loadimage_1' => 'https://example.com/image.jpg',
    'prompt' => 'Make it anime style'
    'seed' => '42'
]);

// Track any generation with requestId
$status = $falAI->generations()->checkStatus($result->requestId);
```

## Advanced Usage

```php
// Use webhooks
$result = $falAI->generations()
    ->withWebhook('https://your-site.com/webhook')
    ->create('fal-ai/recraft-v3', [
        'prompt' => 'A serene lake'
        'seed' => '42'
    ]);

// Cancel a generation using requestId
$cancelled = $falAI->generations()->cancel($result->requestId);
```

## Response Structure

The `GenerationData` object contains:
- `requestId`: Unique identifier for tracking the generation
- `responseUrl`: URL to fetch the result
- `statusUrl`: URL to check status
- `cancelUrl`: URL to cancel generation
- `status`: Current status (IN_QUEUE, IN_PROGRESS, COMPLETED, ERROR)
- `payload`: Generation result data when completed
- `error`: Error message if any

## Tracking Generations

```php
// Store the requestId after creation
$requestId = $result->requestId;

// Later, check status
$status = $falAI->generations()->checkStatus($requestId);

if ($status->status === 'COMPLETED') {
    // Get the final result
    $finalResult = $falAI->generations()->getResult($requestId);
    // Access the generated images
    $images = $finalResult->payload['images'] ?? [];
}
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

public function generate(FalAI $falAI)
{
    $result = $falAI->generations()->create('fal-ai/recraft-v3', [
        'prompt' => 'A mountain landscape'
        'seed' => '42'
    ]);
    
    // Store requestId for later use
    $requestId = $result->requestId;
}

public function checkStatus(FalAI $falAI, string $requestId)
{
    return $falAI->generations()->checkStatus($requestId);
}
```

## Support & Security

For security issues, please email diagngo@gmail.com.

## License

MIT License - see LICENSE.

## Credits

- Built with [Saloon v3](https://github.com/saloonphp/saloon)
- Inspired by [replicate-php](https://github.com/replicate-php)