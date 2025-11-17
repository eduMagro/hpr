<?php

namespace App\Services;

use App\Models\User;
use App\Models\Etiqueta;
use App\Models\Paquete;
use App\Models\Maquina;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * ================================================================================
 * SERVICIO DE LOGS DE PRODUCCIÓN
 * ================================================================================
 * Registra todas las operaciones de fabricación y gestión de paquetes en CSV
 * - Organizado por mes en carpeta storage/produccion_piezas/
 * - Formato: fabricacion_YYYY_MM.csv
 * - Información legible y detallada
 * ================================================================================
 */
class ProductionLogger
{
    private const LOG_DIRECTORY = 'produccion_piezas';

    /**
     * Registra inicio de fabricación de una etiqueta
     */
    public static function logInicioFabricacion(Etiqueta $etiqueta, Maquina $maquina, ?User $operario1 = null, ?User $operario2 = null): void
    {
        $compañero = auth()->user() ? auth()->user()->compañeroDeTurno() : null;

        $data = [
            'Fecha y Hora' => now()->format('Y-m-d H:i:s'),
            'Acción' => 'INICIO FABRICACIÓN',
            'Usuario' => auth()->user() ? auth()->user()->nombre_completo : 'Sistema',
            'Usuario 2' => $compañero ? $compañero->nombre_completo : '',
            'Etiqueta' => $etiqueta->etiqueta_sub_id ?? $etiqueta->id,
            'Planilla' => $etiqueta->planilla->codigo ?? 'N/A',
            'Obra' => $etiqueta->planilla->obra->obra ?? 'N/A',
            'Cliente' => $etiqueta->planilla->cliente->nombre ?? 'N/A',
            'Nave' => $maquina->obra->obra ?? 'N/A',
            'Máquina' => $maquina->nombre,
            'Tipo Máquina' => $maquina->tipo_material ?? $maquina->tipo,
            'Operario 1' => $operario1 ? $operario1->nombre_completo : 'No asignado',
            'Operario 2' => $operario2 ? $operario2->nombre_completo : 'No asignado',
            'Estado Inicial' => 'pendiente',
            'Estado Final' => 'fabricando',
            'Elementos' => $etiqueta->elementos->count(),
            'Peso Estimado (kg)' => number_format($etiqueta->peso ?? 0, 2, ',', '.'),
            'Diámetros' => $etiqueta->elementos->pluck('diametro')->unique()->implode(', ') . ' mm',
            'Paquete' => 'Sin asignar',
            'Observaciones' => 'Inicio de proceso de fabricación'
        ];

        self::writeToCSV($data);
    }

    /**
     * Registra cambio de estado durante fabricación
     */
    public static function logCambioEstadoFabricacion(
        Etiqueta $etiqueta,
        string $estadoAnterior,
        string $estadoNuevo,
        Maquina $maquina,
        array $productosAfectados = [],
        array $coladas = [],
        ?Carbon $fechaInicio = null,
        ?Carbon $fechaFin = null
    ): void {
        $duracionMinutos = null;
        if ($fechaInicio && $fechaFin) {
            $duracionMinutos = $fechaInicio->diffInMinutes($fechaFin);
        }

        // Información de productos consumidos
        $productosInfo = collect($productosAfectados)->map(function($producto) {
            return sprintf(
                "Colada %s: %.2f kg (Stock: %.2f kg → %.2f kg)",
                $producto['n_colada'] ?? 'N/A',
                $producto['peso_consumido'] ?? 0,
                ($producto['peso_stock'] ?? 0) + ($producto['peso_consumido'] ?? 0),
                $producto['peso_stock'] ?? 0
            );
        })->implode(' | ');

        $compañero = auth()->user() ? auth()->user()->compañeroDeTurno() : null;

        $data = [
            'Fecha y Hora' => now()->format('Y-m-d H:i:s'),
            'Acción' => 'CAMBIO ESTADO FABRICACIÓN',
            'Usuario' => auth()->user() ? auth()->user()->nombre_completo : 'Sistema',
            'Usuario 2' => $compañero ? $compañero->nombre_completo : '',
            'Etiqueta' => $etiqueta->etiqueta_sub_id ?? $etiqueta->id,
            'Planilla' => $etiqueta->planilla->codigo ?? 'N/A',
            'Obra' => $etiqueta->planilla->obra->obra ?? 'N/A',
            'Cliente' => $etiqueta->planilla->cliente->nombre ?? 'N/A',
            'Nave' => $maquina->obra->obra ?? 'N/A',
            'Máquina' => $maquina->nombre,
            'Tipo Máquina' => $maquina->tipo_material ?? $maquina->tipo,
            'Operario 1' => optional($etiqueta->operario1)->nombre_completo ?? 'No asignado',
            'Operario 2' => optional($etiqueta->operario2)->nombre_completo ?? 'No asignado',
            'Estado Inicial' => ucfirst($estadoAnterior),
            'Estado Final' => ucfirst($estadoNuevo),
            'Elementos' => $etiqueta->elementos->count(),
            'Peso Estimado (kg)' => number_format($etiqueta->peso ?? 0, 2, ',', '.'),
            'Diámetros' => $etiqueta->elementos->pluck('diametro')->unique()->implode(', ') . ' mm',
            'Paquete' => $etiqueta->paquete?->codigo ?? 'Sin asignar',
            'Observaciones' => implode(' | ', array_filter([
                $duracionMinutos ? "Duración: {$duracionMinutos} min" : null,
                !empty($coladas) ? 'Coladas: ' . implode(', ', $coladas) : null,
                $productosInfo ?: null
            ]))
        ];

        self::writeToCSV($data);
    }

