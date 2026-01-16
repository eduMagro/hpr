<?php

namespace App\Services\Asistente;

use Illuminate\Support\Str;

/**
 * Analizador Semántico Inteligente
 *
 * Analiza mensajes de usuario considerando múltiples factores:
 * - Intención del mensaje (reportar error, pedir ayuda, consultar, etc.)
 * - Entidades mencionadas (pedidos, planillas, elementos, etc.)
 * - Acciones descritas (activar, fabricar, mover, etc.)
 * - Contexto temporal
 * - Nivel de urgencia/importancia
 *
 * Usa un sistema de puntuación ponderada en lugar de simple coincidencia de palabras.
 */
class AnalizadorSemanticoService
{
    /**
     * Resultado del análisis
     */
    protected array $analisis = [];

    /**
     * Vocabulario de intenciones con sus indicadores y pesos
     */
    protected const INTENCIONES = [
        'reportar_error' => [
            'indicadores' => [
                'me equivoqué' => 10,
                'me equivoque' => 10,
                'cometí un error' => 10,
                'hice mal' => 9,
                'por error' => 9,
                'sin querer' => 8,
                'accidentalmente' => 8,
                'no debería' => 7,
                'no deberia' => 7,
                'está mal' => 7,
                'esta mal' => 7,
                'incorrecto' => 6,
                'equivocado' => 6,
                'mal' => 3,
                'error' => 4,
            ],
            'contexto_requerido' => ['accion_pasada'],
        ],
        'solicitar_reversion' => [
            'indicadores' => [
                'revertir' => 10,
                'deshacer' => 10,
                'volver atrás' => 9,
                'volver atras' => 9,
                'anular' => 8,
                'cancelar' => 7,
                'quitar' => 5,
                'eliminar' => 5,
                'borrar' => 5,
                'restaurar' => 7,
                'recuperar' => 6,
                'como estaba' => 8,
                'como antes' => 7,
            ],
            'contexto_requerido' => [],
        ],
        'pedir_ayuda' => [
            'indicadores' => [
                'cómo puedo' => 8,
                'como puedo' => 8,
                'cómo hago' => 8,
                'como hago' => 8,
                'ayuda' => 6,
                'ayúdame' => 7,
                'ayudame' => 7,
                'necesito' => 5,
                'qué hago' => 7,
                'que hago' => 7,
                'puedes' => 4,
                'podrías' => 5,
                'podrias' => 5,
                'es posible' => 5,
            ],
            'contexto_requerido' => [],
        ],
        'corregir' => [
            'indicadores' => [
                'corregir' => 9,
                'arreglar' => 8,
                'solucionar' => 8,
                'reparar' => 7,
                'ajustar' => 6,
                'modificar' => 5,
                'cambiar' => 4,
                'actualizar' => 4,
            ],
            'contexto_requerido' => [],
        ],
        'consultar' => [
            'indicadores' => [
                'qué pasó' => 7,
                'que paso' => 7,
                'qué ocurrió' => 7,
                'que ocurrio' => 7,
                'por qué' => 6,
                'porque' => 3,
                'cuál es' => 5,
                'cual es' => 5,
                'dónde está' => 5,
                'donde esta' => 5,
                'muéstrame' => 6,
                'muestrame' => 6,
                'ver' => 3,
                'mostrar' => 4,
            ],
            'contexto_requerido' => [],
        ],
    ];

