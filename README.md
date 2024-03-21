# FAL AI PHP Client

This is a framework-agnostic PHP client for Fal AI built on the amazing Saloon v3 🤠 library. Use it to easily interact with the Fal AI API and generate images right from your PHP application.


## Table of contents

- Quick Start
- Using with Laravel
- Response Data
- Webhooks
- Generation Methods
    - get
    - create

## 🚀 Quick start

### Install with composer

```bash
composer require marceloeatworld/falai-php
```

Create a new FalAI instance

```bash
use MarceloEatWorld\FalAI\FalAI;

$falAI = new FalAI(
    apiKey: $_ENV['FAL_AI_API_KEY'],
);
```
Then use it to generate an image

```bash
$model = 'instantid/lcm';
$input = [
    "face_image_url" => "https://example.com/image.png",
    "prompt" => "A tender moment between the newlyweds, capturing their happiness with a natural background and soft light.",
    "style" => "(No style)",
    "negative_prompt" => "nsfw, lowres, bad anatomy, bad hands, text, error, missing fingers, extra digit, fewer digits, cropped, worst quality, low quality, normal quality, jpeg artifacts, signature, watermark, username, blurry",
    "num_inference_steps" => 5,
    "guidance_scale" => 1.5,
    "controlnet_selection" => "canny",
    "controlnet_conditioning_scale" => 0.4,
    "ip_adapter_scale" => 0.7,
    "identity_controlnet_conditioning_scale" => 0.7,
    "enhance_face_region" => true,
    "seed" => 454545
];

$data = $falAI->generations()->create($model, $input);
$data->requestId; // bf1bb655-9027-4d01-ac38-f85e0cb007dc
```
Using with Laravel
Begin by adding your API key to your services config file
```bash
// config/services.php
'falai' => [
    'api_key' => env('FAL_AI_API_KEY'),
],
```
Bind the FalAI class in a service provider
```bash
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
And use anywhere in your application
```bash
$data = app(FalAI::class)->generations()->get($model, $requestId);
```

Response Data
All responses are returned as data objects. Detailed information can be found by inspecting the following class properties:

GenerationData
Webhooks
Fal AI allows you to configure a webhook to be called when your image generation is complete. To do so, chain withWebhook($url) onto your FalAI instance before calling the create method. For example:

```bash
$falAI->generations()->withWebhook('https://www.example.com/webhook')->create($model, $input);
$data->requestId; // bf1bb655-9027-4d01-ac38-f85e0cb007dc
```