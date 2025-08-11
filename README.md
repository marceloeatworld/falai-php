# FAL.AI PHP Client

A modern, production-ready PHP client for the [FAL.AI API](https://fal.ai) with comprehensive support for AI models including LLM, image generation, video generation, audio processing, and custom workflows.

[![Latest Version](https://img.shields.io/packagist/v/marceloeatworld/falai-php.svg)](https://packagist.org/packages/marceloeatworld/falai-php)
[![PHP Version](https://img.shields.io/packagist/php-v/marceloeatworld/falai-php.svg)](https://packagist.org/packages/marceloeatworld/falai-php)
[![License](https://img.shields.io/packagist/l/marceloeatworld/falai-php.svg)](LICENSE)

## Features

- **🚀 Queue Processing** - Reliable asynchronous processing with status tracking
- **📡 Real-time Streaming** - Server-Sent Events for live status updates  
- **🔄 Synchronous Execution** - Direct model execution for quick tasks
- **🔗 Webhook Support** - Secure webhook verification with cryptographic signatures
- **🎯 Auto Array Normalization** - Eliminates 422 "Input should be a valid list" errors
- **🏗️ Modern Architecture** - Built on Saloon v3 with PSR compliance
- **🎨 Universal Compatibility** - Works with all FAL.AI models (Flux, SDXL, LLM, Video, Audio, Workflows)

## Requirements

- PHP 8.1+
- ext-sodium (optional, for webhook verification)

## Installation

```bash
composer require marceloeatworld/falai-php
```

## Quick Start

### Configuration

Set your FAL.AI API key:

```bash
export FAL_KEY='your-fal-ai-api-key-here'
```

Or pass it directly:

```php
use MarceloEatWorld\FalAI\FalAI;

$fal = FalAI::client('your-api-key');
```

### Basic Usage

```php
use MarceloEatWorld\FalAI\FalAI;

// Create client
$fal = FalAI::client();

// Queue-based processing (recommended)
$submission = $fal->queue()->submit('fal-ai/fast-sdxl', [
    'prompt' => 'A majestic dragon soaring through clouds',
    'image_size' => 'landscape_4_3'
]);

// Stream status updates
foreach ($fal->queue()->streamStatus('fal-ai/fast-sdxl', $submission->requestId) as $update) {
    echo "Status: " . $update['status'] . "\n";
    
    if ($update['status'] === 'COMPLETED') {
        break;
    }
}

// Get final result
$result = $fal->queue()->result('fal-ai/fast-sdxl', $submission->requestId);
echo "Generated image: " . $result->data['images'][0]['url'] . "\n";
```

## Advanced Usage

### Working with Workflows

```php
// Custom workflow with automatic array normalization
$submission = $fal->queue()->submit('workflows/username/my-workflow', [
    'prompt' => 'Epic fantasy landscape',
    'reference_image_urls' => 'https://example.com/image.jpg', // Auto-converted to array
    'style' => 'cinematic'
]);

// Or use direct streaming
foreach ($fal->generations()->stream('workflows/username/my-workflow', $input) as $event) {
    // Real-time processing updates
    echo json_encode($event) . "\n";
}
```

### Priority and Performance Hints

```php
use MarceloEatWorld\FalAI\Queue\QueuePriority;

$submission = $fal->queue()->submit(
    endpointId: 'fal-ai/flux-pro',
    input: ['prompt' => 'Professional headshot'],
    webhookUrl: 'https://your-app.com/webhook',
    hint: 'fal-ai/flux-pro',
    priority: QueuePriority::High->value
);
```

### Webhook Security

```php
use MarceloEatWorld\FalAI\WebhookVerifier;
use MarceloEatWorld\FalAI\Exceptions\WebhookVerificationException;

$verifier = new WebhookVerifier();

try {
    $isValid = $verifier->verify($request->getContent(), $request->headers->all());
    
    if ($isValid) {
        $payload = json_decode($request->getContent(), true);
        
        if ($payload['status'] === 'COMPLETED') {
            // Process completed generation
            echo "Request {$payload['request_id']} completed!\n";
        }
    }
} catch (WebhookVerificationException $e) {
    echo "Webhook verification failed: " . $e->getMessage() . "\n";
}
```

### Synchronous Execution

```php
// For quick tasks that complete fast
$result = $fal->run('fal-ai/fast-lightning-sdxl', [
    'prompt' => 'A simple illustration'
]);

echo $result['images'][0]['url'];
```

## API Reference

### Client Factory

```php
// From environment variable FAL_KEY
$fal = FalAI::client();

// With explicit API key
$fal = FalAI::client('your-api-key');
```

### Queue Operations

```php
// Submit to queue
$submission = $fal->queue()->submit($endpointId, $input, $webhookUrl, $hint, $priority);

// Check status
$status = $fal->queue()->status($endpointId, $requestId, $includeLogs);

// Get result
$result = $fal->queue()->result($endpointId, $requestId);

// Stream status updates
foreach ($fal->queue()->streamStatus($endpointId, $requestId, $includeLogs) as $update) {
    // Process update
}

// Cancel request
$cancelled = $fal->queue()->cancel($endpointId, $requestId);
```

### Direct Operations

```php
// Synchronous execution
$result = $fal->run($endpointId, $input);

// Direct streaming
foreach ($fal->generations()->stream($endpointId, $input) as $event) {
    // Process event
}
```

## Error Handling

```php
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

try {
    $result = $fal->queue()->submit('fal-ai/fast-sdxl', $input);
} catch (FatalRequestException $e) {
    echo "Fatal error: " . $e->getMessage();
} catch (RequestException $e) {
    echo "Request error: " . $e->getMessage();
} catch (Exception $e) {
    echo "General error: " . $e->getMessage();
}
```

## Automatic Array Normalization

This library automatically converts single values to arrays for fields that require arrays, eliminating common 422 errors:

```php
// These are equivalent - both work without errors:

// Single value (auto-converted to array)
$input = [
    'prompt' => 'A cat',
    'reference_image_urls' => 'https://example.com/image.jpg'
];

// Array value (preserved as-is)  
$input = [
    'prompt' => 'A cat',
    'reference_image_urls' => ['https://example.com/image.jpg']
];
```

**Auto-normalized fields:**
- `reference_image_urls`, `reference_mask_urls`
- `image_urls`, `images`, `input_images`
- `mask_urls`, `style_reference_urls`
- `character_images`, `pose_images`
- `uploaded_masks`, `loadimage_1`, `loadimage_2`, `loadimage_3`

## Performance

- **Optimized autoloader** with `optimize-autoloader: true`
- **Webhook caching** - Pass PSR-16 cache to `WebhookVerifier` for better performance
- **Stream processing** - Memory-efficient event streaming
- **Connection pooling** - Built on Saloon's efficient HTTP client

## License

MIT License. See [LICENSE](LICENSE) for details.

## Contributing

Contributions are welcome! Please see our contributing guidelines.

## Support

- 🐛 **Bug Reports**: [GitHub Issues](https://github.com/marceloeatworld/falai-php/issues)
- 💬 **Questions**: [GitHub Discussions](https://github.com/marceloeatworld/falai-php/discussions)
- 📖 **Documentation**: [FAL.AI API Docs](https://fal.ai/models)

---

Built with ❤️ using [Saloon PHP](https://docs.saloon.dev/) and [SSE-Saloon](https://github.com/hosmelq/sse-saloon)