    /**
     * Vocabulario de entidades del sistema
     */
    protected const ENTIDADES = [
        'linea_pedido' => [
            'terminos' => [
                'línea de pedido' => 10,
                'linea de pedido' => 10,
                'línea del pedido' => 10,
                'linea del pedido' => 10,
                'línea' => 5,
                'linea' => 5,
            ],
            'contexto' => ['pedido', 'producto', 'cantidad'],
        ],
        'pedido' => [
            'terminos' => [
                'pedido' => 8,
                'pedidos' => 8,
                'orden de compra' => 7,
                'compra' => 4,
            ],
            'contexto' => ['proveedor', 'material', 'recepción'],
        ],
        'elemento' => [
            'terminos' => [
                'elemento' => 8,
                'elementos' => 8,
                'pieza' => 6,
                'piezas' => 6,
                'barra' => 5,
                'barras' => 5,
                'figura' => 5,
            ],
            'contexto' => ['planilla', 'fabricar', 'máquina'],
        ],
        'planilla' => [
            'terminos' => [
                'planilla' => 9,
                'planillas' => 9,
                'hoja de corte' => 7,
                'orden de fabricación' => 7,
            ],
            'contexto' => ['elementos', 'fabricar', 'cliente', 'obra'],
        ],
        'maquina' => [
            'terminos' => [
                'máquina' => 8,
                'maquina' => 8,
                'máquinas' => 8,
                'maquinas' => 8,
                'equipo' => 5,
                'cortadora' => 6,
                'dobladora' => 6,
                'estribadora' => 6,
            ],
            'contexto' => ['asignar', 'fabricar', 'producción'],
        ],
        'stock' => [
            'terminos' => [
                'stock' => 9,
                'inventario' => 8,
                'material' => 6,
                'materiales' => 6,
                'existencias' => 7,
                'almacén' => 6,
                'almacen' => 6,
                'nave' => 5,
            ],
            'contexto' => ['cantidad', 'mover', 'transferir'],
        ],
        'recepcion' => [
            'terminos' => [
                'recepción' => 9,
                'recepcion' => 9,
                'recepcionar' => 9,
                'recepcioné' => 9,
                'recepcione' => 9,
                'recibir' => 6,
                'recibí' => 7,
                'recibi' => 7,
                'entrada' => 5,
            ],
            'contexto' => ['pedido', 'cantidad', 'material'],
        ],
    ];

    /**
     * Vocabulario de acciones
     */
    protected const ACCIONES = [
        'activar' => [
            'terminos' => [
                'activar' => 9,
                'activé' => 10,
                'active' => 10,
                'activado' => 8,
                'habilitar' => 7,
                'habilitado' => 6,
                'poner activo' => 8,
            ],
            'opuesto' => 'desactivar',
        ],
        'desactivar' => [
            'terminos' => [
                'desactivar' => 9,
                'desactivé' => 10,
                'desactive' => 10,
                'desactivado' => 8,
                'deshabilitar' => 7,
                'quitar' => 5,
            ],
            'opuesto' => 'activar',
        ],
        'fabricar' => [
            'terminos' => [
                'fabricar' => 9,
                'fabricado' => 9,
                'fabriqué' => 10,
                'fabrique' => 10,
                'marcar como fabricado' => 10,
                'marqué como fabricado' => 10,
                'marque como fabricado' => 10,
                'producir' => 6,
                'producido' => 6,
            ],
            'opuesto' => 'pendiente',
        ],
        'asignar' => [
            'terminos' => [
                'asignar' => 9,
                'asigné' => 10,
                'asigne' => 10,
                'asignado' => 8,
                'asignación' => 8,
                'asignacion' => 8,
                'poner en' => 5,
                'mandar a' => 5,
            ],
            'opuesto' => 'desasignar',
        ],
        'recepcionar' => [
            'terminos' => [
                'recepcionar' => 9,
                'recepcioné' => 10,
                'recepcione' => 10,
                'recepcionado' => 8,
                'recibir' => 6,
                'recibido' => 6,
                'dar entrada' => 7,
            ],
            'opuesto' => null,
        ],
        'mover' => [
            'terminos' => [
                'mover' => 8,
                'moví' => 9,
                'movi' => 9,
                'movido' => 7,
                'transferir' => 8,
                'transferí' => 9,
                'transferido' => 7,
                'trasladar' => 7,
                'cambiar de nave' => 8,
                'pasar a' => 5,
            ],
            'opuesto' => null,
        ],
        'cambiar_estado' => [
            'terminos' => [
                'cambiar estado' => 9,
                'cambié el estado' => 10,
                'cambie el estado' => 10,
                'cambiar a' => 6,
                'poner como' => 6,
                'poner en estado' => 8,
                'marcar como' => 7,
            ],
            'opuesto' => null,
        ],
        'eliminar' => [
            'terminos' => [
                'eliminar' => 9,
                'eliminé' => 10,
                'elimine' => 10,
                'eliminado' => 8,
                'borrar' => 8,
                'borré' => 9,
                'borre' => 9,
                'quitar' => 6,
                'quité' => 7,
            ],
            'opuesto' => 'restaurar',
        ],
    ];