    /**
     * Registra creación de paquete
     */
    public static function logCreacionPaquete(
        Paquete $paquete,
        array $etiquetasIds,
        Maquina $maquina,
        ?User $usuario = null
    ): void {
        $etiquetas = Etiqueta::whereIn('etiqueta_sub_id', $etiquetasIds)
            ->orWhereIn('id', $etiquetasIds)
            ->get();

        $compañero = $usuario ? $usuario->compañeroDeTurno() : null;

        $data = [
            'Fecha y Hora' => now()->format('Y-m-d H:i:s'),
            'Acción' => 'CREAR PAQUETE',
            'Usuario' => $usuario ? $usuario->nombre_completo : 'Sistema',
            'Usuario 2' => $compañero ? $compañero->nombre_completo : '',
            'Etiqueta' => implode(', ', $etiquetas->pluck('etiqueta_sub_id')->toArray()),
            'Planilla' => $paquete->planilla->codigo ?? 'N/A',
            'Obra' => $paquete->planilla->obra->obra ?? 'N/A',
            'Cliente' => $paquete->planilla->cliente->nombre ?? 'N/A',
            'Nave' => $maquina->obra->obra ?? 'N/A',
            'Máquina' => $maquina->nombre,
            'Tipo Máquina' => $maquina->tipo_material ?? $maquina->tipo,
            'Operario 1' => $usuario ? $usuario->nombre_completo : 'Sistema',
            'Operario 2' => '',
            'Estado Inicial' => 'fabricada/completada',
            'Estado Final' => 'en-paquete',
            'Elementos' => $etiquetas->sum(fn($e) => $e->elementos->count()),
            'Peso Estimado (kg)' => number_format($paquete->peso, 2, ',', '.'),
            'Diámetros' => $etiquetas->flatMap(fn($e) => $e->elementos->pluck('diametro'))->unique()->implode(', ') . ' mm',
            'Paquete' => $paquete->codigo,
            'Observaciones' => sprintf(
                'Paquete creado con %d etiquetas | Ubicación: %s | Nave: %s',
                $etiquetas->count(),
                $paquete->ubicacion->nombre ?? 'Sin ubicación',
                $paquete->nave->obra ?? 'Sin nave'
            )
        ];

        self::writeToCSV($data);
    }

