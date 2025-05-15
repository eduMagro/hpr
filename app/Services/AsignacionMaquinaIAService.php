<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AsignacionMaquinaIAService
{
    public function decidirMaquina(array $datos): string
    {
        $prompt = $this->crearPromptDecidir($datos);

        $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Eres un experto en planificación de producción en una fábrica de hierros. Tu tarea es elegir la mejor máquina para fabricar un elemento en función de sus características.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
        ]);

        return $response->json('choices.0.message.content') ?? 'Sin respuesta de la IA';
    }

    private function crearPromptDecidir(array $datos): string
    {
        return <<<EOT
Estoy fabricando un elemento con estas características:
- Diámetro: {$datos['diametro']} mm
- Longitud: {$datos['longitud']} mm
- Figura: {$datos['figura']}
- Dobles por barra: {$datos['dobles']}
- Número de barras: {$datos['barras']}
- Peso total estimado: {$datos['peso']} kg
- Ensamblado: {$datos['ensamblado']}

Las máquinas disponibles son: {$datos['maquinas_disponibles']} (estos son los códigos de máquina disponibles en la fábrica).

Indica qué máquina es la más adecuada para este trabajo. Primero escribe el **código exacto de la máquina en la primera línea** y después da una explicación clara y breve del porqué.
EOT;
    }
}