    /**
     * Indicadores temporales
     */
    protected const TEMPORALES = [
        'muy_reciente' => [
            'indicadores' => [
                'ahora mismo' => 10,
                'recién' => 9,
                'hace un momento' => 9,
                'hace nada' => 9,
                'acabo de' => 10,
                'ahora' => 7,
            ],
            'minutos' => 15,
        ],
        'reciente' => [
            'indicadores' => [
                'hace poco' => 8,
                'hace un rato' => 7,
                'hace unos minutos' => 8,
                'hoy' => 6,
                'esta mañana' => 7,
                'esta tarde' => 7,
            ],
            'minutos' => 120,
        ],
        'hoy' => [
            'indicadores' => [
                'hoy' => 7,
                'esta mañana' => 7,
                'esta tarde' => 7,
                'hace unas horas' => 6,
            ],
            'minutos' => 480,
        ],
        'reciente_dias' => [
            'indicadores' => [
                'ayer' => 7,
                'hace unos días' => 6,
                'hace unos dias' => 6,
                'la semana pasada' => 5,
                'el otro día' => 6,
                'el otro dia' => 6,
            ],
            'minutos' => 10080,
        ],
    ];

    /**
     * Analiza un mensaje completo y devuelve un análisis estructurado
     */
    public function analizar(string $mensaje): array
    {
        $mensajeNormalizado = $this->normalizar($mensaje);
        $mensajeOriginal = $mensaje;

        $this->analisis = [
            'mensaje_original' => $mensajeOriginal,
            'mensaje_normalizado' => $mensajeNormalizado,
            'intenciones' => $this->detectarIntenciones($mensajeNormalizado),
            'entidades' => $this->detectarEntidades($mensajeNormalizado),
            'acciones' => $this->detectarAcciones($mensajeNormalizado),
            'temporal' => $this->detectarContextoTemporal($mensajeNormalizado),
            'codigos' => $this->extraerCodigos($mensajeOriginal),
            'cantidades' => $this->extraerCantidades($mensajeOriginal),
            'negaciones' => $this->detectarNegaciones($mensajeNormalizado),
        ];

        // Calcular el tipo de problema más probable
        $this->analisis['problema_detectado'] = $this->inferirProblema();

        // Calcular nivel de confianza general
        $this->analisis['confianza'] = $this->calcularConfianza();

        // Determinar si necesita más información
        $this->analisis['requiere_clarificacion'] = $this->necesitaClarificacion();

        return $this->analisis;
    }

    /**
     * Normaliza el mensaje para análisis
     */
    protected function normalizar(string $mensaje): string
    {
        $mensaje = mb_strtolower($mensaje);
        // Mantener acentos para mejor detección
        $mensaje = preg_replace('/\s+/', ' ', $mensaje);
        return trim($mensaje);
    }

