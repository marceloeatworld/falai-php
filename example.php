<?php

require_once 'vendor/autoload.php';

use MarceloEatWorld\FalAI\FalAI;
use MarceloEatWorld\FalAI\Queue\QueuePriority;

// Example usage of the production-ready FAL.AI PHP client

try {
    // 1. Create client (reads FAL_KEY environment variable)
    $fal = FalAI::client();
    
    // 2. Queue-based processing (recommended for production)
    echo "🚀 Submitting generation to queue...\n";
    
    $submission = $fal->queue()->submit(
        endpointId: 'fal-ai/fast-sdxl',
        input: [
            'prompt' => 'A serene mountain landscape at sunset with vibrant colors',
            'image_size' => 'landscape_4_3',
            'num_inference_steps' => 4
        ],
        webhookUrl: null, // Optional: 'https://your-app.com/webhook'
        hint: 'fal-ai/fast-sdxl',
        priority: QueuePriority::Normal->value
    );
    
    echo "✅ Request ID: " . $submission->requestId . "\n";
    echo "📊 Status: " . ($submission->status ? $submission->status->value : 'unknown') . "\n\n";
    
    // 3. Stream status updates until completion
    echo "📡 Streaming status updates...\n";
    
    foreach ($fal->queue()->streamStatus('fal-ai/fast-sdxl', $submission->requestId, true) as $update) {
        $status = $update['status'] ?? 'unknown';
        echo "Status: $status";
        
        if (isset($update['queuePosition'])) {
            echo " (Queue position: {$update['queuePosition']})";
        }
        
        echo "\n";
        
        if ($status === 'COMPLETED') {
            echo "\n🎉 Generation completed!\n";
            break;
        }
        
        if ($status === 'ERROR') {
            echo "\n❌ Generation failed.\n";
            break;
        }
        
        // Prevent infinite loop in example
        static $counter = 0;
        if (++$counter > 30) {
            echo "\n⏰ Stopping example after 30 updates...\n";
            break;
        }
        
        sleep(2);
    }
    
    // 4. Get final result
    echo "\n📥 Retrieving final result...\n";
    $result = $fal->queue()->result('fal-ai/fast-sdxl', $submission->requestId);
    
    if ($result->data && isset($result->data['images'])) {
        echo "🖼️ Generated images:\n";
        foreach ($result->data['images'] as $i => $image) {
            echo "  " . ($i + 1) . ". " . $image['url'] . "\n";
        }
    } else {
        echo "⚠️ No images in result\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}