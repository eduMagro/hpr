<?php

namespace App\Services;

use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\Planilla;
use App\Models\Movimiento;
use App\Models\Paquete;
use App\Models\OrdenPlanilla;
use App\Models\Maquina;
use App\Models\ProductoBase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class CompletarLoteService
{
    public function completarLote(array $etiquetas, int $maquinaId): array
    {
        $completadas = 0;
        $errors = [];
        $planillasARevisar = [];
        $algunaFalló = false;

        foreach ($etiquetas as $identificador) {
            try {
                $etiqueta = Etiqueta::where('codigo', $identificador)
                    ->orWhere('etiqueta_sub_id', $identificador)
                    ->firstOrFail();

                if (in_array($etiqueta->estado, ['completada', 'fabricada'])) {
                    throw new \Exception("La etiqueta {$etiqueta->codigo} ya fue completada.");
                }

                $maquina = Maquina::findOrFail($maquinaId);
                $enOtrasMaquinas = $etiqueta->elementos()
                    ->where('maquina_id', '!=', $maquina->id)
                    ->exists();

                $elementosEnMaquina = $etiqueta->elementos()
                    ->where('maquina_id', $maquina->id)
                    ->get();

                $productosAfectados = [];
                $numeroCompletados = 0;
                $warnings = [];

                DB::beginTransaction();

                $res = $this->actualizarElementosYConsumos(
                    $elementosEnMaquina,
                    $maquina,
                    $etiqueta,
                    $warnings,
                    $numeroCompletados,
                    $enOtrasMaquinas,
                    $productosAfectados,
                    $planilla
                );

                if ($res !== true) {
                    throw new \Exception($res['error'] ?? 'Error al completar etiqueta');
                }

                if ($numeroCompletados > 0) {
                    DB::commit();
                    $this->empaquetarEtiqueta($etiqueta);
                    $completadas++;

                    $planilla = $etiqueta->planilla;
                    if ($planilla && !in_array($planilla->id, $planillasARevisar)) {
                        $planillasARevisar[] = $planilla->id;
                    }
                } else {
                    DB::rollBack();
                    throw new \Exception("No se pudo completar la etiqueta {$etiqueta->codigo} porque no se fabricó ningún elemento.");
                }

                Log::info("Etiqueta {$etiqueta->codigo} completada con éxito.");
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("Error al completar etiqueta $identificador: " . $e->getMessage());
                $errors[] = [
                    'id' => $identificador,
                    'error' => $e->getMessage(),
                    'line' => $e->getLine(),
                ];
                $algunaFalló = true;
            }
        }

        // 🛑 Si falló al menos una etiqueta, NO tocar la orden de planillas
        if ($algunaFalló) {
            return [
                'success' => false,
                'message' => "Solo se completaron {$completadas} etiquetas. Al menos una falló.",
                'errors' => $errors,
            ];
        }

        // ✅ Cierre de planillas solo si todas fueron correctas
        $completadasPlanillas = [];
        foreach ($planillasARevisar as $pid) {
            $res = $this->finalizarPlanillaSiCorresponde($pid, $maquinaId);
            if (!empty($res['success'])) {
                $completadasPlanillas[] = $pid;
            } else {
                if (!str_contains(($res['message'] ?? ''), 'Aún hay elementos')) {
                    $errors[] = ['planilla' => $pid, 'error' => $res['message'] ?? 'Fallo al cerrar planilla'];
                }
            }
        }

        return [
            'success' => true,
            'message' => "Se completaron {$completadas} etiquetas y " . count($completadasPlanillas) . " planillas.",
            'errors' => $errors,
            'planillas_cerradas' => $completadasPlanillas,
        ];
    }


    // Copiar aquí tu método actualizarElementosYConsumos completo (sin cambios)
    private function actualizarElementosYConsumos($elementosEnMaquina, $maquina, &$etiqueta, &$warnings, &$numeroElementosCompletadosEnMaquina, $enOtrasMaquinas, &$productosAfectados, &$planilla)
    {

        foreach ($elementosEnMaquina as $elemento) {
            $elemento->estado = "fabricado";
            $elemento->save();
        }

        // ✅ ACTUALIZAR EL CONTADOR DE ELEMENTOS COMPLETADOS
        $numeroElementosCompletadosEnMaquina = $elementosEnMaquina->where('estado', 'fabricado')->count();

        // -------------- CONSUMOS
        $consumos = [];
        foreach ($elementosEnMaquina->groupBy('diametro') as $diametro => $elementos) {
            // Si la máquina es ID 7, solo permitir diámetro 5
            if ($maquina->tipo == 'ensambladora' && $diametro != 5) {
                continue; // Saltar cualquier otro diámetro
            }
            $pesoNecesarioTotal = $elementos->sum('peso');

            $productosPorDiametro = $maquina->productos()
                ->whereHas('productoBase', function ($query) use ($diametro) {
                    $query->where('diametro', $diametro);
                })
                ->with('productoBase')
                ->orderBy('peso_stock')
                ->get();

            if ($productosPorDiametro->isEmpty()) {
                throw new \Exception("No se encontraron materias primas para el diámetro {$diametro}.");
            }


            $consumos[$diametro] = [];
            foreach ($productosPorDiametro as $producto) {
                if ($pesoNecesarioTotal <= 0) break;

                $pesoInicial = $producto->peso_inicial ?? $producto->peso_stock;

                $restar = min($producto->peso_stock, $pesoNecesarioTotal);
                $producto->peso_stock -= $restar;
                $pesoNecesarioTotal -= $restar;

                if ($producto->peso_stock == 0) {
                    $producto->estado = "consumido";
                    $producto->ubicacion_id = null;
                    $producto->maquina_id = null;
                }

                $producto->save();

                $productosAfectados[] = [
                    'id' => $producto->id,
                    'peso_stock' => $producto->peso_stock,
                    'peso_inicial' => $pesoInicial,
                ];

                $consumos[$diametro][] = [
                    'producto_id' => $producto->id,
                    'consumido' => $restar,
                ];
            }
            if ($pesoNecesarioTotal > 0) {

                $productoBase = ProductoBase::where('diametro', $diametro)
                    ->where('tipo', $maquina->tipo_material)
                    ->first();

                if (!$productoBase) {
                    Log::warning("No se encontró ProductoBase Ø{$diametro} / tipo {$maquina->tipo_material}");
                    DB::rollBack();
                    return [
                        'success' => false,
                        'error'   => "No existe materia prima para Ø{$diametro} mm (tipo {$maquina->tipo_material}).",
                    ];
                }

                DB::rollBack();

                // Encapsulamos el aviso al gruista
                $this->avisarGruistaRecarga($productoBase, $maquina);

                // ❌ Lanzamos excepción para detener el proceso
                throw new \Exception("No hay suficiente materia prima para Ø{$diametro} mm en {$maquina->nombre}. Se ha solicitado recarga al gruista.");
            }
        }

        // ✅ Asignar productos consumidos a los elementos
        foreach ($elementosEnMaquina as $elemento) {
            $pesoRestanteElemento = $elemento->peso;
            $consumosDisponibles = $consumos[$elemento->diametro] ?? [];
            $productosAsignados = [];

            while ($pesoRestanteElemento > 0 && count($consumosDisponibles) > 0) {
                $consumo = &$consumosDisponibles[0];

                if ($consumo['consumido'] <= $pesoRestanteElemento) {
                    $productosAsignados[] = $consumo['producto_id'];
                    $pesoRestanteElemento -= $consumo['consumido'];
                    array_shift($consumosDisponibles);
                } else {
                    $productosAsignados[] = $consumo['producto_id'];
                    $consumo['consumido'] -= $pesoRestanteElemento;
                    $pesoRestanteElemento = 0;
                }
            }

            $elemento->producto_id = $productosAsignados[0] ?? null;
            $elemento->producto_id_2 = $productosAsignados[1] ?? null;
            $elemento->producto_id_3 = $productosAsignados[2] ?? null;
            $elemento->save();
        }

        // ✅ Lógica de "TALLER" y "CARCASAS"

        $planilla = $etiqueta->planilla;
        $ensambladoText = strtolower($planilla->ensamblado ?? '');
        if (!$planilla) {
            throw new \Exception("La etiqueta {$etiqueta->codigo} no tiene planilla asociada.");
        }

        if (str_contains($ensambladoText, 'taller')) {
            // Verificar si todos los elementos de la etiqueta están en estado "completado"
            $elementosEtiquetaCompletos = $etiqueta->elementos()->where('estado', '!=', 'fabricado')->doesntExist();
            if (str_contains($planilla->comentario, 'amarrado')) {
            } elseif (str_contains($planilla->comentario, 'ensamblado amarrado')) {
            } else {
                // Verificar si TODOS los elementos de la máquina actual están completados
                if ($elementosEnMaquina->count() > 0 && $numeroElementosCompletadosEnMaquina >= $elementosEnMaquina->count()) {
                    // Si la etiqueta tiene elementos en otras máquinas, marcamos como parcialmente completada
                    if ($enOtrasMaquinas) {
                        $etiqueta->estado = 'parcialmente_completada';
                    } else {
                        // Si no hay elementos en otras máquinas, se marca como fabricada/completada
                        $etiqueta->estado = 'fabricada';
                        $etiqueta->fecha_finalizacion = now();
                    }

                    $etiqueta->save();
                }
                // Buscar una máquina de soldar disponible
                $maquinaSoldarDisponible = Maquina::whereRaw('LOWER(nombre) LIKE LOWER(?)', ['%soldadora%'])
                    ->whereDoesntHave('elementos')
                    ->first();

                if (!$maquinaSoldarDisponible) {
                    $maquinaSoldarDisponible = Maquina::whereRaw('LOWER(nombre) LIKE LOWER(?)', ['%soldadora%'])
                        ->whereHas('elementos', function ($query) {
                            $query->orderBy('created_at');
                        })
                        ->first();
                }

                if ($maquinaSoldarDisponible) {
                    foreach ($elementosEnMaquina as $elemento) {
                        $elemento->maquina_id_3 = $maquinaSoldarDisponible->id;
                        $elemento->save();
                    }
                } else {
                    throw new \Exception("No se encontró una máquina de soldar disponible para taller.");
                }
            }
        } elseif (str_contains($ensambladoText, 'carcasas')) {
            $elementosEtiquetaCompletos = $etiqueta->elementos()
                ->where('diametro', '!=', 5.00)
                ->where('estado', '!=', 'fabricado')
                ->doesntExist();

            if ($elementosEtiquetaCompletos) {
                $etiqueta->estado = $maquina->tipo === 'estribadora' ? 'fabricada' : 'completada';
                $etiqueta->fecha_finalizacion = now();
                $etiqueta->save();
            }

            // 🔧 Solo si la máquina actual no es cortadora_dobladora
            if ($maquina->tipo !== 'cortadora_dobladora') {
                $maquinaEnsambladora = Maquina::where('tipo', 'ensambladora')->first();

                if ($maquinaEnsambladora) {
                    foreach ($elementosEnMaquina as $elemento) {
                        if (is_null($elemento->maquina_id_2)) {
                            $elemento->maquina_id_2 = $maquinaEnsambladora->id;
                            $elemento->save();
                        }
                    }
                }
            }
        } else {

            // Verificar si todos los elementos de la etiqueta están en estado "completado"
            $elementosEtiquetaCompletos = $etiqueta->elementos()->where('estado', '!=', 'fabricado')->doesntExist();

            if ($elementosEtiquetaCompletos) {
                $etiqueta->estado = 'completada';
                $etiqueta->fecha_finalizacion = now();
                $etiqueta->save();
            } else {
                // Si la etiqueta tiene elementos en otras máquinas, marcamos como parcialmente completada
                if ($enOtrasMaquinas) {
                    $etiqueta->estado = 'parcialmente_completada';
                    $etiqueta->save();
                }
            }
        }

        return true;
    }

    protected function generarMovimientoRecargaMateriaPrima(
        ProductoBase $productoBase,
        Maquina $maquina,
        ?int $productoId = null
    ): void {
        try {
            Movimiento::create([
                'tipo'              => 'Recarga materia prima',
                'maquina_origen'    => null,
                'maquina_destino'   => $maquina->id,
                'producto_id'       => $productoId,
                'producto_base_id'  => $productoBase->id,
                'estado'            => 'pendiente',
                'descripcion'       => "Se solicita materia prima del tipo "
                    . strtolower($productoBase->tipo)
                    . " (Ø{$productoBase->diametro}, {$productoBase->longitud} mm) "
                    . "en la máquina {$maquina->nombre}",
                'prioridad'         => 1,
                'fecha_solicitud'   => now(),
                'solicitado_por'    => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            // Lo registras y vuelves a lanzar una excepción más “amigable”
            Log::error('Error al crear movimiento de recarga', [
                'maquina_id' => $maquina->id,
                'producto_base_id' => $productoBase->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('No se pudo registrar la solicitud de recarga de materia prima.');
        }
    }
    private function avisarGruistaRecarga(ProductoBase $productoBase, Maquina $maquina): void
    {
        $yaExiste = Movimiento::where('tipo', 'Recarga materia prima')
            ->where('producto_base_id', $productoBase->id)
            ->where('maquina_destino', $maquina->id)
            ->where('estado', 'pendiente')
            ->exists();

        if ($yaExiste) {
            Log::info("⏭️ Movimiento de recarga ya existente para Ø{$productoBase->diametro} en {$maquina->nombre}");
            return;
        }

        $this->generarMovimientoRecargaMateriaPrima($productoBase, $maquina);
        Log::info("✅ Se solicitó recarga de Ø{$productoBase->diametro} en {$maquina->nombre}");
    }

    public function finalizarPlanillaSiCorresponde($planillaId, $maquinaId)
    {
        $planilla = Planilla::with('etiquetas')->find($planillaId);

        if (!$planilla) {
            return [
                'success' => false,
                'message' => 'Planilla no encontrada.',
            ];
        }

        $todasEtiquetasCompletadas = $planilla->etiquetas
            ->every(fn($etq) => $etq->estado === 'completada');

        try {
            DB::transaction(function () use ($planilla, $maquinaId, $todasEtiquetasCompletadas) {

                // Eliminar orden y reordenar
                OrdenPlanilla::where('planilla_id', $planilla->id)
                    ->where('maquina_id', $maquinaId)
                    ->delete();

                $restantes = OrdenPlanilla::where('maquina_id', $maquinaId)
                    ->orderBy('posicion')
                    ->lockForUpdate()
                    ->get();

                foreach ($restantes as $index => $orden) {
                    $orden->posicion = $index;
                    if (!$orden->save()) {
                        throw new \Exception("No se pudo actualizar la posición de la orden de planilla ID {$orden->id}");
                    }
                }
            });

            return [
                'success' => true,
                'message' => $todasEtiquetasCompletadas
                    ? 'Planilla completada y empaquetada. Cola actualizada.'
                    : 'Planilla en proceso. Cola actualizada.',
            ];
        } catch (\Exception $e) {
            Log::error("Error al finalizar planilla {$planillaId}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }


    public function empaquetarEtiqueta(Etiqueta $etiqueta): void
    {
        $codigoPaquete = Paquete::generarCodigo();

        $paquete = Paquete::create([
            'codigo'      => $codigoPaquete,
            'planilla_id' => $etiqueta->planilla_id,
            'peso'        => $etiqueta->peso ?? 0,
        ]);

        if (!$paquete || !$paquete->exists) {
            Log::error("No se pudo crear el paquete para la etiqueta {$etiqueta->codigo}");
            throw new \Exception("No se pudo crear el paquete para la etiqueta {$etiqueta->codigo}");
        }

        $etiqueta->paquete_id = $paquete->id;

        if (!$etiqueta->save()) {
            Log::error("❌ No se pudo actualizar la etiqueta {$etiqueta->codigo} con el paquete asignado.");
            throw new \Exception("No se pudo actualizar la etiqueta {$etiqueta->codigo} con el paquete asignado.");
        }

        Log::info("✅ Etiqueta {$etiqueta->codigo} completada y asignada al paquete {$codigoPaquete}");
    }
}
