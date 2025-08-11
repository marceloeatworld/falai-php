# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP client library for the FAL.AI API, built on Saloon v3. It provides a simple interface for generating AI content using FAL AI models (Recraft, Flux Pro) and ComfyUI workflows.

### ✨ Key Features
- **Automatic Array Normalization**: Handles single image URLs vs array requirements automatically
- **Smart Field Conversion**: Converts string image URLs to arrays when required by specific models
- **422 Error Prevention**: Eliminates "Input should be a valid list" errors for image fields
- **Universal Compatibility**: Works with all FAL.AI models including ideogram, flux, stable-diffusion, etc.

## Development Commands

```bash
# Install dependencies
composer install

# Run tests (if test directory exists)
./vendor/bin/phpunit

# Autoload refresh after adding new classes
composer dump-autoload
```

## Architecture

### Core Structure

The library follows a **Resource-Based Pattern** built on Saloon HTTP client:

- `src/FalAI.php`: Main connector class extending Saloon's Connector - handles authentication and base configuration
- `src/GenerationsResource.php`: Resource class containing all generation operations (create, status, result, cancel)
- `src/Requests/`: Individual request classes for each API operation
- `src/Data/GenerationData.php`: Immutable DTO for API responses with error handling

### Request Path Resolution

The request classes implement intelligent model path handling:
- FAL AI models (e.g., `fal-ai/recraft-v3`): Uses first two segments as base path
- Other models: Uses full path as provided
- Endpoints: `{basePath}/requests/{requestId}/[status|cancel]`

### Critical Implementation Notes

**API Method Signatures**: The implementation now correctly requires the model ID for all operations:
- `checkStatus(string $model, string $requestId, bool $includeLogs = false)`
- `getResult(string $model, string $requestId)`
- `cancel(string $model, string $requestId)`

This aligns with the FAL.AI API specification where endpoints are: `{model_id}/requests/{request_id}/[status|cancel]`

### Response Handling

`GenerationData::fromResponse()` handles multiple response formats:
- Creation responses with `request_id`
- Status responses with `status` field
- Error responses with `error` field
- Direct result responses

### 🔧 Automatic Array Normalization

**Problem Solved**: Many FAL.AI models require image fields as arrays, even for single images. This caused 422 "Input should be a valid list" errors.

**Solution**: `GenerationsResource::normalizeArrayFields()` automatically converts:

```php
// Before (would cause 422 error):
$input = [
    'reference_image_urls' => 'single-image.jpg'  // String
];

// After (automatic conversion):
$input = [
    'reference_image_urls' => ['single-image.jpg']  // Array
];
```

**Affected Fields** (automatically converted to arrays):
- `reference_image_urls` - Reference images for generation
- `reference_mask_urls` - Mask images for inpainting
- `image_urls` - Input images for processing
- `mask_urls` - Mask URLs for editing
- `style_reference_urls` - Style reference images
- `character_images` - Character reference images
- `pose_images` - Pose reference images
- `uploaded_masks` - Uploaded mask files

**Usage**: No changes required in your code. Pass single strings or arrays - both work:

```php
// Both formats work automatically:
$client->generations()->create('fal-ai/ideogram/character', [
    'prompt' => 'character portrait',
    'reference_image_urls' => 'image.jpg'  // String - auto-converted
]);

$client->generations()->create('fal-ai/ideogram/character', [
    'prompt' => 'character portrait',
    'reference_image_urls' => ['image1.jpg', 'image2.jpg']  // Array - preserved
]);
```

## Key Files to Understand

1. `src/GenerationsResource.php`: Contains all API operations - review this when adding new endpoints
2. `src/Requests/CreateRequest.php`: Template for POST requests with JSON bodies
3. `src/Requests/CheckStatus.php`: Template for GET requests with path parameters
4. `src/Data/GenerationData.php`: Response parsing logic and error handling

## Testing Approach

While PHPUnit is configured, the tests directory is gitignored. When writing tests:
- Create unit tests for request classes
- Mock Saloon responses for integration tests
- Test error handling in GenerationData::fromResponse()

## Common Tasks

### Adding a New API Endpoint

1. Create a new request class in `src/Requests/` extending appropriate Saloon request type
2. Add corresponding method in `GenerationsResource.php`
3. Update `GenerationData.php` if response format differs

### Debugging API Issues

- Check request path construction in request classes' `resolveEndpoint()` methods
- Review response parsing in `GenerationData::fromResponse()`
- Enable Saloon debugging for full request/response logging