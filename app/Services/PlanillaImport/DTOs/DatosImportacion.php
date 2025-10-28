<?php

namespace App\Services\PlanillaImport\DTOs;

/**
 * Encapsula los datos leídos del Excel para importación.
 */
class DatosImportacion
{
    public function __construct(
        protected array $filas = [],
        protected array $estadisticas = []
    ) {}

    public static function vacio(array $errores = [], array $estadisticas = []): self
    {
        return new self([], array_merge($estadisticas, ['errores' => $errores]));
    }

    public function estaVacio(): bool
    {
        return empty($this->filas);
    }

    public function filas(): array
    {
        return $this->filas;
    }

    public function totalFilas(): int
    {
        return $this->estadisticas['total_filas'] ?? 0;
    }

    public function filasValidas(): int
    {
        return count($this->filas);
    }

    public function estadisticas(): array
    {
        return $this->estadisticas;
    }

    /**
     * Agrupa las filas por código de planilla.
     *
     * @return array ['codigo_planilla' => [filas...], ...]
     */
    public function agruparPorPlanilla(): array
    {
        $grupos = [];

        foreach ($this->filas as $fila) {
            $codigo = (string)($fila[10] ?? 'Sin código'); // Columna 10 = código planilla
            $grupos[$codigo][] = $fila;
        }

        return $grupos;
    }

    /**
     * Retorna los códigos únicos de planillas detectados.
     *
     * @return array
     */
    public function codigosPlanillas(): array
    {
        return array_keys($this->agruparPorPlanilla());
    }

    /**
     * Retorna el número de planillas detectadas.
     *
     * @return int
     */
    public function planillasDetectadas(): int
    {
        return count($this->codigosPlanillas());
    }

    /**
     * Filtra las filas excluyendo los códigos de planilla especificados.
     *
     * @param array $codigosAExcluir Códigos de planilla a filtrar
     * @return self Nueva instancia con filas filtradas
     */
    public function filtrarPlanillas(array $codigosAExcluir): self
    {
        if (empty($codigosAExcluir)) {
            return $this;
        }

        $filasFiltradas = array_filter($this->filas, function ($fila) use ($codigosAExcluir) {
            $codigoFila = (string)($fila[10] ?? ''); // Columna 10 = código planilla
            return !in_array($codigoFila, $codigosAExcluir, true);
        });

        // Mantener estadísticas originales pero actualizar conteo
        $estadisticasActualizadas = $this->estadisticas;
        $estadisticasActualizadas['filas_filtradas'] = count($this->filas) - count($filasFiltradas);
        $estadisticasActualizadas['planillas_excluidas'] = count($codigosAExcluir);

        return new self(
            array_values($filasFiltradas), // Reindexar array
            $estadisticasActualizadas
        );
    }
}