    /**
     * Registra adición de etiqueta a paquete existente
     */
    public static function logAñadirEtiquetaPaquete(
        Paquete $paquete,
        Etiqueta $etiqueta,
        float $pesoAnterior,
        ?User $usuario = null
    ): void {
        $compañero = $usuario ? $usuario->compañeroDeTurno() : null;

        $data = [
            'Fecha y Hora' => now()->format('Y-m-d H:i:s'),
            'Acción' => 'AÑADIR A PAQUETE',
            'Usuario' => $usuario ? $usuario->nombre_completo : 'Sistema',
            'Usuario 2' => $compañero ? $compañero->nombre_completo : '',
            'Etiqueta' => $etiqueta->etiqueta_sub_id ?? $etiqueta->id,
            'Planilla' => $paquete->planilla->codigo ?? 'N/A',
            'Obra' => $paquete->planilla->obra->obra ?? 'N/A',
            'Cliente' => $paquete->planilla->cliente->nombre ?? 'N/A',
            'Nave' => 'N/A',
            'Máquina' => '',
            'Tipo Máquina' => '',
            'Operario 1' => $usuario ? $usuario->nombre_completo : 'Sistema',
            'Operario 2' => '',
            'Estado Inicial' => $etiqueta->estado,
            'Estado Final' => 'en-paquete',
            'Elementos' => $etiqueta->elementos->count(),
            'Peso Estimado (kg)' => number_format($etiqueta->peso ?? 0, 2, ',', '.'),
            'Diámetros' => $etiqueta->elementos->pluck('diametro')->unique()->implode(', ') . ' mm',
            'Paquete' => $paquete->codigo,
            'Observaciones' => sprintf(
                'Etiqueta añadida a paquete existente | Peso paquete: %.2f kg → %.2f kg | Etiquetas en paquete: %d',
                $pesoAnterior,
                $paquete->peso,
                $paquete->etiquetas->count()
            )
        ];

        self::writeToCSV($data);
    }

    /**
     * Registra eliminación de etiqueta de paquete
     */
    public static function logEliminarEtiquetaPaquete(
        Paquete $paquete,
        Etiqueta $etiqueta,
        float $pesoAnterior,
        int $etiquetasRestantes,
        ?User $usuario = null
    ): void {
        $compañero = $usuario ? $usuario->compañeroDeTurno() : null;

        $data = [
            'Fecha y Hora' => now()->format('Y-m-d H:i:s'),
            'Acción' => 'QUITAR DE PAQUETE',
            'Usuario' => $usuario ? $usuario->nombre_completo : 'Sistema',
            'Usuario 2' => $compañero ? $compañero->nombre_completo : '',
            'Etiqueta' => $etiqueta->etiqueta_sub_id ?? $etiqueta->id,
            'Planilla' => $paquete->planilla->codigo ?? 'N/A',
            'Obra' => $paquete->planilla->obra->obra ?? 'N/A',
            'Cliente' => $paquete->planilla->cliente->nombre ?? 'N/A',
            'Nave' => 'N/A',
            'Máquina' => '',
            'Tipo Máquina' => '',
            'Operario 1' => $usuario ? $usuario->nombre_completo : 'Sistema',
            'Operario 2' => '',
            'Estado Inicial' => 'en-paquete',
            'Estado Final' => 'pendiente',
            'Elementos' => $etiqueta->elementos->count(),
            'Peso Estimado (kg)' => number_format($etiqueta->peso ?? 0, 2, ',', '.'),
            'Diámetros' => $etiqueta->elementos->pluck('diametro')->unique()->implode(', ') . ' mm',
            'Paquete' => $paquete->codigo,
            'Observaciones' => sprintf(
                'Etiqueta eliminada del paquete | Peso paquete: %.2f kg → %.2f kg | Etiquetas restantes: %d',
                $pesoAnterior,
                $paquete->peso,
                $etiquetasRestantes
            )
        ];

        self::writeToCSV($data);
    }

    /**
     * Registra eliminación completa de paquete
     */
    public static function logEliminarPaquete(
        Paquete $paquete,
        int $etiquetasLiberadas,
        array $etiquetasIds,
        ?User $usuario = null
    ): void {
        $compañero = $usuario ? $usuario->compañeroDeTurno() : null;

        $data = [
            'Fecha y Hora' => now()->format('Y-m-d H:i:s'),
            'Acción' => 'ELIMINAR PAQUETE',
            'Usuario' => $usuario ? $usuario->nombre_completo : 'Sistema',
            'Usuario 2' => $compañero ? $compañero->nombre_completo : '',
            'Etiqueta' => implode(', ', $etiquetasIds),
            'Planilla' => $paquete->planilla->codigo ?? 'N/A',
            'Obra' => $paquete->planilla->obra->obra ?? 'N/A',
            'Cliente' => $paquete->planilla->cliente->nombre ?? 'N/A',
            'Nave' => 'N/A',
            'Máquina' => '',
            'Tipo Máquina' => '',
            'Operario 1' => $usuario ? $usuario->nombre_completo : 'Sistema',
            'Operario 2' => '',
            'Estado Inicial' => 'en-paquete',
            'Estado Final' => 'liberada',
            'Elementos' => '',
            'Peso Estimado (kg)' => number_format($paquete->peso, 2, ',', '.'),
            'Diámetros' => '',
            'Paquete' => $paquete->codigo,
            'Observaciones' => sprintf(
                'Paquete eliminado completamente | %d etiquetas liberadas | Ubicación: %s',
                $etiquetasLiberadas,
                $paquete->ubicacion->nombre ?? 'Sin ubicación'
            )
        ];

        self::writeToCSV($data);
    }

