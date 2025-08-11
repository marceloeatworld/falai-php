<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MarceloEatWorld\FalAI\FalAI;

/**
 * AUTOMATIC ARRAY NORMALIZATION DEMO
 * 
 * This example demonstrates how the client automatically handles
 * the conversion between single image URLs and arrays for different models.
 */

$apiKey = 'your-fal-api-key-here';

echo "🔧 FAL.AI PHP CLIENT - Automatic Array Normalization Demo\n";
echo str_repeat('=', 60) . "\n";

$client = new FalAI($apiKey);

echo "Problem: Some FAL.AI models require image fields as arrays,\n";
echo "even when you only have one image. This caused 422 errors.\n\n";

echo "Solution: The client now automatically converts formats!\n";
echo str_repeat('-', 40) . "\n\n";

// Example 1: Single image URL (gets converted to array automatically)
echo "Example 1: Single Image URL\n";
echo "Before: 'reference_image_urls' => 'image.jpg'\n";
echo "After:  'reference_image_urls' => ['image.jpg']\n\n";

$singleImageInput = [
    'prompt' => 'A beautiful landscape painting',
    'reference_image_urls' => 'https://example.com/reference.jpg', // Single string
    'aspect_ratio' => '1:1'
];

try {
    $result = $client->generations()->create('fal-ai/ideogram/character', $singleImageInput);
    echo "✅ Request successful with single image URL!\n";
    echo "   Request ID: {$result->requestId}\n\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// Example 2: Multiple images (array preserved)
echo "Example 2: Multiple Images Array\n";
echo "Before: 'reference_image_urls' => ['img1.jpg', 'img2.jpg']\n";
echo "After:  'reference_image_urls' => ['img1.jpg', 'img2.jpg']\n\n";

$multiImageInput = [
    'prompt' => 'A character with multiple references',
    'reference_image_urls' => [
        'https://example.com/reference1.jpg',
        'https://example.com/reference2.jpg'
    ],
    'aspect_ratio' => '1:1'
];

try {
    $result = $client->generations()->create('fal-ai/ideogram/character', $multiImageInput);
    echo "✅ Request successful with multiple images!\n";
    echo "   Request ID: {$result->requestId}\n\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// Example 3: Multiple field types
echo "Example 3: Multiple Field Types\n";
echo "All these fields get normalized automatically:\n";

$complexInput = [
    'prompt' => 'Complex generation with multiple inputs',
    
    // These will all be converted to arrays if they're strings:
    'reference_image_urls' => 'https://example.com/ref.jpg',
    'mask_urls' => 'https://example.com/mask.jpg',
    'style_reference_urls' => 'https://example.com/style.jpg',
    
    'aspect_ratio' => '16:9'
];

echo "  reference_image_urls: string → array\n";
echo "  mask_urls: string → array\n";
echo "  style_reference_urls: string → array\n\n";

try {
    $result = $client->generations()->create('fal-ai/fast-sdxl', $complexInput);
    echo "✅ Request successful with multiple field normalization!\n";
    echo "   Request ID: {$result->requestId}\n\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

echo str_repeat('=', 60) . "\n";
echo "🎯 Summary:\n";
echo "   ✓ No more 422 'should be a valid list' errors\n";
echo "   ✓ Single strings automatically converted to arrays\n";
echo "   ✓ Existing arrays preserved unchanged\n";
echo "   ✓ Works with all FAL.AI models\n";
echo "   ✓ No code changes required on your end\n\n";

echo "💡 Supported Fields (auto-normalized):\n";
$fields = [
    'reference_image_urls',
    'reference_mask_urls',
    'image_urls',
    'mask_urls',
    'style_reference_urls',
    'character_images',
    'pose_images',
    'uploaded_masks'
];

foreach ($fields as $field) {
    echo "   • {$field}\n";
}

echo "\n🚀 You can now use either format without worrying about API requirements!\n";
echo str_repeat('=', 60) . "\n";