<?php

namespace App\Services;

use App\Models\User;
use App\Models\Empresa;
use App\Models\AsignacionTurno;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio centralizado para consultas de operarios.
 * Optimiza rendimiento con caché y proporciona métodos reutilizables.
 */
class OperarioService
{
    /**
     * Campos base que siempre se seleccionan para operarios
     */
    private const CAMPOS_BASE = [
        'id', 'name', 'primer_apellido', 'segundo_apellido',
        'turno', 'maquina_id', 'empresa_id'
    ];

    /**
     * TTL de caché en segundos (5 minutos)
     */
    private const CACHE_TTL = 300;

    /**
     * Obtiene todas las empresas que tienen operarios activos.
     * Útil para generar selectores dinámicos.
     */
    public function getEmpresasConOperarios(): Collection
    {
        return Cache::remember('empresas_con_operarios', self::CACHE_TTL, function () {
            $empresaIds = User::where('rol', 'operario')
                ->whereNotNull('empresa_id')
                ->distinct()
                ->pluck('empresa_id');

            return Empresa::whereIn('id', $empresaIds)
                ->orderBy('nombre')
                ->get(['id', 'nombre']);
        });
    }

    /**
     * Obtiene todos los operarios activos con información de empresa.
     */
    public function getTodosOperarios(array $camposExtra = []): Collection
    {
        $campos = array_merge(self::CAMPOS_BASE, $camposExtra);

        return User::operarios()
            ->with('empresa:id,nombre')
            ->select($campos)
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtiene operarios agrupados por empresa.
     * Devuelve estructura: [ empresa_id => ['empresa' => {...}, 'operarios' => [...]] ]
     */
    public function getOperariosAgrupadosPorEmpresa(array $camposExtra = []): array
    {
        $operarios = $this->getTodosOperarios($camposExtra);
        $empresas = $this->getEmpresasConOperarios();

        $resultado = [];

        // Primero añadir empresas conocidas
        foreach ($empresas as $empresa) {
            $resultado[$empresa->id] = [
                'empresa' => [
                    'id' => $empresa->id,
                    'nombre' => $empresa->nombre,
                ],
                'operarios' => [],
            ];
        }

        // Añadir grupo para operarios sin empresa
        $resultado['sin_empresa'] = [
            'empresa' => [
                'id' => null,
                'nombre' => 'Sin empresa asignada',
            ],
            'operarios' => [],
        ];

        // Distribuir operarios en sus grupos
        foreach ($operarios as $operario) {
            $key = $operario->empresa_id ?? 'sin_empresa';
            if (!isset($resultado[$key])) {
                // Empresa no encontrada (caso raro), crear grupo
                $resultado[$key] = [
                    'empresa' => [
                        'id' => $operario->empresa_id,
                        'nombre' => $operario->empresa?->nombre ?? 'Empresa desconocida',
                    ],
                    'operarios' => [],
                ];
            }
            $resultado[$key]['operarios'][] = $this->formatearOperario($operario);
        }

        // Eliminar grupos vacíos
        return array_filter($resultado, fn($grupo) => count($grupo['operarios']) > 0);
    }

    /**
     * Obtiene operarios que NO tienen turno asignado en una fecha específica,
     * agrupados por empresa.
     */
    public function getOperariosSinTurnoPorEmpresa(string $fecha): array
    {
        // IDs de operarios con turno ese día
        $idsConTurno = AsignacionTurno::whereDate('fecha', $fecha)
            ->pluck('user_id')
            ->toArray();

        $operarios = User::operarios()
            ->with('empresa:id,nombre')
            ->whereNotIn('id', $idsConTurno)
            ->select(self::CAMPOS_BASE)
            ->orderBy('name')
            ->get();

        return $this->agruparPorEmpresa($operarios);
    }

    /**
     * Obtiene operarios asignados a una máquina específica, agrupados por empresa.
     */
    public function getOperariosDeMaquinaPorEmpresa(int $maquinaId): array
    {
        $operarios = User::operarios()
            ->with('empresa:id,nombre')
            ->where('maquina_id', $maquinaId)
            ->select(self::CAMPOS_BASE)
            ->orderBy('name')
            ->get();

        return $this->agruparPorEmpresa($operarios);
    }

    /**
     * Método principal para el diálogo de generar turnos.
     * Devuelve todos los datos necesarios en una sola llamada.
     */
    public function getDatosParaGenerarTurnos(string $fecha, int $maquinaId): array
    {
        // IDs de operarios con turno ese día
        $idsConTurno = AsignacionTurno::whereDate('fecha', $fecha)
            ->pluck('user_id')
            ->toArray();

        // Una sola consulta para todos los operarios
        $todosOperarios = User::operarios()
            ->with('empresa:id,nombre')
            ->select(self::CAMPOS_BASE)
            ->orderBy('name')
            ->get();

        // Obtener empresas para ordenar los grupos
        $empresas = $this->getEmpresasConOperarios();

        // Separar en grupos
        $sinTurno = $todosOperarios->filter(fn($op) => !in_array($op->id, $idsConTurno));
        $deMaquina = $todosOperarios->filter(fn($op) => $op->maquina_id == $maquinaId);

        return [
            'empresas' => $empresas->map(fn($e) => ['id' => $e->id, 'nombre' => $e->nombre])->values()->toArray(),
            'sin_turno_por_empresa' => $this->agruparPorEmpresa($sinTurno),
            'de_maquina_por_empresa' => $this->agruparPorEmpresa($deMaquina),
            'todos_por_empresa' => $this->agruparPorEmpresa($todosOperarios),
            // Mantener compatibilidad con código existente
            'sin_turno' => $sinTurno->map(fn($op) => $this->formatearOperario($op))->values()->toArray(),
            'de_maquina' => $deMaquina->map(fn($op) => $this->formatearOperario($op))->values()->toArray(),
            'todos' => $todosOperarios->map(fn($op) => $this->formatearOperario($op))->values()->toArray(),
        ];
    }

    /**
     * Agrupa una colección de operarios por empresa.
     */
    private function agruparPorEmpresa(Collection $operarios): array
    {
        $grupos = [];

        foreach ($operarios as $operario) {
            $empresaId = $operario->empresa_id ?? 'sin_empresa';
            $empresaNombre = $operario->empresa?->nombre ?? 'Sin empresa';

            if (!isset($grupos[$empresaId])) {
                $grupos[$empresaId] = [
                    'empresa_id' => $operario->empresa_id,
                    'empresa_nombre' => $empresaNombre,
                    'operarios' => [],
                ];
            }

            $grupos[$empresaId]['operarios'][] = $this->formatearOperario($operario);
        }

        // Ordenar por nombre de empresa
        uasort($grupos, fn($a, $b) => strcmp($a['empresa_nombre'], $b['empresa_nombre']));

        return array_values($grupos);
    }

    /**
     * Formatea un operario para respuesta JSON.
     */
    private function formatearOperario(User $operario): array
    {
        return [
            'id' => $operario->id,
            'name' => $operario->name,
            'primer_apellido' => $operario->primer_apellido,
            'segundo_apellido' => $operario->segundo_apellido,
            'nombre_completo' => $operario->nombre_completo,
            'turno' => $operario->turno,
            'maquina_id' => $operario->maquina_id,
            'empresa_id' => $operario->empresa_id,
            'empresa_nombre' => $operario->empresa?->nombre ?? null,
        ];
    }

    /**
     * Limpia la caché de empresas con operarios.
     * Llamar cuando se modifiquen usuarios o empresas.
     */
    public function limpiarCache(): void
    {
        Cache::forget('empresas_con_operarios');
    }
}