    /**
     * Registra asignación detallada de coladas a elementos
     *
     * @param Etiqueta $etiqueta
     * @param Maquina $maquina
     * @param array $elementosConColadas Array de elementos con sus productos asignados
     * @param array $productosAfectados Array de productos consumidos
     * @param array $warnings Warnings generados durante el proceso
     */
    public static function logAsignacionColadas(
        Etiqueta $etiqueta,
        Maquina $maquina,
        array $elementosConColadas,
        array $productosAfectados = [],
        array $warnings = []
    ): void {
        $compañero = auth()->user() ? auth()->user()->compañeroDeTurno() : null;

        // Construir detalle de asignaciones
        $asignacionesDetalle = [];
        foreach ($elementosConColadas as $elementoData) {
            $elemento = $elementoData['elemento'];
            $coladas = $elementoData['coladas'] ?? [];

            $coladasStr = collect($coladas)->map(function($colada) {
                // Usar código si está disponible, sino ID
                $identificador = $colada['producto_codigo'] ?? 'P' . $colada['producto_id'];
                return sprintf(
                    "%s(Colada:%s,%.2fkg)",
                    $identificador,
                    $colada['n_colada'] ?? 'N/A',
                    $colada['peso_consumido'] ?? 0
                );
            })->implode(' + ');

            $asignacionesDetalle[] = sprintf(
                "Elem%d[Ø%smm,%.2fkg]→%s",
                $elemento->id,
                $elemento->diametro,
                $elemento->peso,
                $coladasStr ?: 'Sin asignar'
            );
        }

        // Construir detalle de productos consumidos
        $productosDetalle = collect($productosAfectados)->map(function($prod) {
            // Usar código si está disponible, sino ID
            $identificador = $prod['codigo'] ?? 'P' . $prod['id'];
            return sprintf(
                "%s: Colada %s | Stock: %.2fkg→%.2fkg | Consumido: %.2fkg%s",
                $identificador,
                $prod['n_colada'] ?? 'N/A',
                ($prod['peso_inicial'] ?? 0),
                ($prod['peso_stock'] ?? 0),
                ($prod['peso_inicial'] ?? 0) - ($prod['peso_stock'] ?? 0),
                ($prod['peso_stock'] ?? 0) <= 0 ? ' [AGOTADO]' : ''
            );
        })->implode(' | ');

        // Estadísticas de asignación
        $totalElementos = count($elementosConColadas);
        $con1Producto = 0;
        $con2Productos = 0;
        $con3Productos = 0;

        foreach ($elementosConColadas as $elemData) {
            $numColadas = count($elemData['coladas'] ?? []);
            if ($numColadas === 1) $con1Producto++;
            if ($numColadas === 2) $con2Productos++;
            if ($numColadas === 3) $con3Productos++;
        }

        $estadisticas = sprintf(
            "Total:%d | Simple:%d | Doble:%d | Triple:%d",
            $totalElementos,
            $con1Producto,
            $con2Productos,
            $con3Productos
        );

        // Formatear observaciones de forma legible
        $observaciones = [];
        $observaciones[] = "📊 ESTADÍSTICAS";
        $observaciones[] = "   Total elementos: {$totalElementos} | Simple: {$con1Producto} | Doble: {$con2Productos} | Triple: {$con3Productos}";
        $observaciones[] = "";

        $observaciones[] = "📦 ASIGNACIONES POR ELEMENTO";
        foreach ($elementosConColadas as $elementoData) {
            $elemento = $elementoData['elemento'];
            $coladas = $elementoData['coladas'] ?? [];

            $coladasStr = collect($coladas)->map(function($colada) {
                $identificador = $colada['producto_codigo'] ?? 'P' . $colada['producto_id'];
                return sprintf(
                    "%s (Colada: %s, %.2f kg)",
                    $identificador,
                    $colada['n_colada'] ?? 'N/A',
                    $colada['peso_consumido'] ?? 0
                );
            })->implode(' + ');

            // Usar código del elemento si está disponible, sino usar ID
            $elementoIdentificador = $elemento->codigo ?? 'E' . $elemento->id;

            $observaciones[] = sprintf(
                "   • Elemento %s [Ø%s mm, %.2f kg] → %s",
                $elementoIdentificador,
                $elemento->diametro,
                $elemento->peso,
                $coladasStr ?: 'Sin asignar'
            );
        }

        if (!empty($productosDetalle)) {
            $observaciones[] = "";
            $observaciones[] = "📋 PRODUCTOS CONSUMIDOS";
            foreach ($productosAfectados as $prod) {
                $identificador = $prod['codigo'] ?? 'P' . $prod['id'];
                $pesoInicial = $prod['peso_inicial'] ?? 0;
                $pesoStock = $prod['peso_stock'] ?? 0;
                // Usar el consumo acumulado si está disponible, sino calcular
                $consumido = $prod['consumido'] ?? ($pesoInicial - $pesoStock);
                $agotado = $pesoStock <= 0 ? ' ⚠️ AGOTADO' : '';

                $observaciones[] = sprintf(
                    "   • %s - Colada: %s | Stock: %.2f kg → %.2f kg | Consumido: %.2f kg%s",
                    $identificador,
                    $prod['n_colada'] ?? 'N/A',
                    $pesoInicial,
                    $pesoStock,
                    $consumido,
                    $agotado
                );
            }
        }

        if (!empty($warnings)) {
            $observaciones[] = "";
            $observaciones[] = "⚠️ ADVERTENCIAS";
            foreach ($warnings as $warning) {
                $observaciones[] = "   • " . $warning;
            }
        }

        $data = [
            'Fecha y Hora' => now()->format('Y-m-d H:i:s'),
            'Acción' => 'ASIGNACIÓN COLADAS',
            'Usuario' => auth()->user() ? auth()->user()->nombre_completo : 'Sistema',
            'Usuario 2' => $compañero ? $compañero->nombre_completo : '',
            'Etiqueta' => $etiqueta->etiqueta_sub_id ?? $etiqueta->id,
            'Planilla' => $etiqueta->planilla->codigo ?? 'N/A',
            'Obra' => $etiqueta->planilla->obra->obra ?? 'N/A',
            'Cliente' => $etiqueta->planilla->cliente->nombre ?? 'N/A',
            'Nave' => $maquina->obra->obra ?? 'N/A',
            'Máquina' => $maquina->nombre,
            'Tipo Máquina' => $maquina->tipo_material ?? $maquina->tipo,
            'Operario 1' => optional($etiqueta->operario1)->nombre_completo ?? 'No asignado',
            'Operario 2' => optional($etiqueta->operario2)->nombre_completo ?? 'No asignado',
            'Estado Inicial' => 'fabricando',
            'Estado Final' => 'fabricando',
            'Elementos' => $totalElementos,
            'Peso Estimado (kg)' => number_format($etiqueta->peso ?? 0, 2, ',', '.'),
            'Diámetros' => $etiqueta->elementos->pluck('diametro')->unique()->implode(', ') . ' mm',
            'Paquete' => $etiqueta->paquete?->codigo ?? 'Sin asignar',
            'Observaciones' => implode("\n", $observaciones)
        ];

        self::writeToCSV($data);
    }

