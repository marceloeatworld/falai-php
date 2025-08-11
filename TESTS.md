# FAL.AI PHP Client - Test Documentation

## Test Suite Overview

The FAL.AI PHP client includes comprehensive unit and integration tests to ensure reliability and correctness.

## Running Tests

### Prerequisites

```bash
# Install dependencies including PHPUnit
composer install

# Set your FAL.AI API key (for integration tests)
export FAL_API_KEY="your-api-key-here"
```

### Run All Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run with detailed output
./vendor/bin/phpunit --testdox

# Run only unit tests (no API calls)
./vendor/bin/phpunit tests/FalAITest.php

# Run only integration tests (requires API key)
./vendor/bin/phpunit tests/IntegrationTest.php
```

## Unit Tests (tests/FalAITest.php)

Unit tests use mock responses and don't require an API key. They test:

### Core Functionality
- ✅ Constructor sets API key correctly
- ✅ Base URL resolution (https://queue.fal.run/)
- ✅ Default headers configuration
- ✅ Resource instantiation

### Request Creation
- ✅ Create generation with mock response
- ✅ Webhook configuration
- ✅ Models with subpaths support

### Status Management
- ✅ Check status with queue position
- ✅ Check status in progress
- ✅ Check status completed
- ✅ Status enum functionality

### Result Handling
- ✅ Get final result
- ✅ Error response handling
- ✅ Data transformation to array

### Request Control
- ✅ Cancel request
- ✅ Handle already completed cancellation
- ✅ Synchronous request support

## Integration Tests (tests/IntegrationTest.php)

Integration tests make real API calls and require a valid API key.

### Tested Models

The tests use these models:
- `fal-ai/fast-sdxl` - Fast image generation
- `fal-ai/any-llm/enterprise` - LLM text generation
- `fal-ai/imagen4/preview` - High-quality image generation (optional)

### Test Coverage

1. **Create Generation**
   - Submit request to queue
   - Verify request ID and URLs returned

2. **Status Checking**
   - Poll status updates
   - Check queue position
   - Verify status transitions

3. **Result Retrieval**
   - Get completed results
   - Parse response payload
   - Handle images and metadata

4. **Request Cancellation**
   - Cancel queued requests
   - Handle cancellation states

5. **Synchronous Requests**
   - Direct API calls without queue
   - Immediate response handling

6. **Webhook Configuration**
   - Set webhook URLs
   - Verify gateway request ID

7. **Error Handling**
   - Invalid input validation
   - HTTP error responses
   - Malformed data handling

8. **Streaming Status**
   - Server-sent events processing
   - Real-time status updates

## Example Test Output

```
FAL.AI PHP Client - Complete Test Suite
========================================

Test 1: Image Generation Queue (fast-sdxl)
-------------------------------------------
✓ Request created
  Request ID: ca6c4907-4646-408e-a19a-7cc8fe59fd13
  
Test 2: Status Checking
------------------------
  Attempt 1: IN_PROGRESS
  Attempt 2: COMPLETED
✓ Generation completed successfully!

Test 3: Getting Final Result
-----------------------------
✓ Result retrieved
  Images generated: 1
  Image URL: https://v3.fal.media/files/...
  Dimensions: 512x512
  Seed used: 6120680204242773068
```

## Mock Testing

For unit tests, we use Saloon's MockClient:

```php
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

$mockClient = new MockClient([
    MockResponse::make([
        'request_id' => '123-456-789',
        'status' => 'IN_QUEUE',
        'queue_position' => 5
    ], 200),
]);

$client->withMockClient($mockClient);
```

## Continuous Integration

Add to your CI pipeline:

```yaml
# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - run: composer install
      - run: ./vendor/bin/phpunit tests/FalAITest.php
```

## Test API Keys

For testing, you can use the provided test key (limited usage):
```
8cc3571a-dae1-41a7-8b38-0bb0230bffbe:a78a0f9ae506d984fc4a0259cff27267
```

**Note**: This key has rate limits. For production, use your own API key from [fal.ai](https://fal.ai).

## Troubleshooting

### Common Issues

1. **405 Method Not Allowed**
   - Some endpoints only accept POST requests
   - Status/result endpoints might return HTML on errors

2. **Timeout Errors**
   - Image generation can take 10-30 seconds
   - Increase timeout in integration tests if needed

3. **JSON Decode Errors**
   - API might return HTML error pages
   - Check response content type and handle accordingly

4. **Rate Limiting**
   - Test API key has usage limits
   - Space out integration tests to avoid rate limits

## Coverage Report

Generate code coverage report:

```bash
# Requires Xdebug or PCOV
./vendor/bin/phpunit --coverage-html build/coverage
```

## Contributing

When adding new features:
1. Write unit tests with mocked responses
2. Add integration tests for real API calls
3. Update this documentation
4. Ensure all tests pass before submitting PR