    /**
     * Detecta las intenciones del usuario con puntuación
     */
    protected function detectarIntenciones(string $mensaje): array
    {
        $resultados = [];

        foreach (self::INTENCIONES as $intencion => $config) {
            $puntuacion = 0;
            $coincidencias = [];

            foreach ($config['indicadores'] as $indicador => $peso) {
                if ($this->contieneExpresion($mensaje, $indicador)) {
                    $puntuacion += $peso;
                    $coincidencias[] = $indicador;
                }
            }

            if ($puntuacion > 0) {
                $resultados[$intencion] = [
                    'puntuacion' => $puntuacion,
                    'coincidencias' => $coincidencias,
                    'confianza' => min(100, $puntuacion * 5),
                ];
            }
        }

        // Ordenar por puntuación
        uasort($resultados, fn($a, $b) => $b['puntuacion'] <=> $a['puntuacion']);

        return $resultados;
    }

    /**
     * Detecta entidades mencionadas en el mensaje
     */
    protected function detectarEntidades(string $mensaje): array
    {
        $resultados = [];

        foreach (self::ENTIDADES as $entidad => $config) {
            $puntuacion = 0;
            $coincidencias = [];

            foreach ($config['terminos'] as $termino => $peso) {
                if ($this->contieneExpresion($mensaje, $termino)) {
                    $puntuacion += $peso;
                    $coincidencias[] = $termino;
                }
            }

            // Bonus por contexto relacionado
            foreach ($config['contexto'] as $ctx) {
                if (stripos($mensaje, $ctx) !== false) {
                    $puntuacion += 2;
                }
            }

            if ($puntuacion > 0) {
                $resultados[$entidad] = [
                    'puntuacion' => $puntuacion,
                    'coincidencias' => $coincidencias,
                    'confianza' => min(100, $puntuacion * 6),
                ];
            }
        }

        uasort($resultados, fn($a, $b) => $b['puntuacion'] <=> $a['puntuacion']);

        return $resultados;
    }

    /**
     * Detecta acciones mencionadas
     */
    protected function detectarAcciones(string $mensaje): array
    {
        $resultados = [];

        foreach (self::ACCIONES as $accion => $config) {
            $puntuacion = 0;
            $coincidencias = [];

            foreach ($config['terminos'] as $termino => $peso) {
                if ($this->contieneExpresion($mensaje, $termino)) {
                    $puntuacion += $peso;
                    $coincidencias[] = $termino;
                }
            }

            if ($puntuacion > 0) {
                $resultados[$accion] = [
                    'puntuacion' => $puntuacion,
                    'coincidencias' => $coincidencias,
                    'opuesto' => $config['opuesto'],
                    'confianza' => min(100, $puntuacion * 5),
                ];
            }
        }

        uasort($resultados, fn($a, $b) => $b['puntuacion'] <=> $a['puntuacion']);

        return $resultados;
    }

    /**
     * Detecta contexto temporal
     */
    protected function detectarContextoTemporal(string $mensaje): array
    {
        $resultado = [
            'tipo' => 'no_especificado',
            'minutos_atras' => 120, // Por defecto 2 horas
            'coincidencias' => [],
            'confianza' => 30,
        ];

        // Detectar expresiones específicas de tiempo
        if (preg_match('/hace\s+(\d+)\s+(minuto|hora|día|dia|semana)/iu', $mensaje, $m)) {
            $cantidad = (int)$m[1];
            $unidad = mb_strtolower($m[2]);

            $multiplicador = match(true) {
                str_contains($unidad, 'minuto') => 1,
                str_contains($unidad, 'hora') => 60,
                str_contains($unidad, 'día') || str_contains($unidad, 'dia') => 1440,
                str_contains($unidad, 'semana') => 10080,
                default => 60,
            };

            $resultado['tipo'] = 'especifico';
            $resultado['minutos_atras'] = $cantidad * $multiplicador;
            $resultado['coincidencias'][] = $m[0];
            $resultado['confianza'] = 90;
            return $resultado;
        }

        // Buscar indicadores generales
        $mejorCoincidencia = null;
        $mejorPuntuacion = 0;

        foreach (self::TEMPORALES as $tipo => $config) {
            foreach ($config['indicadores'] as $indicador => $peso) {
                if ($this->contieneExpresion($mensaje, $indicador) && $peso > $mejorPuntuacion) {
                    $mejorPuntuacion = $peso;
                    $mejorCoincidencia = [
                        'tipo' => $tipo,
                        'minutos' => $config['minutos'],
                        'indicador' => $indicador,
                    ];
                }
            }
        }

        if ($mejorCoincidencia) {
            $resultado['tipo'] = $mejorCoincidencia['tipo'];
            $resultado['minutos_atras'] = $mejorCoincidencia['minutos'];
            $resultado['coincidencias'][] = $mejorCoincidencia['indicador'];
            $resultado['confianza'] = 70;
        }

        return $resultado;
    }