    /**
     * Registra detalle de consumo de stock por diámetro
     *
     * @param Etiqueta $etiqueta
     * @param Maquina $maquina
     * @param array $consumosPorDiametro Array con consumos agrupados por diámetro
     */
    public static function logConsumoStockPorDiametro(
        Etiqueta $etiqueta,
        Maquina $maquina,
        array $consumosPorDiametro
    ): void {
        $compañero = auth()->user() ? auth()->user()->compañeroDeTurno() : null;

        // Debug: verificar datos recibidos
        \Log::info('ProductionLogger::logConsumoStockPorDiametro', [
            'etiqueta_id' => $etiqueta->id,
            'consumos_count' => count($consumosPorDiametro),
            'consumos_data' => $consumosPorDiametro
        ]);

        // Formatear observaciones de forma legible
        $observaciones = [];
        $observaciones[] = "📊 CONSUMO DE STOCK POR DIÁMETRO";
        $observaciones[] = "";

        foreach ($consumosPorDiametro as $diametro => $consumos) {
            $totalConsumido = array_sum(array_column($consumos, 'consumido'));
            $numProductos = count($consumos);

            $observaciones[] = sprintf("🔹 Ø%d mm - Total: %.2f kg (%d producto%s)",
                $diametro,
                $totalConsumido,
                $numProductos,
                $numProductos > 1 ? 's' : ''
            );

            foreach ($consumos as $consumo) {
                $identificador = $consumo['producto_codigo'] ?? 'P' . $consumo['producto_id'];
                $observaciones[] = sprintf(
                    "   • %s: %.2f kg",
                    $identificador,
                    $consumo['consumido']
                );
            }

            $observaciones[] = "";
        }

        $data = [
            'Fecha y Hora' => now()->format('Y-m-d H:i:s'),
            'Acción' => 'CONSUMO STOCK',
            'Usuario' => auth()->user() ? auth()->user()->nombre_completo : 'Sistema',
            'Usuario 2' => $compañero ? $compañero->nombre_completo : '',
            'Etiqueta' => $etiqueta->etiqueta_sub_id ?? $etiqueta->id,
            'Planilla' => $etiqueta->planilla->codigo ?? 'N/A',
            'Obra' => $etiqueta->planilla->obra->obra ?? 'N/A',
            'Cliente' => $etiqueta->planilla->cliente->nombre ?? 'N/A',
            'Nave' => $maquina->obra->obra ?? 'N/A',
            'Máquina' => $maquina->nombre,
            'Tipo Máquina' => $maquina->tipo_material ?? $maquina->tipo,
            'Operario 1' => optional($etiqueta->operario1)->nombre_completo ?? 'No asignado',
            'Operario 2' => optional($etiqueta->operario2)->nombre_completo ?? 'No asignado',
            'Estado Inicial' => 'fabricando',
            'Estado Final' => 'fabricando',
            'Elementos' => $etiqueta->elementos->count(),
            'Peso Estimado (kg)' => number_format($etiqueta->peso ?? 0, 2, ',', '.'),
            'Diámetros' => implode(', ', array_keys($consumosPorDiametro)) . ' mm',
            'Paquete' => $etiqueta->paquete?->codigo ?? 'Sin asignar',
            'Observaciones' => implode("\n", array_filter($observaciones))
        ];

        self::writeToCSV($data);
    }

