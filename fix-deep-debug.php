<?php

require_once __DIR__ . '/vendor/autoload.php';

use MarceloEatWorld\FalAI\FalAI;

$apiKey = '8cc3571a-dae1-41a7-8b38-0bb0230bffbe:a78a0f9ae506d984fc4a0259cff27267';
$client = new FalAI($apiKey);

echo "🔧 DEEP DEBUG - Finding 405 Sources\n";
echo str_repeat('=', 40) . "\n\n";

// Enable detailed debugging
$client->debug();

// Test: Create request and track every step
$result = $client->generations()->create('fal-ai/fast-sdxl', [
    'prompt' => 'debug deep test',
    'image_size' => 'square'
]);

$requestId = $result->requestId;
echo "Request ID: {$requestId}\n";

// Wait for completion
sleep(5);

echo "\n1. Testing checkStatus...\n";
try {
    $status = $client->generations()->checkStatus('fal-ai/fast-sdxl', $requestId, true);
    echo "Status: {$status->status->value}\n";
    
    if ($status->isFinished()) {
        echo "\n2. Testing getResult...\n";
        try {
            $result = $client->generations()->getResult('fal-ai/fast-sdxl', $requestId);
            echo "Result retrieved successfully\n";
        } catch (Exception $e) {
            echo "GetResult Error: " . $e->getMessage() . "\n";
            
            if ($e instanceof \Saloon\Exceptions\Request\RequestException) {
                $response = $e->getResponse();
                echo "Status: " . $response->status() . "\n";
                echo "Headers: " . json_encode($response->headers(), JSON_PRETTY_PRINT) . "\n";
                echo "Body: " . $response->body() . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "CheckStatus Error: " . $e->getMessage() . "\n";
    
    if ($e instanceof \Saloon\Exceptions\Request\RequestException) {
        $response = $e->getResponse();
        echo "Status: " . $response->status() . "\n";
        echo "Headers: " . json_encode($response->headers(), JSON_PRETTY_PRINT) . "\n";
        echo "Body: " . $response->body() . "\n";
    }
}

// Test direct curl commands
echo "\n3. Testing with direct curl...\n";

// Test status endpoint
$statusCmd = "curl -s -X GET \"https://queue.fal.run/fal-ai/fast-sdxl/requests/{$requestId}/status\" " .
            "-H \"Authorization: Key {$apiKey}\" " .
            "-H \"Content-Type: application/json\"";

echo "Command: " . substr($statusCmd, 0, 100) . "...\n";
$statusResponse = shell_exec($statusCmd);
echo "Status Response: {$statusResponse}\n";

// Test result endpoint  
$resultCmd = "curl -s -X GET \"https://queue.fal.run/fal-ai/fast-sdxl/requests/{$requestId}\" " .
            "-H \"Authorization: Key {$apiKey}\" " .
            "-H \"Content-Type: application/json\"";

echo "\nCommand: " . substr($resultCmd, 0, 100) . "...\n";
$resultResponse = shell_exec($resultCmd);
echo "Result Response: {$resultResponse}\n";