<?php

namespace App\Services;

use App\Models\Localizacion;
use App\Models\LocalizacionPaquete;
use App\Models\Paquete;
use App\Models\Maquina;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio LocalizacionPaqueteService
 * 
 * Este servicio maneja la lógica de asignación de localizaciones a paquetes
 * en el mapa del almacén. Calcula automáticamente la posición del paquete
 * basándose en la localización de la máquina donde se creó.
 * 
 * Funcionalidades principales:
 * - Obtener la localización de una máquina
 * - Calcular el centro de una máquina
 * - Asignar localización automática a un paquete
 * - Calcular el tamaño del paquete en celdas
 */
class LocalizacionPaqueteService
{
    /**
     * Constante: Tamaño de cada celda en metros
     * Cada celda del grid representa 0.5 metros del espacio físico real
     */
    const METROS_POR_CELDA = 0.5;

    /**
     * Obtiene la localización de una máquina específica
     * 
     * Este método busca en la tabla 'localizaciones' el registro
     * que corresponde a la máquina especificada
     * 
     * @param int $maquinaId ID de la máquina
     * @return Localizacion|null Objeto Localizacion si existe, null si no
     */
    public function obtenerLocalizacionMaquina(int $maquinaId): ?Localizacion
    {
        try {
            // Buscar la localización de tipo 'maquina' que tenga el maquina_id especificado
            $localizacion = Localizacion::where('tipo', 'maquina')
                ->where('maquina_id', $maquinaId)
                ->first();

            // Si no se encuentra, registrar un warning en los logs
            if (!$localizacion) {
                Log::warning("No se encontró localización para la máquina ID: {$maquinaId}");
            }

            return $localizacion;
        } catch (Exception $e) {
            // En caso de error, registrar el error y retornar null
            Log::error("Error al obtener localización de máquina: {$e->getMessage()}", [
                'maquina_id' => $maquinaId,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Calcula las coordenadas del centro de una máquina
     * 
     * Dadas las coordenadas de las esquinas de una máquina (x1, y1, x2, y2),
     * calcula el punto central redondeando al entero más cercano
     * 
     * @param int $x1 Coordenada X inicial (columna inicial)
     * @param int $y1 Coordenada Y inicial (fila inicial)
     * @param int $x2 Coordenada X final (columna final)
     * @param int $y2 Coordenada Y final (fila final)
     * @return array Array con las claves 'x' e 'y' del centro
     */
    public function calcularCentroMaquina(int $x1, int $y1, int $x2, int $y2): array
    {
        // Calcular el punto medio de X (promedio entre x1 y x2)
        $centroX = (int) round(($x1 + $x2) / 2);

        // Calcular el punto medio de Y (promedio entre y1 y y2)
        $centroY = (int) round(($y1 + $y2) / 2);

        Log::info("Centro calculado para máquina", [
            'coordenadas_maquina' => "({$x1},{$y1}) a ({$x2},{$y2})",
            'centro_calculado' => "({$centroX},{$centroY})"
        ]);

        return [
            'x' => $centroX,
            'y' => $centroY
        ];
    }

    /**
     * Calcula el tamaño del paquete en celdas basándose en su contenido
     * 
     * Este método obtiene el tamaño físico del paquete (ancho y longitud en metros)
     * a través del accessor 'tamaño' del modelo Paquete, y lo convierte a celdas
     * 
     * @param Paquete $paquete Instancia del paquete
     * @return array Array con 'ancho_celdas' y 'largo_celdas'
     */
    public function calcularTamanoPaqueteEnCeldas(Paquete $paquete): array
    {
        try {
            // Obtener el tamaño del paquete usando el accessor del modelo
            // El accessor 'tamaño' devuelve un array con 'ancho' y 'longitud' en metros
            $tamano = $paquete->tamaño ?? $paquete->tamano ?? ['ancho' => 0.5, 'longitud' => 0.5];

            // Extraer ancho y longitud en metros
            $anchoMetros = (float) ($tamano['ancho'] ?? 0.5);
            $longitudMetros = (float) ($tamano['longitud'] ?? 0.5);

            // Convertir metros a celdas
            // Se usa ceil() para redondear hacia arriba, asegurando que el paquete
            // siempre tenga espacio suficiente (mínimo 1 celda)
            $anchoCeldas = max(1, (int) ceil($anchoMetros / self::METROS_POR_CELDA));
            $largoCeldas = max(1, (int) ceil($longitudMetros / self::METROS_POR_CELDA));

            Log::info("Tamaño de paquete calculado", [
                'paquete_id' => $paquete->id,
                'codigo_paquete' => $paquete->codigo,
                'ancho_metros' => $anchoMetros,
                'longitud_metros' => $longitudMetros,
                'ancho_celdas' => $anchoCeldas,
                'largo_celdas' => $largoCeldas
            ]);

            return [
                'ancho_celdas' => $anchoCeldas,
                'largo_celdas' => $largoCeldas
            ];
        } catch (Exception $e) {
            // En caso de error, usar valores por defecto de 1 celda
            Log::error("Error al calcular tamaño del paquete, usando valores por defecto", [
                'paquete_id' => $paquete->id,
                'error' => $e->getMessage()
            ]);

            return [
                'ancho_celdas' => 1,
                'largo_celdas' => 1
            ];
        }
    }

    /**
     * Calcula las coordenadas finales del paquete centrado en un punto
     * 
     * Dado un punto central y el tamaño del paquete, calcula las coordenadas
     * (x1, y1, x2, y2) de forma que el paquete esté centrado en ese punto
     * 
     * @param int $centroX Coordenada X del centro
     * @param int $centroY Coordenada Y del centro
     * @param int $anchoCeldas Ancho del paquete en celdas
     * @param int $largoCeldas Largo del paquete en celdas
     * @return array Array con 'x1', 'y1', 'x2', 'y2'
     */
    private function calcularCoordenadasPaquete(
        int $centroX,
        int $centroY,
        int $anchoCeldas,
        int $largoCeldas
    ): array {
        // Calcular cuántas celdas hay a cada lado del centro
        // Se usa floor() para la mitad inferior y ceil() para la superior
        // Esto asegura que el paquete esté centrado incluso con tamaños impares

        // Para el ancho (eje X):
        // Si anchoCeldas = 3, mitadAncho = 1, entonces va de (centro-1) a (centro+1)
        // Si anchoCeldas = 4, mitadAncho = 2, entonces va de (centro-2) a (centro+1)
        $mitadAncho = (int) floor($anchoCeldas / 2);
        $x1 = max(1, $centroX - $mitadAncho); // Asegurar que x1 >= 1
        $x2 = $x1 + $anchoCeldas - 1;

        // Para el largo (eje Y):
        $mitadLargo = (int) floor($largoCeldas / 2);
        $y1 = max(1, $centroY - $mitadLargo); // Asegurar que y1 >= 1
        $y2 = $y1 + $largoCeldas - 1;

        Log::info("Coordenadas del paquete calculadas", [
            'centro' => "({$centroX},{$centroY})",
            'tamano' => "{$anchoCeldas}x{$largoCeldas} celdas",
            'coordenadas_finales' => "({$x1},{$y1}) a ({$x2},{$y2})"
        ]);

        return [
            'x1' => $x1,
            'y1' => $y1,
            'x2' => $x2,
            'y2' => $y2
        ];
    }

    /**
     * Asigna automáticamente una localización a un paquete en el centro de la máquina
     *
     * Este es el método principal del servicio. Realiza los siguientes pasos:
     * 1. Obtiene la localización de la máquina (o usa valores por defecto)
     * 2. Calcula el centro de la máquina
     * 3. Calcula el tamaño del paquete
     * 4. Calcula las coordenadas finales del paquete centrado
     * 5. Crea o actualiza el registro en localizaciones_paquetes
     *
     * @param Paquete $paquete Instancia del paquete a localizar
     * @param int $maquinaId ID de la máquina donde se creó el paquete
     * @return LocalizacionPaquete|null Instancia de LocalizacionPaquete si todo OK, null si error
     */
    public function asignarLocalizacionAutomatica(Paquete $paquete, int $maquinaId): ?LocalizacionPaquete
    {
        try {
            Log::info("Iniciando asignación automática de localización", [
                'paquete_id' => $paquete->id,
                'codigo_paquete' => $paquete->codigo,
                'maquina_id' => $maquinaId
            ]);

            // Paso 1: Obtener la localización de la máquina
            $localizacionMaquina = $this->obtenerLocalizacionMaquina($maquinaId);

            // Variables para el centro de la máquina
            $centroX = 5; // Valor por defecto: columna 5
            $centroY = 5; // Valor por defecto: fila 5

            if (!$localizacionMaquina) {
                // Si no se encuentra la localización de la máquina, usar valores por defecto
                // Colocamos el paquete en una zona por defecto (esquina superior izquierda)
                Log::warning("Máquina sin localización, usando posición por defecto para el paquete", [
                    'paquete_id' => $paquete->id,
                    'maquina_id' => $maquinaId,
                    'posicion_defecto' => "({$centroX},{$centroY})"
                ]);
            } else {
                // Paso 2: Calcular el centro de la máquina si existe la localización
                $centro = $this->calcularCentroMaquina(
                    $localizacionMaquina->x1,
                    $localizacionMaquina->y1,
                    $localizacionMaquina->x2,
                    $localizacionMaquina->y2
                );
                $centroX = $centro['x'];
                $centroY = $centro['y'];
            }

            // Paso 3: Calcular el tamaño del paquete en celdas
            $tamanoPaquete = $this->calcularTamanoPaqueteEnCeldas($paquete);

            // Paso 4: Calcular las coordenadas finales del paquete
            $coordenadas = $this->calcularCoordenadasPaquete(
                $centroX,
                $centroY,
                $tamanoPaquete['ancho_celdas'],
                $tamanoPaquete['largo_celdas']
            );

            // Paso 5: Crear o actualizar la localización del paquete
            // updateOrCreate busca si ya existe un registro con ese paquete_id
            // Si existe, lo actualiza; si no existe, lo crea
            $localizacionPaquete = LocalizacionPaquete::updateOrCreate(
                ['paquete_id' => $paquete->id], // Condición de búsqueda
                [
                    'x1' => $coordenadas['x1'],
                    'y1' => $coordenadas['y1'],
                    'x2' => $coordenadas['x2'],
                    'y2' => $coordenadas['y2']
                ]
            );

            Log::info("Localización de paquete asignada exitosamente", [
                'paquete_id' => $paquete->id,
                'localizacion_paquete_id' => $localizacionPaquete->id,
                'coordenadas' => "({$coordenadas['x1']},{$coordenadas['y1']}) a ({$coordenadas['x2']},{$coordenadas['y2']})",
                'centro_usado' => "({$centroX},{$centroY})",
                'tiene_localizacion_maquina' => $localizacionMaquina ? 'sí' : 'no'
            ]);

            return $localizacionPaquete;
        } catch (Exception $e) {
            // Registrar cualquier error que ocurra durante el proceso
            Log::error("Error al asignar localización automática al paquete", [
                'paquete_id' => $paquete->id ?? null,
                'maquina_id' => $maquinaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Verifica si una posición está disponible (no solapada)
     * 
     * Este método opcional puede usarse para verificar que no haya
     * solapamiento con otras localizaciones antes de crear el paquete
     * 
     * @param int $x1 Coordenada X inicial
     * @param int $y1 Coordenada Y inicial
     * @param int $x2 Coordenada X final
     * @param int $y2 Coordenada Y final
     * @param int|null $excluirPaqueteId ID de paquete a excluir de la verificación
     * @return bool True si está disponible, false si hay solapamiento
     */
    public function posicionDisponible(
        int $x1,
        int $y1,
        int $x2,
        int $y2,
        ?int $excluirPaqueteId = null
    ): bool {
        try {
            // Buscar localizaciones de paquetes que solapen con estas coordenadas
            $query = LocalizacionPaquete::where(function ($q) use ($x1, $y1, $x2, $y2) {
                // Lógica de detección de solapamiento:
                // Dos rectángulos NO solapan si:
                // - El x2 de uno es menor que el x1 del otro, O
                // - El x1 de uno es mayor que el x2 del otro, O
                // - El y2 de uno es menor que el y1 del otro, O
                // - El y1 de uno es mayor que el y2 del otro
                // Por tanto, SÍ solapan si NO se cumple ninguna de esas condiciones
                $q->where('x1', '<=', $x2)
                    ->where('x2', '>=', $x1)
                    ->where('y1', '<=', $y2)
                    ->where('y2', '>=', $y1);
            });

            // Si se especifica un paquete a excluir, no considerarlo en la verificación
            if ($excluirPaqueteId) {
                $query->where('paquete_id', '!=', $excluirPaqueteId);
            }

            $solapamientos = $query->count();

            if ($solapamientos > 0) {
                Log::info("Se detectó solapamiento en la posición", [
                    'coordenadas' => "({$x1},{$y1}) a ({$x2},{$y2})",
                    'solapamientos_encontrados' => $solapamientos
                ]);
            }

            // Retorna true si NO hay solapamientos (posición disponible)
            return $solapamientos === 0;
        } catch (Exception $e) {
            Log::error("Error al verificar disponibilidad de posición", [
                'coordenadas' => "({$x1},{$y1}) a ({$x2},{$y2})",
                'error' => $e->getMessage()
            ]);
            // En caso de error, asumir que la posición NO está disponible por seguridad
            return false;
        }
    }

    /**
     * Elimina la localización de un paquete
     * 
     * Útil cuando se elimina un paquete o se necesita recalcular su posición
     * 
     * @param int $paqueteId ID del paquete
     * @return bool True si se eliminó correctamente, false si hubo error
     */
    public function eliminarLocalizacionPaquete(int $paqueteId): bool
    {
        try {
            $eliminados = LocalizacionPaquete::where('paquete_id', $paqueteId)->delete();

            Log::info("Localización de paquete eliminada", [
                'paquete_id' => $paqueteId,
                'registros_eliminados' => $eliminados
            ]);

            return true;
        } catch (Exception $e) {
            Log::error("Error al eliminar localización de paquete", [
                'paquete_id' => $paqueteId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
