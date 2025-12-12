<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = env('GEMINI_API_KEY');
$apiVersion = env('GEMINI_API_VERSION', 'v1beta');

$url = "https://generativelanguage.googleapis.com/{$apiVersion}/models?key={$apiKey}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$logContent = "HTTP Code: $httpCode\n";

$data = json_decode($response, true);

if (isset($data['models'])) {
    foreach ($data['models'] as $model) {
        // if (strpos($model['name'], 'gemini') !== false) {
        $logContent .= $model['name'] . "\n";
        // }
    }
} else {
    $logContent .= "Response Body: " . $response . "\n";
}

file_put_contents('models_found.txt', $logContent);
echo "Written to models_found.txt";
