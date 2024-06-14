# FAL AI PHP Client 

[FAL.AI](https://fal.ai/)
[FAL.AI Discord](https://discord.gg/fal-ai)

This is a framework-agnostic PHP client for Fal AI built on the amazing Saloon v3 🤠 library. Use it to easily interact with the Fal AI API and generate images directly from your PHP application.


## Table of contents

- Quick Start
- Using with Laravel
- Response Data
- Webhooks
- Generation Methods
    - create
    - workflow

## 🚀 Quick start

### Install with composer

```php
composer require marceloeatworld/falai-php
```

Create a new FalAI instance

```php
use MarceloEatWorld\FalAI\FalAI;
use MarceloEatWorld\FalAI\Data\GenerationData;
```

Using with Laravel
Begin by adding your API key to your services config file:
```php
// config/services.php
'falai' => [
    'api_key' => env('FAL_API_KEY'),
],
```
Bind the FalAI class in a service provider
```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->bind(FalAI::class, function () {
        return new FalAI(
            apiKey: config('services.falai.api_key'),
        );
    });
}
```

Response Data
All responses are returned as data objects. Detailed information can be found by inspecting the GenerationData class properties.

GenerationData
Webhooks
Fal AI allows you to configure a webhook to be called when your image generation is complete. To do so, chain withWebhook($url) onto your FalAI instance before calling the create or workflow method. 
For example:

```php
$falAI->generations()->withWebhook('https://www.example.com/webhook')->create($model, $input);
$data->requestId; // bf1bb655-9027-4d01-ac38-f85e0cb007dc
```
## Generation Methods

### Using create
Next, utilize it to generate an image using the create method. Remember to always prefix the model name with 'fal-ai/'.

```php
use MarceloEatWorld\FalAI\FalAI;
use MarceloEatWorld\FalAI\Data\GenerationData;

$model = 'fal-ai/modelyouwant';
$input = [
    "image_url" => "https://example.com/image.png",
    "prompt" => "A tender moment between the newlyweds, capturing their happiness with a natural background and soft light.",
    "negative_prompt" => "nsfw, lowres, bad anatomy",
    "seed" => 42
];

$data = $falAI->generations()->create($model, $input);
$data->requestId; // bf1bb655-9027-4d01-ac38-f85e0cb007dc
```


### Using Workflows and ComfyUI

In addition to generating images using predefined models, you can also use custom workflows and ComfyUI with the 'workflow' method. 
Here's an example:

```php
use MarceloEatWorld\FalAI\FalAI;
use MarceloEatWorld\FalAI\Data\GenerationData;

// Replace 'workflows/youraccount/fantasy-character-generator' with your actual workflow ID
$workflowId = 'workflows/youraccount/fantasy-character-generator';

// Replace 'comfy/youraccount/fantasy-character-generator' with your actual ComfyUI ID
$workflowId = 'comfy/youraccount/fantasy-character-generator';

$input = [
    'input' => [
        'character_description' => 'A brave elven warrior with long, flowing hair and a glowing magical sword.',
        'seed' => 42,
    ],
];

$data = $falAI->generations()->withWebhook($webhookUrl)->workflow($workflowId, $input);
$data->requestId; // 5e8f1ab3-2c7d-4e9a-b5d6-8c3a1f9b04e7
```