    /**
     * Escribe una línea en el archivo CSV del mes actual
     */
    private static function writeToCSV(array $data): void
    {
        try {
            $fileName = self::getMonthlyFileName();
            $filePath = self::LOG_DIRECTORY . '/' . $fileName;

            // Verificar si el archivo existe para determinar si escribir headers
            $fileExists = Storage::exists($filePath);

            // Asegurarse de que el directorio existe
            if (!Storage::exists(self::LOG_DIRECTORY)) {
                Storage::makeDirectory(self::LOG_DIRECTORY);
            }

            // Abrir archivo para append
            $handle = fopen(storage_path('app/' . $filePath), 'a');

            if ($handle === false) {
                \Log::error('ProductionLogger: No se pudo abrir el archivo CSV', ['path' => $filePath]);
                return;
            }

            // Si el archivo es nuevo, escribir headers
            if (!$fileExists || filesize(storage_path('app/' . $filePath)) === 0) {
                fputcsv($handle, array_keys($data), ';');
            }

            // Escribir datos
            fputcsv($handle, array_values($data), ';');

            fclose($handle);

        } catch (\Exception $e) {
            \Log::error('ProductionLogger: Error al escribir log', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    /**
     * Obtiene el nombre del archivo CSV para el mes actual
     */
    private static function getMonthlyFileName(): string
    {
        return 'fabricacion_' . now()->format('Y_m') . '.csv';
    }

    /**
     * Obtiene la ruta completa del archivo CSV del mes actual
     */
    public static function getCurrentLogPath(): string
    {
        return storage_path('app/' . self::LOG_DIRECTORY . '/' . self::getMonthlyFileName());
    }

    /**
     * Lista todos los archivos de log disponibles
     */
    public static function listLogFiles(): array
    {
        $files = Storage::files(self::LOG_DIRECTORY);

        return collect($files)
            ->filter(fn($file) => str_ends_with($file, '.csv'))
            ->map(fn($file) => [
                'path' => $file,
                'name' => basename($file),
                'size' => Storage::size($file),
                'modified' => Storage::lastModified($file),
                'url' => Storage::url($file)
            ])
            ->sortByDesc('modified')
            ->values()
            ->toArray();
    }
}
