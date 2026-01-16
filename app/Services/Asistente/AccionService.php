<?php

namespace App\Services\Asistente;

use App\Models\User;
use App\Models\Planilla;
use App\Models\Elemento;
use App\Models\Alerta;
use App\Models\Maquina;
use App\Models\AccionAsistente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class AccionService
{
    /**
     * Acciones disponibles organizadas por nivel de riesgo
     */
    public const ACCIONES = [
        // Nivel 1: Solo lectura/notificación - No requieren confirmación
        'enviar_alerta' => [
            'nombre' => 'Enviar Alerta',
            'descripcion' => 'Crea una alerta para uno o más usuarios',
            'nivel' => 1,
            'requiere_confirmacion' => false,
            'permisos' => ['crear_alertas'],
        ],
        'exportar_excel' => [
            'nombre' => 'Exportar a Excel',
            'descripcion' => 'Genera un archivo Excel con los datos solicitados',
            'nivel' => 1,
            'requiere_confirmacion' => false,
            'permisos' => [],
        ],

        // Nivel 2: Modificación de datos - Requieren confirmación
        'adelantar_planilla' => [
            'nombre' => 'Adelantar Planilla',
            'descripcion' => 'Mueve una planilla hacia adelante en la cola de producción',
            'nivel' => 2,
            'requiere_confirmacion' => true,
            'permisos' => ['gestionar_planillas'],
        ],
        'retrasar_planilla' => [
            'nombre' => 'Retrasar Planilla',
            'descripcion' => 'Mueve una planilla hacia atrás en la cola de producción',
            'nivel' => 2,
            'requiere_confirmacion' => true,
            'permisos' => ['gestionar_planillas'],
        ],
        'cambiar_estado_planilla' => [
            'nombre' => 'Cambiar Estado de Planilla',
            'descripcion' => 'Cambia el estado de una planilla (pendiente, fabricando, completada, etc.)',
            'nivel' => 2,
            'requiere_confirmacion' => true,
            'permisos' => ['gestionar_planillas'],
        ],
        'asignar_maquina' => [
            'nombre' => 'Asignar Máquina',
            'descripcion' => 'Asigna una planilla a una máquina específica',
            'nivel' => 2,
            'requiere_confirmacion' => true,
            'permisos' => ['gestionar_planillas'],
        ],
        'cambiar_prioridad' => [
            'nombre' => 'Cambiar Prioridad',
            'descripcion' => 'Cambia la prioridad de una planilla (alta, normal, baja)',
            'nivel' => 2,
            'requiere_confirmacion' => true,
            'permisos' => ['gestionar_planillas'],
        ],
    ];

    /**
     * Estado de acciones pendientes de confirmación (por usuario)
     */
    protected static array $accionesPendientes = [];

    /**
     * Detecta si un mensaje solicita una acción
     */
    public function detectarAccion(string $mensaje): ?array
    {
        $mensaje = strtolower($mensaje);

        $patrones = [
            'enviar_alerta' => [
                '/env[íi]a.*alerta/iu',
                '/crea.*alerta/iu',
                '/notifica.*a\s+\w/iu',
                '/avisa.*a\s+\w/iu',
                '/manda.*alerta/iu',
                '/alerta.*para/iu',
            ],
            'exportar_excel' => [
                '/export.*excel/iu',
                '/desc[aá]rga.*excel/iu',
                '/genera.*excel/iu',
                '/pas.*a.*excel/iu',
            ],
            'adelantar_planilla' => [
                '/adelant.*planilla/iu',
                '/sub.*planilla.*cola/iu',
                '/mover.*planilla.*arriba/iu',
                '/planilla.*primero/iu',
                '/prioriz.*planilla/iu',
            ],
            'retrasar_planilla' => [
                '/retras.*planilla/iu',
                '/baj.*planilla.*cola/iu',
                '/mover.*planilla.*abajo/iu',
                '/planilla.*[úu]ltimo/iu',
            ],
            'cambiar_estado_planilla' => [
                '/cambi.*estado.*planilla/iu',
                '/pon.*planilla.*en/iu',
                '/marc.*planilla.*como/iu',
                '/pas.*planilla.*a/iu',
            ],
            'asignar_maquina' => [
                '/asign.*planilla.*m[aá]quina/iu',
                '/pon.*planilla.*en.*m[aá]quina/iu',
                '/mand.*planilla.*a.*m[aá]quina/iu',
                '/m[aá]quina.*para.*planilla/iu',
            ],
            'cambiar_prioridad' => [
                '/cambi.*prioridad/iu',
                '/prioridad.*alta/iu',
                '/prioridad.*baja/iu',
                '/prioridad.*normal/iu',
                '/pon.*prioridad/iu',
            ],
        ];

        foreach ($patrones as $accion => $expresiones) {
            foreach ($expresiones as $patron) {
                if (preg_match($patron, $mensaje)) {
                    return [
                        'accion' => $accion,
                        'config' => self::ACCIONES[$accion],
                        'parametros' => $this->extraerParametros($mensaje, $accion),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Extrae parámetros del mensaje según el tipo de acción
     */
    protected function extraerParametros(string $mensaje, string $accion): array
    {
        $parametros = [];

        // Extraer código de planilla (formato: P-XXXX, PXXXX, planilla XXXX)
        if (preg_match('/(?:planilla\s*)?(?:P-?)?(\d{4,6})/i', $mensaje, $matches)) {
            $parametros['codigo_planilla'] = $matches[1];
        }

        // Extraer nombre/código de máquina
        if (preg_match('/m[aá]quina\s+([A-Z0-9\-]+)/i', $mensaje, $matches)) {
            $parametros['maquina'] = strtoupper($matches[1]);
        }

        // Extraer estado
        $estados = ['pendiente', 'fabricando', 'completada', 'pausada', 'cancelada'];
        foreach ($estados as $estado) {
            if (stripos($mensaje, $estado) !== false) {
                $parametros['estado'] = $estado;
                break;
            }
        }

        // Extraer prioridad
        if (preg_match('/prioridad\s+(alta|normal|baja)/i', $mensaje, $matches)) {
            $parametros['prioridad'] = strtolower($matches[1]);
        }

        // Extraer usuarios para alertas (solo si es una acción de alerta)
        if ($accion === 'enviar_alerta') {
            // Patrón más específico para destinatarios de alertas
            if (preg_match('/(?:alerta|notifica|avisa)\s+(?:a|para)\s+([A-Za-záéíóúÁÉÍÓÚñÑ\s,]+?)(?:\s+(?:diciendo|que diga|mensaje|con mensaje)|$)/iu', $mensaje, $matches)) {
                $destinatarios = preg_split('/[,y]\s*/i', trim($matches[1]));
                $parametros['destinatarios'] = array_filter(array_map('trim', $destinatarios));
            } elseif (preg_match('/(?:a|para)\s+([A-Za-záéíóúÁÉÍÓÚñÑ]+)/iu', $mensaje, $matches)) {
                $parametros['destinatarios'] = [trim($matches[1])];
            }

            // Extraer mensaje de alerta
            if (preg_match('/(?:diciendo|que diga|mensaje|con mensaje)[:\s]+"?(.+?)"?\s*$/iu', $mensaje, $matches)) {
                $parametros['mensaje_alerta'] = trim($matches[1], ' "\'');
            }
        }

        // Extraer posiciones para cola
        if (preg_match('/(\d+)\s*posicion/i', $mensaje, $matches)) {
            $parametros['posiciones'] = (int)$matches[1];
        }

        return $parametros;
    }

    /**
     * Prepara una acción para confirmación (sin ejecutar)
     */
    public function prepararAccion(string $accion, array $parametros, User $user): array
    {
        $config = self::ACCIONES[$accion] ?? null;
        if (!$config) {
            return [
                'success' => false,
                'error' => "Acción no reconocida: {$accion}",
            ];
        }

        // Validar permisos
        $validacionPermisos = $this->validarPermisos($user, $config['permisos']);
        if (!$validacionPermisos['permitido']) {
            return [
                'success' => false,
                'error' => $validacionPermisos['mensaje'],
            ];
        }

        // Validar parámetros según la acción
        $validacionParams = $this->validarParametros($accion, $parametros);
        if (!$validacionParams['valido']) {
            return [
                'success' => false,
                'error' => $validacionParams['mensaje'],
                'parametros_faltantes' => $validacionParams['faltantes'] ?? [],
            ];
        }

        // Simular el impacto de la acción
        $simulacion = $this->simularAccion($accion, $parametros);

        // Generar token de confirmación
        $token = bin2hex(random_bytes(16));

        // Guardar acción pendiente
        self::$accionesPendientes[$user->id] = [
            'token' => $token,
            'accion' => $accion,
            'parametros' => $parametros,
            'simulacion' => $simulacion,
            'timestamp' => now(),
            'expira' => now()->addMinutes(5),
        ];

        return [
            'success' => true,
            'requiere_confirmacion' => $config['requiere_confirmacion'],
            'accion' => $accion,
            'nombre' => $config['nombre'],
            'descripcion' => $config['descripcion'],
            'parametros' => $parametros,
            'simulacion' => $simulacion,
            'token' => $token,
        ];
    }

    /**
     * Valida los permisos del usuario para una acción
     */
    protected function validarPermisos(User $user, array $permisosRequeridos): array
    {
        // Los administradores pueden hacer todo
        if ($user->rol === 'admin') {
            return ['permitido' => true];
        }

        // Si no requiere permisos especiales
        if (empty($permisosRequeridos)) {
            return ['permitido' => true];
        }

        // Verificar permiso de modificar BD para acciones que modifican datos
        if (in_array('gestionar_planillas', $permisosRequeridos)) {
            if ($user->puede_modificar_bd) {
                return ['permitido' => true];
            }

            // Roles con acceso a gestión de planillas
            if (in_array($user->rol, ['oficina', 'produccion', 'encargado'])) {
                return ['permitido' => true];
            }
        }

        // Permiso para crear alertas
        if (in_array('crear_alertas', $permisosRequeridos)) {
            // Cualquier usuario autenticado puede enviar alertas
            return ['permitido' => true];
        }

        return [
            'permitido' => false,
            'mensaje' => 'No tienes permisos para realizar esta acción. Contacta con un administrador.',
        ];
    }

    /**
     * Valida los parámetros de una acción
     */
    protected function validarParametros(string $accion, array $parametros): array
    {
        $requeridos = [];
        $faltantes = [];

        switch ($accion) {
            case 'enviar_alerta':
                $requeridos = ['destinatarios', 'mensaje_alerta'];
                break;

            case 'adelantar_planilla':
            case 'retrasar_planilla':
                $requeridos = ['codigo_planilla'];
                break;

            case 'cambiar_estado_planilla':
                $requeridos = ['codigo_planilla', 'estado'];
                break;

            case 'asignar_maquina':
                $requeridos = ['codigo_planilla', 'maquina'];
                break;

            case 'cambiar_prioridad':
                $requeridos = ['codigo_planilla', 'prioridad'];
                break;
        }

        foreach ($requeridos as $campo) {
            if (empty($parametros[$campo])) {
                $faltantes[] = $campo;
            }
        }

        if (!empty($faltantes)) {
            $mensajes = [
                'codigo_planilla' => 'código de la planilla',
                'estado' => 'nuevo estado',
                'maquina' => 'máquina destino',
                'prioridad' => 'nivel de prioridad',
                'destinatarios' => 'destinatarios de la alerta',
                'mensaje_alerta' => 'mensaje de la alerta',
            ];

            $faltantesTexto = array_map(fn($f) => $mensajes[$f] ?? $f, $faltantes);

            return [
                'valido' => false,
                'mensaje' => 'Faltan datos: ' . implode(', ', $faltantesTexto),
                'faltantes' => $faltantes,
            ];
        }

        return ['valido' => true];
    }

    /**
     * Simula el impacto de una acción antes de ejecutarla
     */
    protected function simularAccion(string $accion, array $parametros): array
    {
        switch ($accion) {
            case 'adelantar_planilla':
            case 'retrasar_planilla':
                return $this->simularMovimientoCola($accion, $parametros);

            case 'cambiar_estado_planilla':
                return $this->simularCambioEstado($parametros);

            case 'asignar_maquina':
                return $this->simularAsignacionMaquina($parametros);

            case 'cambiar_prioridad':
                return $this->simularCambioPrioridad($parametros);

            case 'enviar_alerta':
                return $this->simularEnvioAlerta($parametros);

            default:
                return ['descripcion' => 'Acción sin simulación disponible'];
        }
    }

    /**
     * Simula movimiento en cola
     */
    protected function simularMovimientoCola(string $accion, array $parametros): array
    {
        $planilla = Planilla::where('codigo', 'LIKE', '%' . $parametros['codigo_planilla'] . '%')
            ->orWhere('id', $parametros['codigo_planilla'])
            ->first();

        if (!$planilla) {
            return [
                'encontrado' => false,
                'error' => 'Planilla no encontrada',
            ];
        }

        $posiciones = $parametros['posiciones'] ?? 1;
        $direccion = $accion === 'adelantar_planilla' ? 'arriba' : 'abajo';

        return [
            'encontrado' => true,
            'planilla' => [
                'id' => $planilla->id,
                'codigo' => $planilla->codigo,
                'cliente' => $planilla->cliente->empresa ?? 'N/A',
                'obra' => $planilla->obra->obra ?? 'N/A',
                'peso' => $planilla->peso_total,
                'estado' => $planilla->estado,
            ],
            'impacto' => "La planilla {$planilla->codigo} se moverá {$posiciones} posicion(es) hacia {$direccion} en la cola",
            'afecta_otras' => true,
            'descripcion' => "Las planillas adyacentes se reordenarán automáticamente",
        ];
    }

    /**
     * Simula cambio de estado
     */
    protected function simularCambioEstado(array $parametros): array
    {
        $planilla = Planilla::where('codigo', 'LIKE', '%' . $parametros['codigo_planilla'] . '%')
            ->orWhere('id', $parametros['codigo_planilla'])
            ->first();

        if (!$planilla) {
            return [
                'encontrado' => false,
                'error' => 'Planilla no encontrada',
            ];
        }

        $estadoActual = $planilla->estado;
        $nuevoEstado = $parametros['estado'];

        // Verificar transición válida
        $transicionesValidas = [
            'pendiente' => ['fabricando', 'cancelada'],
            'fabricando' => ['completada', 'pausada', 'pendiente'],
            'pausada' => ['fabricando', 'cancelada'],
            'completada' => [], // No se puede cambiar desde completada
        ];

        $esValida = in_array($nuevoEstado, $transicionesValidas[$estadoActual] ?? []);

        return [
            'encontrado' => true,
            'planilla' => [
                'id' => $planilla->id,
                'codigo' => $planilla->codigo,
                'estado_actual' => $estadoActual,
            ],
            'transicion_valida' => $esValida,
            'impacto' => $esValida
                ? "El estado cambiará de '{$estadoActual}' a '{$nuevoEstado}'"
                : "No es posible cambiar de '{$estadoActual}' a '{$nuevoEstado}'",
            'advertencia' => !$esValida ? "Esta transición de estado no está permitida" : null,
        ];
    }

    /**
     * Simula asignación de máquina
     */
    protected function simularAsignacionMaquina(array $parametros): array
    {
        $planilla = Planilla::where('codigo', 'LIKE', '%' . $parametros['codigo_planilla'] . '%')
            ->orWhere('id', $parametros['codigo_planilla'])
            ->first();

        $maquina = Maquina::where('codigo', $parametros['maquina'])
            ->orWhere('nombre', 'LIKE', '%' . $parametros['maquina'] . '%')
            ->first();

        if (!$planilla) {
            return ['encontrado' => false, 'error' => 'Planilla no encontrada'];
        }

        if (!$maquina) {
            return ['encontrado' => false, 'error' => 'Máquina no encontrada'];
        }

        // Verificar elementos pendientes
        $elementosPendientes = $planilla->elementos()
            ->whereIn('estado', ['pendiente', 'fabricando'])
            ->count();

        return [
            'encontrado' => true,
            'planilla' => [
                'codigo' => $planilla->codigo,
                'elementos_pendientes' => $elementosPendientes,
            ],
            'maquina' => [
                'codigo' => $maquina->codigo,
                'nombre' => $maquina->nombre,
                'tipo' => $maquina->tipo,
            ],
            'impacto' => "{$elementosPendientes} elementos se asignarán a la máquina {$maquina->nombre}",
        ];
    }

    /**
     * Simula cambio de prioridad
     */
    protected function simularCambioPrioridad(array $parametros): array
    {
        $planilla = Planilla::where('codigo', 'LIKE', '%' . $parametros['codigo_planilla'] . '%')
            ->orWhere('id', $parametros['codigo_planilla'])
            ->first();

        if (!$planilla) {
            return ['encontrado' => false, 'error' => 'Planilla no encontrada'];
        }

        return [
            'encontrado' => true,
            'planilla' => [
                'codigo' => $planilla->codigo,
                'prioridad_actual' => $planilla->prioridad ?? 'normal',
            ],
            'nueva_prioridad' => $parametros['prioridad'],
            'impacto' => "La prioridad cambiará a '{$parametros['prioridad']}'",
        ];
    }

    /**
     * Simula envío de alerta
     */
    protected function simularEnvioAlerta(array $parametros): array
    {
        $destinatarios = $parametros['destinatarios'] ?? [];
        $usuariosEncontrados = [];

        foreach ($destinatarios as $dest) {
            $user = User::where('name', 'LIKE', '%' . trim($dest) . '%')->first();
            if ($user) {
                $usuariosEncontrados[] = $user->name;
            }
        }

        return [
            'destinatarios_solicitados' => $destinatarios,
            'destinatarios_encontrados' => $usuariosEncontrados,
            'mensaje' => $parametros['mensaje_alerta'] ?? '',
            'impacto' => count($usuariosEncontrados) . ' usuario(s) recibirán la alerta',
        ];
    }

    /**
     * Verifica si hay una confirmación de acción
     */
    public function verificarConfirmacion(string $mensaje, int $userId): ?array
    {
        $mensaje = strtolower(trim($mensaje));

        // Patrones de confirmación
        $confirmaciones = ['si confirmo', 'sí confirmo', 'confirmo', 'si, confirmo', 'sí, confirmo', 'confirmar'];
        $cancelaciones = ['cancelar', 'no', 'anular', 'no confirmo'];

        $accionPendiente = self::$accionesPendientes[$userId] ?? null;

        if (!$accionPendiente) {
            return null;
        }

        // Verificar si expiró
        if (now()->gt($accionPendiente['expira'])) {
            unset(self::$accionesPendientes[$userId]);
            return [
                'tipo' => 'expirada',
                'mensaje' => 'La confirmación ha expirado. Por favor, solicita la acción nuevamente.',
            ];
        }

        // Verificar confirmación
        foreach ($confirmaciones as $patron) {
            if (str_contains($mensaje, $patron)) {
                return [
                    'tipo' => 'confirmada',
                    'accion' => $accionPendiente['accion'],
                    'parametros' => $accionPendiente['parametros'],
                    'token' => $accionPendiente['token'],
                ];
            }
        }

        // Verificar cancelación
        foreach ($cancelaciones as $patron) {
            if (str_contains($mensaje, $patron)) {
                unset(self::$accionesPendientes[$userId]);
                return [
                    'tipo' => 'cancelada',
                    'mensaje' => 'Acción cancelada.',
                ];
            }
        }

        return null;
    }

    /**
     * Ejecuta una acción confirmada
     */
    public function ejecutarAccion(string $accion, array $parametros, User $user, ?string $token = null): array
    {
        // Verificar token si se proporciona
        $accionPendiente = self::$accionesPendientes[$user->id] ?? null;
        if ($token && $accionPendiente && $accionPendiente['token'] !== $token) {
            return [
                'success' => false,
                'error' => 'Token de confirmación inválido',
            ];
        }

        // Limpiar acción pendiente
        unset(self::$accionesPendientes[$user->id]);

        try {
            DB::beginTransaction();

            $resultado = match ($accion) {
                'enviar_alerta' => $this->ejecutarEnviarAlerta($parametros, $user),
                'adelantar_planilla' => $this->ejecutarAdelantarPlanilla($parametros, $user),
                'retrasar_planilla' => $this->ejecutarRetrasarPlanilla($parametros, $user),
                'cambiar_estado_planilla' => $this->ejecutarCambiarEstado($parametros, $user),
                'asignar_maquina' => $this->ejecutarAsignarMaquina($parametros, $user),
                'cambiar_prioridad' => $this->ejecutarCambiarPrioridad($parametros, $user),
                default => ['success' => false, 'error' => 'Acción no implementada'],
            };

            if ($resultado['success']) {
                // Registrar en auditoría
                $this->registrarAuditoria($accion, $parametros, $user, $resultado);
                DB::commit();
            } else {
                DB::rollBack();
            }

            return $resultado;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error ejecutando acción {$accion}: " . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Error al ejecutar la acción: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Ejecuta: Enviar alerta
     */
    protected function ejecutarEnviarAlerta(array $parametros, User $user): array
    {
        $destinatarios = $parametros['destinatarios'] ?? [];
        $mensaje = $parametros['mensaje_alerta'] ?? '';
        $alertasCreadas = 0;

        foreach ($destinatarios as $dest) {
            $destinatario = User::where('name', 'LIKE', '%' . trim($dest) . '%')->first();
            if ($destinatario) {
                Alerta::create([
                    'user_id' => $destinatario->id,
                    'tipo' => 'info',
                    'titulo' => 'Mensaje de ' . $user->name,
                    'mensaje' => $mensaje,
                    'origen' => 'asistente',
                    'created_by' => $user->id,
                ]);
                $alertasCreadas++;
            }
        }

        return [
            'success' => true,
            'mensaje' => "Se han enviado {$alertasCreadas} alerta(s) correctamente",
            'alertas_creadas' => $alertasCreadas,
        ];
    }

    /**
     * Ejecuta: Adelantar planilla
     */
    protected function ejecutarAdelantarPlanilla(array $parametros, User $user): array
    {
        $planilla = Planilla::where('codigo', 'LIKE', '%' . $parametros['codigo_planilla'] . '%')
            ->orWhere('id', $parametros['codigo_planilla'])
            ->first();

        if (!$planilla) {
            return ['success' => false, 'error' => 'Planilla no encontrada'];
        }

        // Obtener orden actual desde orden_planillas
        $ordenActual = DB::table('orden_planillas')
            ->where('planilla_id', $planilla->id)
            ->first();

        if (!$ordenActual) {
            return ['success' => false, 'error' => 'La planilla no está en la cola de producción'];
        }

        $posiciones = $parametros['posiciones'] ?? 1;
        $nuevaPosicion = max(1, $ordenActual->orden - $posiciones);

        // Reordenar
        DB::table('orden_planillas')
            ->where('orden', '>=', $nuevaPosicion)
            ->where('orden', '<', $ordenActual->orden)
            ->increment('orden');

        DB::table('orden_planillas')
            ->where('planilla_id', $planilla->id)
            ->update(['orden' => $nuevaPosicion]);

        return [
            'success' => true,
            'mensaje' => "La planilla {$planilla->codigo} se ha movido a la posición {$nuevaPosicion}",
            'planilla' => $planilla->codigo,
            'nueva_posicion' => $nuevaPosicion,
        ];
    }

    /**
     * Ejecuta: Retrasar planilla
     */
    protected function ejecutarRetrasarPlanilla(array $parametros, User $user): array
    {
        $planilla = Planilla::where('codigo', 'LIKE', '%' . $parametros['codigo_planilla'] . '%')
            ->orWhere('id', $parametros['codigo_planilla'])
            ->first();

        if (!$planilla) {
            return ['success' => false, 'error' => 'Planilla no encontrada'];
        }

        $ordenActual = DB::table('orden_planillas')
            ->where('planilla_id', $planilla->id)
            ->first();

        if (!$ordenActual) {
            return ['success' => false, 'error' => 'La planilla no está en la cola de producción'];
        }

        $posiciones = $parametros['posiciones'] ?? 1;
        $maxOrden = DB::table('orden_planillas')->max('orden') ?? 1;
        $nuevaPosicion = min($maxOrden, $ordenActual->orden + $posiciones);

        // Reordenar
        DB::table('orden_planillas')
            ->where('orden', '>', $ordenActual->orden)
            ->where('orden', '<=', $nuevaPosicion)
            ->decrement('orden');

        DB::table('orden_planillas')
            ->where('planilla_id', $planilla->id)
            ->update(['orden' => $nuevaPosicion]);

        return [
            'success' => true,
            'mensaje' => "La planilla {$planilla->codigo} se ha movido a la posición {$nuevaPosicion}",
            'planilla' => $planilla->codigo,
            'nueva_posicion' => $nuevaPosicion,
        ];
    }

    /**
     * Ejecuta: Cambiar estado
     */
    protected function ejecutarCambiarEstado(array $parametros, User $user): array
    {
        $planilla = Planilla::where('codigo', 'LIKE', '%' . $parametros['codigo_planilla'] . '%')
            ->orWhere('id', $parametros['codigo_planilla'])
            ->first();

        if (!$planilla) {
            return ['success' => false, 'error' => 'Planilla no encontrada'];
        }

        $estadoAnterior = $planilla->estado;
        $nuevoEstado = $parametros['estado'];

        $planilla->estado = $nuevoEstado;

        // Si se completa, registrar fecha
        if ($nuevoEstado === 'completada') {
            $planilla->fecha_finalizacion = now();
        }

        $planilla->save();

        return [
            'success' => true,
            'mensaje' => "El estado de la planilla {$planilla->codigo} ha cambiado de '{$estadoAnterior}' a '{$nuevoEstado}'",
            'planilla' => $planilla->codigo,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $nuevoEstado,
        ];
    }

    /**
     * Ejecuta: Asignar máquina
     */
    protected function ejecutarAsignarMaquina(array $parametros, User $user): array
    {
        $planilla = Planilla::where('codigo', 'LIKE', '%' . $parametros['codigo_planilla'] . '%')
            ->orWhere('id', $parametros['codigo_planilla'])
            ->first();

        $maquina = Maquina::where('codigo', $parametros['maquina'])
            ->orWhere('nombre', 'LIKE', '%' . $parametros['maquina'] . '%')
            ->first();

        if (!$planilla || !$maquina) {
            return ['success' => false, 'error' => 'Planilla o máquina no encontrada'];
        }

        // Asignar máquina a elementos pendientes
        $elementosActualizados = $planilla->elementos()
            ->whereIn('estado', ['pendiente'])
            ->update(['maquina_id' => $maquina->id]);

        return [
            'success' => true,
            'mensaje' => "Se han asignado {$elementosActualizados} elementos de la planilla {$planilla->codigo} a la máquina {$maquina->nombre}",
            'elementos_asignados' => $elementosActualizados,
        ];
    }

    /**
     * Ejecuta: Cambiar prioridad
     */
    protected function ejecutarCambiarPrioridad(array $parametros, User $user): array
    {
        $planilla = Planilla::where('codigo', 'LIKE', '%' . $parametros['codigo_planilla'] . '%')
            ->orWhere('id', $parametros['codigo_planilla'])
            ->first();

        if (!$planilla) {
            return ['success' => false, 'error' => 'Planilla no encontrada'];
        }

        $prioridadAnterior = $planilla->prioridad ?? 'normal';
        $planilla->prioridad = $parametros['prioridad'];
        $planilla->save();

        return [
            'success' => true,
            'mensaje' => "La prioridad de la planilla {$planilla->codigo} ha cambiado de '{$prioridadAnterior}' a '{$parametros['prioridad']}'",
            'planilla' => $planilla->codigo,
        ];
    }

    /**
     * Registra la acción en la auditoría
     */
    protected function registrarAuditoria(string $accion, array $parametros, User $user, array $resultado): void
    {
        try {
            AccionAsistente::create([
                'user_id' => $user->id,
                'accion' => $accion,
                'parametros' => $parametros,
                'resultado' => $resultado,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Si falla la auditoría, solo logear pero no afectar la acción
            Log::warning("Error registrando auditoría de acción: " . $e->getMessage());
        }
    }

    /**
     * Obtiene el historial de acciones de un usuario
     */
    public function obtenerHistorialAcciones(int $userId, int $limite = 20): array
    {
        return AccionAsistente::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'accion' => self::ACCIONES[$a->accion]['nombre'] ?? $a->accion,
                'parametros' => $a->parametros,
                'resultado' => $a->resultado['mensaje'] ?? 'Ejecutada',
                'fecha' => $a->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Formatea la respuesta de preparación para el chat
     */
    public function formatearPreparacion(array $preparacion): string
    {
        if (!$preparacion['success']) {
            return "❌ **Error**: {$preparacion['error']}";
        }

        $output = "<div class=\"accion-confirmacion\">\n";
        $output .= "<div style=\"background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:16px;margin:8px 0;\">\n";
        $output .= "<h4 style=\"margin:0 0 12px 0;color:#92400e;\">⚠️ Confirmar Acción: {$preparacion['nombre']}</h4>\n";

        // Mostrar simulación
        $sim = $preparacion['simulacion'];

        if (isset($sim['error'])) {
            $output .= "<p style=\"color:#dc2626;\">{$sim['error']}</p>\n";
            $output .= "</div></div>";
            return $output;
        }

        if (isset($sim['planilla'])) {
            $output .= "<p style=\"margin:8px 0;\"><strong>Planilla:</strong> {$sim['planilla']['codigo']}</p>\n";
        }

        if (isset($sim['impacto'])) {
            $output .= "<p style=\"margin:8px 0;\"><strong>Impacto:</strong> {$sim['impacto']}</p>\n";
        }

        if (isset($sim['advertencia'])) {
            $output .= "<p style=\"color:#dc2626;margin:8px 0;\">⚠️ {$sim['advertencia']}</p>\n";
        }

        $output .= "<div style=\"margin-top:16px;padding-top:12px;border-top:1px solid #fbbf24;\">\n";
        $output .= "<p style=\"font-size:0.9rem;color:#78350f;\">Para confirmar, escribe: <strong>\"SI CONFIRMO\"</strong></p>\n";
        $output .= "<p style=\"font-size:0.9rem;color:#78350f;\">Para cancelar, escribe: <strong>\"cancelar\"</strong></p>\n";
        $output .= "<p style=\"font-size:0.75rem;color:#92400e;margin-top:8px;\">⏱️ Esta confirmación expira en 5 minutos</p>\n";
        $output .= "</div>\n";
        $output .= "</div>\n</div>";

        return $output;
    }

    /**
     * Formatea el resultado de una acción ejecutada
     */
    public function formatearResultado(array $resultado): string
    {
        if (!$resultado['success']) {
            return "<div style=\"background:#fee2e2;border:1px solid #ef4444;border-radius:8px;padding:12px;margin:8px 0;\">\n"
                . "<strong style=\"color:#991b1b;\">❌ Error:</strong> {$resultado['error']}\n"
                . "</div>";
        }

        return "<div style=\"background:#d1fae5;border:1px solid #10b981;border-radius:8px;padding:12px;margin:8px 0;\">\n"
            . "<strong style=\"color:#065f46;\">✅ Acción completada</strong>\n"
            . "<p style=\"margin:8px 0 0 0;color:#047857;\">{$resultado['mensaje']}</p>\n"
            . "</div>";
    }

    /**
     * Limpia acciones pendientes expiradas
     */
    public static function limpiarAccionesExpiradas(): void
    {
        $ahora = now();
        foreach (self::$accionesPendientes as $userId => $accion) {
            if ($ahora->gt($accion['expira'])) {
                unset(self::$accionesPendientes[$userId]);
            }
        }
    }
}