    /**
     * Extrae códigos mencionados (pedidos, planillas, etc.)
     */
    protected function extraerCodigos(string $mensaje): array
    {
        $codigos = [];

        // Códigos de pedido (PED-123, P-123, pedido 123)
        if (preg_match_all('/(?:ped(?:ido)?|p)[- ]?(\d{3,})/iu', $mensaje, $m)) {
            foreach ($m[1] as $codigo) {
                $codigos['pedido'][] = $codigo;
            }
        }

        // Códigos de planilla (PLAN-123, planilla 123, 4 dígitos solos en contexto)
        if (preg_match_all('/(?:planilla|plan)[- ]?(\d{3,})/iu', $mensaje, $m)) {
            foreach ($m[1] as $codigo) {
                $codigos['planilla'][] = $codigo;
            }
        }

        // Números de línea
        if (preg_match_all('/l[ií]nea[- ]?(\d+)/iu', $mensaje, $m)) {
            foreach ($m[1] as $num) {
                $codigos['linea'][] = $num;
            }
        }

        // Diámetros
        if (preg_match_all('/(?:di[aá]metro|ø|d)\s*(\d+)/iu', $mensaje, $m)) {
            foreach ($m[1] as $d) {
                $codigos['diametro'][] = $d;
            }
        }

        // IDs genéricos mencionados
        if (preg_match_all('/\bid[- ]?(\d+)\b/iu', $mensaje, $m)) {
            foreach ($m[1] as $id) {
                $codigos['id'][] = $id;
            }
        }

        return $codigos;
    }

