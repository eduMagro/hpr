<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\AsistenteVirtualService;

class AsistenteVirtualController extends Controller
{
    /**
     * Servicio de asistente virtual
     */
    private $asistente;

    /**
     * Constructor - Inyección de dependencias
     */
    public function __construct(AsistenteVirtualService $asistente)
    {
        $this->asistente = $asistente;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('asistente-virtual.index');
    }

    /**
     * POST /api/asistente/preguntar
     * 
     * Procesa una pregunta del usuario y devuelve la respuesta del asistente
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function preguntar(Request $request)
    {
        // 1. VALIDACIÓN
        $request->validate([
            'pregunta' => 'required|string|min:3|max:500'
        ]);

        // 2. OBTENER USER ID
        $userId = Auth::id() ?? 1; // Fallback a user 1 para testing

        try {
            // 3. LLAMAR AL SERVICIO
            $resultado = $this->asistente->responder(
                $request->pregunta,
                $userId
            );

            // 4. RESPUESTA EXITOSA
            return response()->json([
                'success' => true,
                'data' => $resultado
            ]);
        } catch (\Exception $e) {
            // 5. MANEJO DE ERRORES
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/asistente/estadisticas
     * 
     * Devuelve estadísticas de uso del asistente
     * 
     * @return JsonResponse
     */
    public function estadisticas()
    {
        try {
            // Verificar si existe la tabla
            if (!DB::getSchemaBuilder()->hasTable('asistente_logs')) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'mensaje' => 'Tabla de logs no creada aún. Crea la migración para habilitar estadísticas.'
                    ]
                ]);
            }

            // 1. ESTADÍSTICAS POR TIPO DE CONSULTA
            $stats = DB::table('asistente_logs')
                ->selectRaw('
                    tipo_consulta,
                    COUNT(*) as cantidad,
                    AVG(coste) as coste_promedio,
                    AVG(duracion_segundos) as tiempo_promedio
                ')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('tipo_consulta')
                ->get();

            // 2. TOTALES GENERALES
            $totales = DB::table('asistente_logs')
                ->selectRaw('
                    COUNT(*) as total_consultas,
                    COUNT(DISTINCT user_id) as usuarios_unicos,
                    SUM(coste) as coste_total,
                    AVG(duracion_segundos) as tiempo_promedio
                ')
                ->where('created_at', '>=', now()->subDays(30))
                ->first();

            // 3. RESPUESTA
            return response()->json([
                'success' => true,
                'data' => [
                    'totales' => $totales,
                    'por_tipo' => $stats,
                    'periodo' => 'Últimos 30 días'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/asistente/sugerencias
     * 
     * Devuelve ejemplos de preguntas que el usuario puede hacer
     * 
     * @return JsonResponse
     */
    public function sugerencias()
    {
        $sugerencias = [
            [
                'categoria' => 'Pedidos',
                'ejemplos' => [
                    '¿Dónde está el pedido PC25/0001?',
                    '¿Cuáles son los pedidos pendientes?',
                    'Muestra los últimos pedidos',
                    '¿Qué pedidos hay para completar?'
                ]
            ],
            [
                'categoria' => 'Stock',
                'ejemplos' => [
                    '¿Cuánto stock hay de Ø12mm?',
                    'Muestra el stock de diámetro 16',
                    '¿Hay material disponible?',
                    '¿Qué productos tienen stock bajo?'
                ]
            ],
            [
                'categoria' => 'Planillas',
                'ejemplos' => [
                    '¿Qué planillas hay pendientes?',
                    'Información de la planilla PL0567',
                    '¿Cuál es la próxima entrega?',
                    '¿Cuántas planillas activas hay?'
                ]
            ],
            [
                'categoria' => 'Entradas',
                'ejemplos' => [
                    '¿Qué entradas hay recientes?',
                    'Muestra las últimas entregas',
                    '¿Ha llegado material nuevo?'
                ]
            ],
            [
                'categoria' => 'General',
                'ejemplos' => [
                    '¿Cómo está el sistema hoy?',
                    'Dame un resumen general',
                    '¿Qué hay pendiente?'
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $sugerencias
        ]);
    }
}
