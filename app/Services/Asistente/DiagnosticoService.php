<?php

namespace App\Services\Asistente;

use App\Models\User;
use App\Models\Planilla;
use App\Models\Elemento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class DiagnosticoService
{
    protected IAService $iaService;
    protected AnalizadorSemanticoService $analizadorLocal;

    public function __construct(?string $modelo = null)
    {
        $this->iaService = new IAService($modelo);
        $this->analizadorLocal = new AnalizadorSemanticoService();
    }

    /**
     * Establece el modelo de IA a usar
     */
    public function setModelo(string $modelo): self
    {
        $this->iaService->setModelo($modelo);
        return $this;
    }

    /**
     * Obtiene el modelo actual
     */
    public function getModelo(): string
    {
        return $this->iaService->getModelo();
    }

    /**
     * Obtiene información del modelo actual
     */
    public function getInfoModelo(): array
    {
        return $this->iaService->getInfoModelo();
    }

    /**
     * Detecta el tipo de problema usando IA para comprensión real
     */
    public function detectarProblema(string $mensaje): ?array
    {
        // Usar IA para análisis profundo del mensaje
        $analisisIA = $this->iaService->analizarProblema($mensaje);

        Log::debug('DiagnosticoService: Análisis IA', [
            'mensaje' => substr($mensaje, 0, 100),
            'es_problema' => $analisisIA['es_problema'] ?? false,
            'confianza' => $analisisIA['confianza'] ?? 0,
            'comprension' => $analisisIA['comprension'] ?? 'N/A',
        ]);

        // Si la IA no detecta un problema
        if (empty($analisisIA['es_problema'])) {
            return null;
        }

        // Si la confianza es muy baja
        if (($analisisIA['confianza'] ?? 0) < 20) {
            return null;
        }

        // Construir respuesta estructurada desde el análisis de IA
        $tipo = $analisisIA['tipo_problema'] ?? 'problema_general';
        $contexto = $this->construirContextoDesdeIA($analisisIA);

        // Si necesita clarificación
        if (!empty($analisisIA['pregunta_clarificacion']) && ($analisisIA['confianza'] ?? 0) < 60) {
            return [
                'tipo' => 'requiere_clarificacion',
                'descripcion' => $analisisIA['comprension'] ?? 'Necesito más información',
                'contexto' => $contexto,
                'analisis_ia' => $analisisIA,
                'confianza' => $analisisIA['confianza'] ?? 50,
                'pregunta' => $analisisIA['pregunta_clarificacion'],
            ];
        }

        return [
            'tipo' => $tipo !== 'ninguno' ? $tipo : 'problema_general',
            'descripcion' => $analisisIA['comprension'] ?? 'Problema detectado',
            'contexto' => $contexto,
            'analisis_ia' => $analisisIA,
            'confianza' => $analisisIA['confianza'] ?? 70,
            'gravedad' => $analisisIA['gravedad'] ?? 'media',
        ];
    }

    /**
     * Construye el contexto desde el análisis de IA
     */
    protected function construirContextoDesdeIA(array $analisis): array
    {
        $contexto = [];
        $detalles = $analisis['detalles'] ?? [];

        if (!empty($detalles['codigo_pedido'])) {
            $contexto['codigo_pedido'] = $detalles['codigo_pedido'];
        }
        if (!empty($detalles['codigo_planilla'])) {
            $contexto['codigo_planilla'] = $detalles['codigo_planilla'];
        }
        if (!empty($detalles['numero_linea'])) {
            $contexto['numero_linea'] = $detalles['numero_linea'];
        }
        if (!empty($detalles['diametro'])) {
            $contexto['diametro'] = $detalles['diametro'];
        }
        if (!empty($detalles['cantidad'])) {
            $contexto['cantidad'] = $detalles['cantidad'];
        }
        if (!empty($detalles['tiempo_transcurrido'])) {
            $contexto['tiempo_descripcion'] = $detalles['tiempo_transcurrido'];
            $contexto['tiempo_atras'] = $this->parsearTiempo($detalles['tiempo_transcurrido']);
        }

        // Entidad y acción
        if (!empty($analisis['entidad_afectada']) && $analisis['entidad_afectada'] !== 'ninguno') {
            $contexto['entidad_principal'] = $analisis['entidad_afectada'];
        }
        if (!empty($analisis['accion_realizada']) && $analisis['accion_realizada'] !== 'ninguno') {
            $contexto['accion_detectada'] = $analisis['accion_realizada'];
        }

        return $contexto;
    }

    /**
     * Parsea descripción temporal a estructura
     */
    protected function parsearTiempo(?string $descripcion): array
    {
        if (!$descripcion) {
            return ['cantidad' => 2, 'unidad' => 'hora'];
        }

        $descripcion = mb_strtolower($descripcion);

        // Detectar patrones comunes
        if (preg_match('/(\d+)\s*(minuto|min)/iu', $descripcion, $m)) {
            return ['cantidad' => (int)$m[1], 'unidad' => 'minuto'];
        }
        if (preg_match('/(\d+)\s*(hora|hr)/iu', $descripcion, $m)) {
            return ['cantidad' => (int)$m[1], 'unidad' => 'hora'];
        }
        if (preg_match('/(\d+)\s*(d[ií]a)/iu', $descripcion, $m)) {
            return ['cantidad' => (int)$m[1], 'unidad' => 'día'];
        }

        // Expresiones comunes
        if (preg_match('/hace\s+(un\s+)?momento|reci[eé]n|ahora/iu', $descripcion)) {
            return ['cantidad' => 15, 'unidad' => 'minuto'];
        }
        if (preg_match('/hace\s+(un\s+)?rato/iu', $descripcion)) {
            return ['cantidad' => 1, 'unidad' => 'hora'];
        }
        if (preg_match('/hoy|esta\s+ma[ñn]ana|esta\s+tarde/iu', $descripcion)) {
            return ['cantidad' => 4, 'unidad' => 'hora'];
        }
        if (preg_match('/ayer/iu', $descripcion)) {
            return ['cantidad' => 1, 'unidad' => 'día'];
        }

        return ['cantidad' => 2, 'unidad' => 'hora'];
    }

    /**
     * Construye el contexto desde el análisis semántico
     */
    protected function construirContextoDesdeAnalisis(array $analisis): array
    {
        $contexto = [];

        // Códigos extraídos
        if (!empty($analisis['codigos'])) {
            if (!empty($analisis['codigos']['pedido'])) {
                $contexto['codigo_pedido'] = $analisis['codigos']['pedido'][0];
            }
            if (!empty($analisis['codigos']['planilla'])) {
                $contexto['codigo_planilla'] = $analisis['codigos']['planilla'][0];
            }
            if (!empty($analisis['codigos']['linea'])) {
                $contexto['numero_linea'] = $analisis['codigos']['linea'][0];
            }
            if (!empty($analisis['codigos']['diametro'])) {
                $contexto['diametro'] = $analisis['codigos']['diametro'][0];
            }
            if (!empty($analisis['codigos']['id'])) {
                $contexto['id_especifico'] = $analisis['codigos']['id'][0];
            }
        }

        // Contexto temporal
        if (!empty($analisis['temporal'])) {
            $minutos = $analisis['temporal']['minutos_atras'];
            if ($minutos < 60) {
                $contexto['tiempo_atras'] = ['cantidad' => $minutos, 'unidad' => 'minuto'];
            } elseif ($minutos < 1440) {
                $contexto['tiempo_atras'] = ['cantidad' => ceil($minutos / 60), 'unidad' => 'hora'];
            } else {
                $contexto['tiempo_atras'] = ['cantidad' => ceil($minutos / 1440), 'unidad' => 'día'];
            }
        }

        // Cantidades mencionadas
        if (!empty($analisis['cantidades'])) {
            $contexto['cantidades'] = $analisis['cantidades'];
        }

        // Entidad principal
        if (!empty($analisis['entidades'])) {
            $contexto['entidad_principal'] = array_key_first($analisis['entidades']);
        }

        // Acción detectada
        if (!empty($analisis['acciones'])) {
            $contexto['accion_detectada'] = array_key_first($analisis['acciones']);
        }

        return $contexto;
    }

    /**
     * Diagnostica el problema y propone soluciones
     */
    public function diagnosticar(array $problema, User $user): array
    {
        $tipo = $problema['tipo'];
        $contexto = $problema['contexto'];

        // Manejar caso de clarificación
        if ($tipo === 'requiere_clarificacion') {
            return $this->generarPreguntasClarificacion($problema);
        }

        // Mapear tipos del analizador semántico a métodos de diagnóstico
        $tipoNormalizado = $this->normalizarTipoProblema($tipo);

        return match ($tipoNormalizado) {
            'linea_pedido_activada', 'linea_pedido_recepcionada', 'linea_pedido_eliminada' => $this->diagnosticarLineaPedido($contexto, $user),
            'elemento_fabricado_error', 'elemento_estado_error', 'elemento_asignacion_error', 'elemento_eliminado_error' => $this->diagnosticarElementoFabricado($contexto, $user),
            'planilla_estado_incorrecto', 'planilla_fabricacion_error', 'planilla_asignacion_error' => $this->diagnosticarEstadoPlanilla($contexto, $user),
            'asignacion_maquina_error' => $this->diagnosticarAsignacionMaquina($contexto, $user),
            'recepcion_pedido_error' => $this->diagnosticarRecepcionPedido($contexto, $user),
            'movimiento_stock_error', 'problema_stock' => $this->diagnosticarMovimientoStock($contexto, $user),
            'problema_general', 'problema_pedido', 'problema_planilla', 'problema_elemento', 'problema_maquina', 'problema_recepcion' => $this->diagnosticarGeneral($contexto, $user),
            default => [
                'encontrado' => false,
                'mensaje' => 'No pude identificar el problema específico. ¿Puedes darme más detalles?',
            ],
        };
    }

    /**
     * Normaliza el tipo de problema para el mapeo
     */
    protected function normalizarTipoProblema(string $tipo): string
    {
        // Remover prefijo "problema_" para entidades genéricas
        if (str_starts_with($tipo, 'problema_')) {
            $entidad = substr($tipo, 9);
            if (in_array($entidad, ['pedido', 'planilla', 'elemento', 'maquina', 'stock', 'recepcion'])) {
                return $tipo;
            }
        }
        return $tipo;
    }

    /**
     * Genera preguntas de clarificación basadas en el análisis de IA
     */
    protected function generarPreguntasClarificacion(array $problema): array
    {
        $analisisIA = $problema['analisis_ia'] ?? [];
        $pregunta = $problema['pregunta'] ?? $analisisIA['pregunta_clarificacion'] ?? null;

        // Construir mensaje con lo que entendió la IA
        $comprension = $problema['descripcion'] ?? $analisisIA['comprension'] ?? 'tu mensaje';

        $mensaje = "**Entendí:** {$comprension}\n\n";

        // Mostrar contexto detectado
        $contextoDetectado = [];
        if (!empty($analisisIA['entidad_afectada']) && $analisisIA['entidad_afectada'] !== 'ninguno') {
            $contextoDetectado[] = "Relacionado con: " . str_replace('_', ' ', $analisisIA['entidad_afectada']);
        }
        if (!empty($analisisIA['accion_realizada']) && $analisisIA['accion_realizada'] !== 'ninguno') {
            $contextoDetectado[] = "Acción: " . str_replace('_', ' ', $analisisIA['accion_realizada']);
        }
        if (!empty($analisisIA['intencion_usuario']) && $analisisIA['intencion_usuario'] !== 'otro') {
            $contextoDetectado[] = "Quieres: " . str_replace('_', ' ', $analisisIA['intencion_usuario']);
        }

        if (!empty($contextoDetectado)) {
            $mensaje .= implode(" | ", $contextoDetectado) . "\n\n";
        }

        // Agregar pregunta de clarificación
        if ($pregunta) {
            $mensaje .= "**Para ayudarte mejor:** {$pregunta}\n";
        }

        return [
            'encontrado' => true,
            'tipo' => 'clarificacion',
            'mensaje' => $mensaje,
            'datos' => [],
            'soluciones' => [],
            'requiere_respuesta' => true,
        ];
    }

    /**
     * Diagnostica problemas con líneas de pedido activadas
     */
    protected function diagnosticarLineaPedido(array $contexto, User $user): array
    {
        // Buscar líneas de pedido recientemente modificadas
        $query = DB::table('pedido_productos')
            ->select(
                'pedido_productos.*',
                'pedidos.codigo as pedido_codigo',
                'productos_base.descripcion as producto',
                'productos_base.diametro'
            )
            ->leftJoin('pedidos', 'pedido_productos.pedido_id', '=', 'pedidos.id')
            ->leftJoin('productos_base', 'pedido_productos.producto_base_id', '=', 'productos_base.id')
            ->whereNull('pedido_productos.deleted_at');

        // Filtrar por contexto
        if (!empty($contexto['codigo_pedido'])) {
            $query->where('pedidos.codigo', 'LIKE', '%' . $contexto['codigo_pedido'] . '%');
        }

        if (!empty($contexto['diametro'])) {
            $query->where('productos_base.diametro', $contexto['diametro']);
        }

        // Buscar modificaciones recientes
        $tiempoAtras = $contexto['tiempo_atras'] ?? ['cantidad' => 2, 'unidad' => 'hora'];
        $desde = match ($tiempoAtras['unidad']) {
            'minuto' => now()->subMinutes($tiempoAtras['cantidad']),
            'hora' => now()->subHours($tiempoAtras['cantidad']),
            'día', 'dia' => now()->subDays($tiempoAtras['cantidad']),
            default => now()->subHours(2),
        };

        $lineasRecientes = $query
            ->where('pedido_productos.updated_at', '>=', $desde)
            ->orderByDesc('pedido_productos.updated_at')
            ->limit(10)
            ->get();

        if ($lineasRecientes->isEmpty()) {
            // Buscar todas las líneas activas recientes sin filtro de tiempo tan estricto
            $lineasRecientes = DB::table('pedido_productos')
                ->select(
                    'pedido_productos.*',
                    'pedidos.codigo as pedido_codigo',
                    'productos_base.descripcion as producto',
                    'productos_base.diametro'
                )
                ->leftJoin('pedidos', 'pedido_productos.pedido_id', '=', 'pedidos.id')
                ->leftJoin('productos_base', 'pedido_productos.producto_base_id', '=', 'productos_base.id')
                ->whereNull('pedido_productos.deleted_at')
                ->where('pedido_productos.updated_at', '>=', now()->subDay())
                ->orderByDesc('pedido_productos.updated_at')
                ->limit(10)
                ->get();
        }

        if ($lineasRecientes->isEmpty()) {
            return [
                'encontrado' => false,
                'mensaje' => "No encontré líneas de pedido modificadas recientemente.\n\n¿Puedes indicarme el código del pedido o más detalles sobre la línea que activaste?",
            ];
        }

        // Preparar diagnóstico
        $lineas = $lineasRecientes->map(function ($l) {
            return [
                'id' => $l->id,
                'pedido' => $l->pedido_codigo ?? 'N/A',
                'producto' => $l->producto ?? 'N/A',
                'diametro' => $l->diametro ?? 'N/A',
                'cantidad' => $l->cantidad ?? 0,
                'cantidad_recepcionada' => $l->cantidad_recepcionada ?? 0,
                'estado' => $l->estado ?? 'N/A',
                'modificado' => Carbon::parse($l->updated_at)->diffForHumans(),
            ];
        })->toArray();

        return [
            'encontrado' => true,
            'tipo' => 'linea_pedido_activada',
            'mensaje' => "Encontré las siguientes líneas de pedido modificadas recientemente:",
            'datos' => $lineas,
            'soluciones' => [
                [
                    'accion' => 'revertir_estado_linea',
                    'descripcion' => 'Cambiar el estado de la línea a su valor anterior',
                    'requiere_seleccion' => true,
                ],
                [
                    'accion' => 'revertir_cantidad_recepcionada',
                    'descripcion' => 'Revertir la cantidad recepcionada a 0 o valor anterior',
                    'requiere_seleccion' => true,
                ],
                [
                    'accion' => 'eliminar_linea',
                    'descripcion' => 'Eliminar la línea de pedido (soft delete)',
                    'requiere_confirmacion' => true,
                ],
            ],
        ];
    }

    /**
     * Diagnostica problemas con elementos fabricados
     */
    protected function diagnosticarElementoFabricado(array $contexto, User $user): array
    {
        $query = DB::table('elementos')
            ->select(
                'elementos.*',
                'planillas.codigo as planilla_codigo',
                'maquinas.nombre as maquina_nombre'
            )
            ->leftJoin('planillas', 'elementos.planilla_id', '=', 'planillas.id')
            ->leftJoin('maquinas', 'elementos.maquina_id', '=', 'maquinas.id')
            ->where('elementos.elaborado', 1)
            ->whereNull('elementos.deleted_at');

        if (!empty($contexto['codigo_planilla'])) {
            $query->where('planillas.codigo', 'LIKE', '%' . $contexto['codigo_planilla'] . '%');
        }

        $tiempoAtras = $contexto['tiempo_atras'] ?? ['cantidad' => 2, 'unidad' => 'hora'];
        $desde = match ($tiempoAtras['unidad']) {
            'minuto' => now()->subMinutes($tiempoAtras['cantidad']),
            'hora' => now()->subHours($tiempoAtras['cantidad']),
            default => now()->subHours(2),
        };

        $elementosRecientes = $query
            ->where('elementos.updated_at', '>=', $desde)
            ->orderByDesc('elementos.updated_at')
            ->limit(15)
            ->get();

        if ($elementosRecientes->isEmpty()) {
            return [
                'encontrado' => false,
                'mensaje' => "No encontré elementos marcados como fabricados recientemente.\n\n¿Puedes indicarme el código de la planilla o más detalles?",
            ];
        }

        $elementos = $elementosRecientes->map(function ($e) {
            return [
                'id' => $e->id,
                'codigo' => $e->codigo,
                'planilla' => $e->planilla_codigo ?? 'N/A',
                'figura' => $e->figura,
                'diametro' => $e->diametro,
                'peso' => $e->peso,
                'maquina' => $e->maquina_nombre ?? 'N/A',
                'modificado' => Carbon::parse($e->updated_at)->diffForHumans(),
            ];
        })->toArray();

        return [
            'encontrado' => true,
            'tipo' => 'elemento_fabricado_error',
            'mensaje' => "Encontré los siguientes elementos marcados como fabricados recientemente:",
            'datos' => $elementos,
            'soluciones' => [
                [
                    'accion' => 'revertir_estado_elemento',
                    'descripcion' => 'Cambiar el estado del elemento a "pendiente"',
                    'parametros' => ['nuevo_estado' => 'pendiente'],
                ],
                [
                    'accion' => 'revertir_multiples_elementos',
                    'descripcion' => 'Revertir varios elementos a la vez',
                    'requiere_seleccion' => true,
                ],
            ],
        ];
    }

    /**
     * Diagnostica problemas con estados de planilla
     */
    protected function diagnosticarEstadoPlanilla(array $contexto, User $user): array
    {
        $query = DB::table('planillas')
            ->select('planillas.*', 'obras.obra as obra_nombre', 'clientes.empresa as cliente')
            ->leftJoin('obras', 'planillas.obra_id', '=', 'obras.id')
            ->leftJoin('clientes', 'planillas.cliente_id', '=', 'clientes.id')
            ->whereNull('planillas.deleted_at');

        if (!empty($contexto['codigo_planilla'])) {
            $query->where('planillas.codigo', 'LIKE', '%' . $contexto['codigo_planilla'] . '%');
        }

        $planillasRecientes = $query
            ->where('planillas.updated_at', '>=', now()->subHours(4))
            ->orderByDesc('planillas.updated_at')
            ->limit(10)
            ->get();

        if ($planillasRecientes->isEmpty()) {
            return [
                'encontrado' => false,
                'mensaje' => "No encontré planillas modificadas recientemente.\n\n¿Cuál es el código de la planilla que necesitas corregir?",
            ];
        }

        $planillas = $planillasRecientes->map(function ($p) {
            return [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'cliente' => $p->cliente ?? 'N/A',
                'obra' => $p->obra_nombre ?? 'N/A',
                'estado' => $p->estado,
                'peso_total' => $p->peso_total ?? 0,
                'modificado' => Carbon::parse($p->updated_at)->diffForHumans(),
            ];
        })->toArray();

        return [
            'encontrado' => true,
            'tipo' => 'planilla_estado_incorrecto',
            'mensaje' => "Encontré las siguientes planillas modificadas recientemente:",
            'datos' => $planillas,
            'soluciones' => [
                [
                    'accion' => 'cambiar_estado_planilla',
                    'descripcion' => 'Cambiar el estado de la planilla',
                    'estados_disponibles' => ['pendiente', 'fabricando', 'completada', 'pausada'],
                ],
            ],
        ];
    }

    /**
     * Diagnostica problemas con asignación de máquinas
     */
    protected function diagnosticarAsignacionMaquina(array $contexto, User $user): array
    {
        $elementos = DB::table('elementos')
            ->select(
                'elementos.*',
                'planillas.codigo as planilla_codigo',
                'maquinas.nombre as maquina_nombre',
                'maquinas.codigo as maquina_codigo'
            )
            ->leftJoin('planillas', 'elementos.planilla_id', '=', 'planillas.id')
            ->leftJoin('maquinas', 'elementos.maquina_id', '=', 'maquinas.id')
            ->whereNotNull('elementos.maquina_id')
            ->where('elementos.updated_at', '>=', now()->subHours(2))
            ->orderByDesc('elementos.updated_at')
            ->limit(20)
            ->get();

        if ($elementos->isEmpty()) {
            return [
                'encontrado' => false,
                'mensaje' => "No encontré asignaciones de máquina recientes.\n\n¿Puedes darme más detalles sobre la asignación incorrecta?",
            ];
        }

        // Agrupar por planilla y máquina
        $agrupados = $elementos->groupBy('planilla_codigo')->map(function ($grupo) {
            return [
                'planilla' => $grupo->first()->planilla_codigo,
                'maquina' => $grupo->first()->maquina_nombre,
                'cantidad_elementos' => $grupo->count(),
                'elementos' => $grupo->pluck('id')->toArray(),
            ];
        })->values()->toArray();

        return [
            'encontrado' => true,
            'tipo' => 'asignacion_maquina_error',
            'mensaje' => "Encontré las siguientes asignaciones de máquina recientes:",
            'datos' => $agrupados,
            'soluciones' => [
                [
                    'accion' => 'reasignar_maquina',
                    'descripcion' => 'Reasignar los elementos a otra máquina',
                ],
                [
                    'accion' => 'quitar_asignacion',
                    'descripcion' => 'Quitar la asignación de máquina (dejar sin asignar)',
                ],
            ],
        ];
    }

    /**
     * Diagnostica problemas con recepción de pedidos
     */
    protected function diagnosticarRecepcionPedido(array $contexto, User $user): array
    {
        $recepciones = DB::table('pedido_productos')
            ->select(
                'pedido_productos.*',
                'pedidos.codigo as pedido_codigo',
                'productos_base.descripcion as producto',
                'productos_base.diametro'
            )
            ->leftJoin('pedidos', 'pedido_productos.pedido_id', '=', 'pedidos.id')
            ->leftJoin('productos_base', 'pedido_productos.producto_base_id', '=', 'productos_base.id')
            ->where('pedido_productos.cantidad_recepcionada', '>', 0)
            ->where('pedido_productos.updated_at', '>=', now()->subHours(4))
            ->orderByDesc('pedido_productos.updated_at')
            ->limit(15)
            ->get();

        if ($recepciones->isEmpty()) {
            return [
                'encontrado' => false,
                'mensaje' => "No encontré recepciones de pedido recientes.\n\n¿Cuál es el código del pedido que necesitas corregir?",
            ];
        }

        $datos = $recepciones->map(function ($r) {
            return [
                'id' => $r->id,
                'pedido' => $r->pedido_codigo ?? 'N/A',
                'producto' => $r->producto ?? 'N/A',
                'diametro' => $r->diametro,
                'cantidad_pedida' => $r->cantidad,
                'cantidad_recepcionada' => $r->cantidad_recepcionada,
                'modificado' => Carbon::parse($r->updated_at)->diffForHumans(),
            ];
        })->toArray();

        return [
            'encontrado' => true,
            'tipo' => 'recepcion_pedido_error',
            'mensaje' => "Encontré las siguientes recepciones de pedido recientes:",
            'datos' => $datos,
            'soluciones' => [
                [
                    'accion' => 'revertir_recepcion',
                    'descripcion' => 'Poner la cantidad recepcionada a 0',
                ],
                [
                    'accion' => 'ajustar_recepcion',
                    'descripcion' => 'Ajustar la cantidad recepcionada a un valor específico',
                    'requiere_valor' => true,
                ],
            ],
        ];
    }

    /**
     * Diagnóstico general cuando no hay tipo específico
     */
    protected function diagnosticarGeneral(array $contexto, User $user): array
    {
        $entidad = $contexto['entidad_principal'] ?? null;

        $actividad = [];

        // Recopilar actividad reciente según la entidad
        if ($entidad === 'pedido' || !$entidad) {
            $pedidos = DB::table('pedido_productos')
                ->where('updated_at', '>=', now()->subHours(2))
                ->count();
            if ($pedidos > 0) {
                $actividad[] = "📦 {$pedidos} líneas de pedido modificadas";
            }
        }

        if ($entidad === 'planilla' || !$entidad) {
            $planillas = DB::table('planillas')
                ->where('updated_at', '>=', now()->subHours(2))
                ->count();
            if ($planillas > 0) {
                $actividad[] = "📋 {$planillas} planillas modificadas";
            }
        }

        if ($entidad === 'elemento' || !$entidad) {
            $elementos = DB::table('elementos')
                ->where('updated_at', '>=', now()->subHours(2))
                ->count();
            if ($elementos > 0) {
                $actividad[] = "🔧 {$elementos} elementos modificados";
            }
        }

        return [
            'encontrado' => true,
            'tipo' => 'problema_general',
            'mensaje' => "Veo actividad reciente en el sistema. Para ayudarte mejor, necesito saber:\n\n" .
                         "1. **¿Qué acción realizaste?** (activar, fabricar, mover, cambiar estado...)\n" .
                         "2. **¿Qué elemento se vio afectado?** (pedido, planilla, elemento...)\n" .
                         "3. **¿Hace cuánto tiempo ocurrió?**\n\n" .
                         "**Actividad reciente detectada:**\n" .
                         (empty($actividad) ? "- No hay cambios en las últimas 2 horas" : implode("\n", array_map(fn($a) => "- $a", $actividad))),
            'datos' => [],
            'soluciones' => [],
        ];
    }

    /**
     * Diagnostica problemas con movimientos de stock
     */
    protected function diagnosticarMovimientoStock(array $contexto, User $user): array
    {
        $tiempoAtras = $contexto['tiempo_atras'] ?? ['cantidad' => 2, 'unidad' => 'hora'];
        $desde = match ($tiempoAtras['unidad']) {
            'minuto' => now()->subMinutes($tiempoAtras['cantidad']),
            'hora' => now()->subHours($tiempoAtras['cantidad']),
            'día', 'dia' => now()->subDays($tiempoAtras['cantidad']),
            default => now()->subHours(2),
        };

        // Buscar movimientos de stock recientes
        $movimientos = DB::table('movimientos')
            ->select(
                'movimientos.*',
                'productos_base.descripcion as producto',
                'productos_base.diametro',
                'nave_origen.nombre as nave_origen_nombre',
                'nave_destino.nombre as nave_destino_nombre'
            )
            ->leftJoin('productos_base', 'movimientos.producto_base_id', '=', 'productos_base.id')
            ->leftJoin('naves as nave_origen', 'movimientos.nave_origen_id', '=', 'nave_origen.id')
            ->leftJoin('naves as nave_destino', 'movimientos.nave_destino_id', '=', 'nave_destino.id')
            ->where('movimientos.created_at', '>=', $desde)
            ->orderByDesc('movimientos.created_at')
            ->limit(15)
            ->get();

        if ($movimientos->isEmpty()) {
            return [
                'encontrado' => false,
                'mensaje' => "No encontré movimientos de stock recientes.\n\n¿Puedes indicarme qué material moviste o más detalles?",
            ];
        }

        $datos = $movimientos->map(function ($m) {
            return [
                'id' => $m->id,
                'producto' => $m->producto ?? 'N/A',
                'diametro' => $m->diametro ?? 'N/A',
                'cantidad' => $m->cantidad ?? 0,
                'tipo' => $m->tipo ?? 'N/A',
                'origen' => $m->nave_origen_nombre ?? 'N/A',
                'destino' => $m->nave_destino_nombre ?? 'N/A',
                'fecha' => Carbon::parse($m->created_at)->diffForHumans(),
            ];
        })->toArray();

        return [
            'encontrado' => true,
            'tipo' => 'movimiento_stock_error',
            'mensaje' => "Encontré los siguientes movimientos de stock recientes:",
            'datos' => $datos,
            'soluciones' => [
                [
                    'accion' => 'revertir_movimiento',
                    'descripcion' => 'Revertir el movimiento (crear movimiento inverso)',
                ],
                [
                    'accion' => 'ajustar_cantidad',
                    'descripcion' => 'Ajustar la cantidad del movimiento',
                    'requiere_valor' => true,
                ],
            ],
        ];
    }

    /**
     * Ejecuta una corrección específica
     */
    public function ejecutarCorreccion(string $accion, array $parametros, User $user): array
    {
        try {
            DB::beginTransaction();

            $resultado = match ($accion) {
                'revertir_estado_linea' => $this->revertirEstadoLinea($parametros),
                'revertir_cantidad_recepcionada' => $this->revertirCantidadRecepcionada($parametros),
                'revertir_estado_elemento' => $this->revertirEstadoElemento($parametros),
                'revertir_multiples_elementos' => $this->revertirMultiplesElementos($parametros),
                'cambiar_estado_planilla' => $this->corregirEstadoPlanilla($parametros),
                'reasignar_maquina' => $this->reasignarMaquina($parametros),
                'quitar_asignacion' => $this->quitarAsignacionMaquina($parametros),
                'revertir_recepcion' => $this->revertirRecepcion($parametros),
                'ajustar_recepcion' => $this->ajustarRecepcion($parametros),
                default => ['success' => false, 'mensaje' => 'Acción no reconocida'],
            };

            if ($resultado['success']) {
                DB::commit();
                Log::info("Corrección ejecutada por usuario {$user->id}: {$accion}", $parametros);
            } else {
                DB::rollBack();
            }

            return $resultado;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error ejecutando corrección {$accion}: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error al ejecutar la corrección: ' . $e->getMessage(),
            ];
        }
    }

    protected function revertirEstadoLinea(array $params): array
    {
        $id = $params['linea_id'] ?? null;
        $nuevoEstado = $params['nuevo_estado'] ?? 'pendiente';

        if (!$id) {
            return ['success' => false, 'mensaje' => 'No se especificó la línea de pedido'];
        }

        $linea = DB::table('pedido_productos')->where('id', $id)->first();
        if (!$linea) {
            return ['success' => false, 'mensaje' => 'Línea de pedido no encontrada'];
        }

        $estadoAnterior = $linea->estado;
        DB::table('pedido_productos')->where('id', $id)->update([
            'estado' => $nuevoEstado,
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'mensaje' => "Línea de pedido #{$id} actualizada: estado cambiado de '{$estadoAnterior}' a '{$nuevoEstado}'",
        ];
    }

    protected function revertirCantidadRecepcionada(array $params): array
    {
        $id = $params['linea_id'] ?? null;
        $nuevaCantidad = $params['cantidad'] ?? 0;

        if (!$id) {
            return ['success' => false, 'mensaje' => 'No se especificó la línea de pedido'];
        }

        $linea = DB::table('pedido_productos')->where('id', $id)->first();
        if (!$linea) {
            return ['success' => false, 'mensaje' => 'Línea de pedido no encontrada'];
        }

        $cantidadAnterior = $linea->cantidad_recepcionada;
        DB::table('pedido_productos')->where('id', $id)->update([
            'cantidad_recepcionada' => $nuevaCantidad,
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'mensaje' => "Línea de pedido #{$id}: cantidad recepcionada cambiada de {$cantidadAnterior} a {$nuevaCantidad} kg",
        ];
    }

    protected function revertirEstadoElemento(array $params): array
    {
        $id = $params['elemento_id'] ?? null;
        $nuevoEstado = $params['nuevo_estado'] ?? 'pendiente';

        if (!$id) {
            return ['success' => false, 'mensaje' => 'No se especificó el elemento'];
        }

        // Convertir estado textual a elaborado (0=pendiente, 1=fabricado)
        $elaborado = in_array($nuevoEstado, ['fabricado', 'completado']) ? 1 : 0;

        DB::table('elementos')->where('id', $id)->update([
            'elaborado' => $elaborado,
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'mensaje' => "Elemento #{$id} revertido a elaborado={$elaborado}",
        ];
    }

    protected function revertirMultiplesElementos(array $params): array
    {
        $ids = $params['elemento_ids'] ?? [];
        $nuevoEstado = $params['nuevo_estado'] ?? 'pendiente';

        if (empty($ids)) {
            return ['success' => false, 'mensaje' => 'No se especificaron elementos'];
        }

        // Convertir estado textual a elaborado (0=pendiente, 1=fabricado)
        $elaborado = in_array($nuevoEstado, ['fabricado', 'completado']) ? 1 : 0;

        $count = DB::table('elementos')->whereIn('id', $ids)->update([
            'elaborado' => $elaborado,
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'mensaje' => "{$count} elementos revertidos a elaborado={$elaborado}",
        ];
    }

    protected function corregirEstadoPlanilla(array $params): array
    {
        $id = $params['planilla_id'] ?? null;
        $nuevoEstado = $params['nuevo_estado'] ?? 'pendiente';

        if (!$id) {
            return ['success' => false, 'mensaje' => 'No se especificó la planilla'];
        }

        $planilla = DB::table('planillas')->where('id', $id)->first();
        if (!$planilla) {
            return ['success' => false, 'mensaje' => 'Planilla no encontrada'];
        }

        $estadoAnterior = $planilla->estado;
        DB::table('planillas')->where('id', $id)->update([
            'estado' => $nuevoEstado,
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'mensaje' => "Planilla {$planilla->codigo}: estado cambiado de '{$estadoAnterior}' a '{$nuevoEstado}'",
        ];
    }

    protected function reasignarMaquina(array $params): array
    {
        $elementoIds = $params['elemento_ids'] ?? [];
        $nuevaMaquinaId = $params['maquina_id'] ?? null;

        if (empty($elementoIds)) {
            return ['success' => false, 'mensaje' => 'No se especificaron elementos'];
        }

        $count = DB::table('elementos')->whereIn('id', $elementoIds)->update([
            'maquina_id' => $nuevaMaquinaId,
            'updated_at' => now(),
        ]);

        $maquina = $nuevaMaquinaId
            ? DB::table('maquinas')->where('id', $nuevaMaquinaId)->value('nombre')
            : 'sin asignar';

        return [
            'success' => true,
            'mensaje' => "{$count} elementos reasignados a: {$maquina}",
        ];
    }

    protected function quitarAsignacionMaquina(array $params): array
    {
        return $this->reasignarMaquina(array_merge($params, ['maquina_id' => null]));
    }

    protected function revertirRecepcion(array $params): array
    {
        return $this->revertirCantidadRecepcionada(array_merge($params, ['cantidad' => 0]));
    }

    protected function ajustarRecepcion(array $params): array
    {
        return $this->revertirCantidadRecepcionada($params);
    }

    /**
     * Formatea el diagnóstico para mostrar en el chat
     */
    public function formatearDiagnostico(array $diagnostico, ?array $problemaOriginal = null): string
    {
        if (!$diagnostico['encontrado']) {
            return "<div style=\"background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:16px;margin:8px 0;\">\n" .
                   "<strong style=\"color:#92400e;\">🔍 No encontré el problema</strong>\n" .
                   "<p style=\"margin:8px 0 0 0;color:#78350f;\">{$diagnostico['mensaje']}</p>\n" .
                   "</div>";
        }

        // Si es una respuesta de clarificación
        if (($diagnostico['tipo'] ?? '') === 'clarificacion') {
            return "<div style=\"background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:16px;margin:8px 0;\">\n" .
                   "<div style=\"white-space:pre-wrap;color:#78350f;\">" . nl2br(htmlspecialchars($diagnostico['mensaje'])) . "</div>\n" .
                   "</div>";
        }

        $html = "<div class=\"diagnostico-container\">\n";
        $html .= "<div style=\"background:#dbeafe;border:1px solid #3b82f6;border-radius:8px;padding:16px;margin:8px 0;\">\n";

        // Mostrar información del análisis de IA si está disponible
        $analisisIA = $problemaOriginal['analisis_ia'] ?? $problemaOriginal['analisis_completo'] ?? null;
        $confianza = $problemaOriginal['confianza'] ?? ($analisisIA['confianza'] ?? 0);

        if ($analisisIA || $confianza) {
            $colorConfianza = match(true) {
                $confianza >= 70 => '#16a34a',
                $confianza >= 45 => '#ca8a04',
                default => '#dc2626',
            };

            $html .= "<div style=\"display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;\">\n";
            $html .= "<h4 style=\"margin:0;color:#1e40af;\">🔍 Diagnóstico</h4>\n";
            $html .= "<span style=\"background:{$colorConfianza};color:white;padding:2px 8px;border-radius:12px;font-size:0.75rem;\">Confianza: {$confianza}%</span>\n";
            $html .= "</div>\n";

            // Mostrar comprensión de la IA
            if (!empty($analisisIA['comprension'])) {
                $html .= "<div style=\"background:#eff6ff;padding:8px 12px;border-radius:4px;margin-bottom:12px;font-size:0.875rem;\">\n";
                $html .= "<strong>Entendí:</strong> " . htmlspecialchars($analisisIA['comprension']);
                $html .= "\n</div>\n";
            }
        } else {
            $html .= "<h4 style=\"margin:0 0 12px 0;color:#1e40af;\">🔍 Diagnóstico</h4>\n";
        }

        $html .= "<p style=\"margin:0 0 12px 0;color:#1e3a8a;\">{$diagnostico['mensaje']}</p>\n";

        // Mostrar datos encontrados
        if (!empty($diagnostico['datos'])) {
            $html .= "<div style=\"overflow-x:auto;margin:12px 0;\">\n";
            $html .= "<table style=\"width:100%;border-collapse:collapse;font-size:0.875rem;\">\n";

            // Headers basados en el primer elemento
            $headers = array_keys($diagnostico['datos'][0]);
            $html .= "<thead><tr style=\"background:#eff6ff;\">\n";
            foreach ($headers as $h) {
                $label = ucfirst(str_replace('_', ' ', $h));
                $html .= "<th style=\"padding:8px 12px;text-align:left;font-weight:600;color:#1e40af;border-bottom:2px solid #93c5fd;\">{$label}</th>\n";
            }
            $html .= "</tr></thead>\n<tbody>\n";

            foreach ($diagnostico['datos'] as $i => $row) {
                $bg = $i % 2 === 0 ? '#ffffff' : '#f8fafc';
                $html .= "<tr style=\"background:{$bg};\">\n";
                foreach ($row as $value) {
                    if (is_array($value)) {
                        $value = count($value) . ' items';
                    }
                    $html .= "<td style=\"padding:8px 12px;border-bottom:1px solid #e2e8f0;color:#334155;\">{$value}</td>\n";
                }
                $html .= "</tr>\n";
            }
            $html .= "</tbody></table>\n</div>\n";
        }

        // Mostrar soluciones disponibles
        if (!empty($diagnostico['soluciones'])) {
            $html .= "<div style=\"margin-top:16px;padding-top:12px;border-top:1px solid #93c5fd;\">\n";
            $html .= "<strong style=\"color:#1e40af;\">💡 Soluciones disponibles:</strong>\n";
            $html .= "<ul style=\"margin:8px 0;padding-left:20px;color:#334155;\">\n";
            foreach ($diagnostico['soluciones'] as $sol) {
                $html .= "<li style=\"margin:4px 0;\">{$sol['descripcion']}</li>\n";
            }
            $html .= "</ul>\n";
            $html .= "<p style=\"font-size:0.875rem;color:#64748b;margin-top:12px;\">Dime qué solución quieres aplicar y sobre qué elemento (puedes usar el ID de la tabla).</p>\n";
            $html .= "</div>\n";
        }

        $html .= "</div>\n</div>";
        return $html;
    }
}