    /**
     * Extrae cantidades mencionadas
     */
    protected function extraerCantidades(string $mensaje): array
    {
        $cantidades = [];

        // Cantidades con unidades
        if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*(kg|kilos?|toneladas?|t|unidades?|uds?|metros?|m)\b/iu', $mensaje, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $cantidades[] = [
                    'valor' => floatval(str_replace(',', '.', $match[1])),
                    'unidad' => mb_strtolower($match[2]),
                ];
            }
        }

        return $cantidades;
    }

    /**
     * Detecta negaciones que pueden invertir el significado
     */
    protected function detectarNegaciones(string $mensaje): array
    {
        $negaciones = [];
        $patrones = [
            '/no\s+deb[ií]a/iu' => 'no debía',
            '/no\s+quer[ií]a/iu' => 'no quería',
            '/no\s+era/iu' => 'no era',
            '/no\s+es\s+correct/iu' => 'no es correcto',
            '/sin\s+querer/iu' => 'sin querer',
            '/por\s+accidente/iu' => 'por accidente',
            '/no\s+correspond[ií]a/iu' => 'no correspondía',
        ];

        foreach ($patrones as $patron => $etiqueta) {
            if (preg_match($patron, $mensaje)) {
                $negaciones[] = $etiqueta;
            }
        }

        return $negaciones;
    }

    /**
     * Verifica si el mensaje contiene una expresión (con tolerancia)
     */
    protected function contieneExpresion(string $mensaje, string $expresion): bool
    {
        // Primero búsqueda exacta
        if (stripos($mensaje, $expresion) !== false) {
            return true;
        }

        // Búsqueda con regex para variaciones
        $patron = str_replace(' ', '\s+', preg_quote($expresion, '/'));
        return (bool) preg_match("/{$patron}/iu", $mensaje);
    }

    /**
     * Infiere el tipo de problema basado en el análisis completo
     */
    protected function inferirProblema(): ?array
    {
        $intenciones = $this->analisis['intenciones'];
        $entidades = $this->analisis['entidades'];
        $acciones = $this->analisis['acciones'];
        $negaciones = $this->analisis['negaciones'];

        // Si no hay suficiente información
        if (empty($intenciones) && empty($acciones)) {
            return null;
        }

        // Determinar si es un reporte de error
        $esError = isset($intenciones['reportar_error']) ||
                   isset($intenciones['solicitar_reversion']) ||
                   !empty($negaciones);

        if (!$esError && !isset($intenciones['corregir']) && !isset($intenciones['pedir_ayuda'])) {
            return null;
        }

        // Mapear combinación de entidad + acción a tipo de problema
        $problemaInferido = $this->mapearProblema($entidades, $acciones);

        if ($problemaInferido) {
            return [
                'tipo' => $problemaInferido['tipo'],
                'descripcion' => $problemaInferido['descripcion'],
                'entidad_principal' => $problemaInferido['entidad'],
                'accion_detectada' => $problemaInferido['accion'],
                'confianza' => $problemaInferido['confianza'],
                'contexto' => [
                    'codigos' => $this->analisis['codigos'],
                    'cantidades' => $this->analisis['cantidades'],
                    'tiempo_atras' => $this->analisis['temporal'],
                ],
            ];
        }

        // Si hay intención pero no se pudo mapear exactamente
        if ($esError && !empty($entidades)) {
            $entidadPrincipal = array_key_first($entidades);
            return [
                'tipo' => 'problema_' . $entidadPrincipal,
                'descripcion' => 'Problema relacionado con ' . str_replace('_', ' ', $entidadPrincipal),
                'entidad_principal' => $entidadPrincipal,
                'accion_detectada' => array_key_first($acciones) ?? 'no_especificada',
                'confianza' => 50,
                'contexto' => [
                    'codigos' => $this->analisis['codigos'],
                    'cantidades' => $this->analisis['cantidades'],
                    'tiempo_atras' => $this->analisis['temporal'],
                ],
            ];
        }

        return null;
    }

    /**
     * Mapea combinaciones de entidad+acción a tipos de problemas específicos
     */
    protected function mapearProblema(array $entidades, array $acciones): ?array
    {
        $mapeo = [
            // Línea de pedido + activar = línea activada por error
            'linea_pedido' => [
                'activar' => ['tipo' => 'linea_pedido_activada', 'desc' => 'Línea de pedido activada por error'],
                'recepcionar' => ['tipo' => 'linea_pedido_recepcionada', 'desc' => 'Recepción de línea de pedido incorrecta'],
                'eliminar' => ['tipo' => 'linea_pedido_eliminada', 'desc' => 'Línea de pedido eliminada por error'],
            ],
            // Elemento + fabricar = elemento marcado fabricado por error
            'elemento' => [
                'fabricar' => ['tipo' => 'elemento_fabricado_error', 'desc' => 'Elemento marcado como fabricado por error'],
                'asignar' => ['tipo' => 'elemento_asignacion_error', 'desc' => 'Elemento asignado incorrectamente'],
                'cambiar_estado' => ['tipo' => 'elemento_estado_error', 'desc' => 'Estado de elemento incorrecto'],
                'eliminar' => ['tipo' => 'elemento_eliminado_error', 'desc' => 'Elemento eliminado por error'],
            ],
            // Planilla + cambiar_estado = estado de planilla incorrecto
            'planilla' => [
                'cambiar_estado' => ['tipo' => 'planilla_estado_incorrecto', 'desc' => 'Estado de planilla incorrecto'],
                'fabricar' => ['tipo' => 'planilla_fabricacion_error', 'desc' => 'Error en fabricación de planilla'],
                'asignar' => ['tipo' => 'planilla_asignacion_error', 'desc' => 'Asignación de planilla incorrecta'],
            ],
            // Máquina + asignar = asignación incorrecta
            'maquina' => [
                'asignar' => ['tipo' => 'asignacion_maquina_error', 'desc' => 'Asignación de máquina incorrecta'],
            ],
            // Recepción + recepcionar = error en recepción
            'recepcion' => [
                'recepcionar' => ['tipo' => 'recepcion_pedido_error', 'desc' => 'Error en recepción de pedido'],
                'default' => ['tipo' => 'recepcion_pedido_error', 'desc' => 'Error en recepción de pedido'],
            ],
            // Pedido + recepcionar
            'pedido' => [
                'recepcionar' => ['tipo' => 'recepcion_pedido_error', 'desc' => 'Error en recepción de pedido'],
                'activar' => ['tipo' => 'linea_pedido_activada', 'desc' => 'Línea de pedido activada por error'],
            ],
            // Stock + mover = movimiento incorrecto
            'stock' => [
                'mover' => ['tipo' => 'movimiento_stock_error', 'desc' => 'Movimiento de stock incorrecto'],
                'default' => ['tipo' => 'movimiento_stock_error', 'desc' => 'Problema con stock'],
            ],
        ];

        // Buscar la mejor combinación
        foreach ($entidades as $entidad => $entidadData) {
            if (!isset($mapeo[$entidad])) continue;

            foreach ($acciones as $accion => $accionData) {
                if (isset($mapeo[$entidad][$accion])) {
                    $confianza = min(100, ($entidadData['confianza'] + $accionData['confianza']) / 2 + 20);
                    return [
                        'tipo' => $mapeo[$entidad][$accion]['tipo'],
                        'descripcion' => $mapeo[$entidad][$accion]['desc'],
                        'entidad' => $entidad,
                        'accion' => $accion,
                        'confianza' => $confianza,
                    ];
                }
            }

            // Si hay entidad pero no acción específica, usar default si existe
            if (isset($mapeo[$entidad]['default'])) {
                return [
                    'tipo' => $mapeo[$entidad]['default']['tipo'],
                    'descripcion' => $mapeo[$entidad]['default']['desc'],
                    'entidad' => $entidad,
                    'accion' => 'no_especificada',
                    'confianza' => $entidadData['confianza'] * 0.7,
                ];
            }
        }

        return null;
    }

    /**
     * Calcula el nivel de confianza general del análisis
     */
    protected function calcularConfianza(): array
    {
        $factores = [];

        // Confianza por intenciones detectadas
        if (!empty($this->analisis['intenciones'])) {
            $mejorIntencion = reset($this->analisis['intenciones']);
            $factores['intencion'] = $mejorIntencion['confianza'];
        } else {
            $factores['intencion'] = 0;
        }

        // Confianza por entidades detectadas
        if (!empty($this->analisis['entidades'])) {
            $mejorEntidad = reset($this->analisis['entidades']);
            $factores['entidad'] = $mejorEntidad['confianza'];
        } else {
            $factores['entidad'] = 0;
        }

        // Confianza por acciones detectadas
        if (!empty($this->analisis['acciones'])) {
            $mejorAccion = reset($this->analisis['acciones']);
            $factores['accion'] = $mejorAccion['confianza'];
        } else {
            $factores['accion'] = 0;
        }

        // Confianza temporal
        $factores['temporal'] = $this->analisis['temporal']['confianza'];

        // Bonus por códigos específicos
        $factores['codigos'] = !empty($this->analisis['codigos']) ? 20 : 0;

        // Bonus por negaciones (indican error)
        $factores['negaciones'] = !empty($this->analisis['negaciones']) ? 15 : 0;

        // Calcular promedio ponderado
        $pesos = [
            'intencion' => 0.25,
            'entidad' => 0.25,
            'accion' => 0.20,
            'temporal' => 0.10,
            'codigos' => 0.10,
            'negaciones' => 0.10,
        ];

        $total = 0;
        foreach ($factores as $factor => $valor) {
            $total += $valor * ($pesos[$factor] ?? 0.1);
        }

        return [
            'general' => round($total),
            'factores' => $factores,
            'nivel' => match(true) {
                $total >= 70 => 'alto',
                $total >= 45 => 'medio',
                $total >= 25 => 'bajo',
                default => 'muy_bajo',
            },
        ];
    }

    /**
     * Determina si se necesita más información del usuario
     */
    protected function necesitaClarificacion(): array
    {
        $razones = [];

        // Sin intención clara
        if (empty($this->analisis['intenciones'])) {
            $razones[] = [
                'motivo' => 'sin_intencion',
                'pregunta' => '¿Qué necesitas hacer? ¿Corregir un error, deshacer un cambio, o consultar información?',
            ];
        }

        // Sin entidad clara
        if (empty($this->analisis['entidades'])) {
            $razones[] = [
                'motivo' => 'sin_entidad',
                'pregunta' => '¿Qué tipo de registro está afectado? (pedido, planilla, elemento, etc.)',
            ];
        }

        // Confianza baja
        $confianza = $this->analisis['confianza']['general'] ?? 0;
        if ($confianza < 40) {
            $razones[] = [
                'motivo' => 'confianza_baja',
                'pregunta' => '¿Podrías darme más detalles sobre qué ocurrió y qué necesitas corregir?',
            ];
        }

        // Sin códigos específicos cuando hay entidad
        if (!empty($this->analisis['entidades']) && empty($this->analisis['codigos'])) {
            $entidad = array_key_first($this->analisis['entidades']);
            $razones[] = [
                'motivo' => 'sin_codigo',
                'pregunta' => "¿Tienes el código o número del {$entidad} afectado?",
            ];
        }

        return [
            'necesita' => !empty($razones) && ($this->analisis['confianza']['general'] ?? 0) < 60,
            'razones' => $razones,
        ];
    }

    /**
     * Genera un resumen legible del análisis
     */
    public function generarResumen(): string
    {
        if (empty($this->analisis)) {
            return "No hay análisis disponible.";
        }

        $resumen = [];

        // Intención principal
        if (!empty($this->analisis['intenciones'])) {
            $intencion = array_key_first($this->analisis['intenciones']);
            $confianza = $this->analisis['intenciones'][$intencion]['confianza'];
            $resumen[] = "**Intención detectada:** " . str_replace('_', ' ', $intencion) . " ({$confianza}% confianza)";
        }

        // Entidad principal
        if (!empty($this->analisis['entidades'])) {
            $entidad = array_key_first($this->analisis['entidades']);
            $confianza = $this->analisis['entidades'][$entidad]['confianza'];
            $resumen[] = "**Entidad afectada:** " . str_replace('_', ' ', $entidad) . " ({$confianza}% confianza)";
        }

        // Acción
        if (!empty($this->analisis['acciones'])) {
            $accion = array_key_first($this->analisis['acciones']);
            $resumen[] = "**Acción:** " . str_replace('_', ' ', $accion);
        }

        // Problema inferido
        if (!empty($this->analisis['problema_detectado'])) {
            $problema = $this->analisis['problema_detectado'];
            $resumen[] = "**Problema:** {$problema['descripcion']} ({$problema['confianza']}% confianza)";
        }

        // Confianza general
        $confianza = $this->analisis['confianza'];
        $resumen[] = "**Confianza general:** {$confianza['general']}% ({$confianza['nivel']})";

        return implode("\n", $resumen);
    }
}
