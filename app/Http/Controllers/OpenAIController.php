<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class OpenAIController extends Controller
{
    public function index()
    {
        return view('openai.index');
    }

    public function procesar(Request $request)
    {
        $request->validate([
            'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:20480',
        ]);

        // Obtener la API key desde el archivo .env
        $apiKey = env('OPENAI_API_KEY');

        if (!$apiKey) {
            return view('openai.index', [
                'resultados' => [[
                    'nombre_archivo' => 'Error de configuración',
                    'ruta' => null,
                    'texto' => null,
                    'error' => 'No se encontró la API Key de OpenAI. Por favor, configura OPENAI_API_KEY en tu archivo .env'
                ]]
            ]);
        }

        $resultados = [];

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                try {
                    // Guardar la imagen temporalmente
                    $nombreArchivo = time() . '_' . uniqid() . '.' . $imagen->getClientOriginalExtension();
                    $rutaImagen = $imagen->storeAs('temp', $nombreArchivo, 'public');
                    $rutaCompleta = storage_path('app/public/' . $rutaImagen);

                    // Convertir la imagen a base64
                    $imagenBase64 = base64_encode(file_get_contents($rutaCompleta));
                    $mimeType = $imagen->getMimeType();

                    // Llamar a la API de OpenAI con GPT-4 Vision
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->timeout(120)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-4o',
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => [
                                    [
                                        'type' => 'text',
                                        'text' => 'Por favor, extrae todo el texto visible de esta imagen de un albarán o documento. Devuelve el texto de forma estructurada y organizada, manteniendo el orden y la jerarquía de la información. Incluye todos los números, fechas, nombres, direcciones y cualquier otro texto que veas. Si hay tablas, intenta mantener su formato.'
                                    ],
                                    [
                                        'type' => 'image_url',
                                        'image_url' => [
                                            'url' => "data:{$mimeType};base64,{$imagenBase64}",
                                            'detail' => 'high'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'max_tokens' => 4096,
                        'temperature' => 0.2
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $textoExtraido = $data['choices'][0]['message']['content'] ?? 'No se pudo extraer texto';

                        $resultados[] = [
                            'nombre_archivo' => $imagen->getClientOriginalName(),
                            'ruta' => asset('storage/' . $rutaImagen),
                            'texto' => $textoExtraido,
                            'error' => null,
                            'tokens_usados' => $data['usage']['total_tokens'] ?? 0,
                        ];
                    } else {
                        $errorMsg = $response->json()['error']['message'] ?? 'Error desconocido';

                        $resultados[] = [
                            'nombre_archivo' => $imagen->getClientOriginalName(),
                            'ruta' => asset('storage/' . $rutaImagen),
                            'texto' => null,
                            'error' => 'Error de OpenAI: ' . $errorMsg,
                        ];
                    }

                } catch (\Exception $e) {
                    Log::error('Error procesando imagen con OpenAI: ' . $e->getMessage());
                    $resultados[] = [
                        'nombre_archivo' => $imagen->getClientOriginalName(),
                        'ruta' => null,
                        'texto' => null,
                        'error' => 'Error al procesar: ' . $e->getMessage(),
                    ];
                }
            }
        }

        return view('openai.index', [
            'resultados' => $resultados
        ]);
    }
}
