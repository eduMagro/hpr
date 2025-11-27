<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SalidasExport implements FromCollection, WithHeadings, WithEvents
{
    protected $salidas;
    protected $empresaSummary;
    protected $clienteObraSummary;

    public function __construct(Collection $salidas, array $empresaSummary, array $clienteObraSummary = [])
    {
        $this->salidas = $salidas;
        $this->empresaSummary = $empresaSummary;
        $this->clienteObraSummary = $clienteObraSummary;
    }

    public function collection()
    {
        $numColumns = 13; // Número total de columnas en la tabla principal (13 en este caso)

        // 🔹 Generar las filas principales de la tabla de salidas usando la relación salidaClientes
        $mainRows = $this->salidas->flatMap(function ($salida) {
            return $salida->salidaClientes->map(function ($registro) use ($salida) {
                // Obtenemos el cliente y la obra desde el registro pivote
                $clienteNombre = $registro->cliente->empresa ?? 'Sin Cliente';
                $obraNombre    = $registro->obra->obra ?? 'Sin Obra';
                $empresaTransporte = $salida->empresaTransporte->nombre ?? 'Desconocida';

                return [
                    $salida->codigo_salida,
                    $clienteNombre,
                    $obraNombre,
                    $empresaTransporte,
                    $salida->camion ? ($salida->camion->modelo . ' - ' . $salida->camion->matricula) : 'Sin camión',
                    $registro->horas_paralizacion ?? 0,
                    number_format($registro->importe_paralizacion ?? 0, 2) . ' €',
                    $registro->horas_grua ?? 0,
                    number_format($registro->importe_grua ?? 0, 2) . ' €',
                    $registro->horas_almacen ?? 0,
                    number_format($registro->importe ?? 0, 2) . ' €',
                    $salida->fecha_salida ?? 'Sin fecha',
                    ucfirst($salida->estado ?? 'pendiente'),
                ];
            });
        })->toArray();

        // 🔹 Construcción del resumen por empresa de transporte
        $blankRow = array_fill(0, $numColumns, ''); // Fila en blanco
        $titleRow = array_pad(['Resumen por Empresa de Transporte'], $numColumns, ''); // Título del resumen
        $summaryHeader = array_pad([
            'Empresa de Transporte',
            'Horas Paralización',
            'Importe Paralización',
            'Horas Grua',
            'Importe Grua',
            'Horas Almacén',
            'Importe',
            'Total Empresa'
        ], $numColumns, '');

        $summaryRows = [];
        foreach ($this->empresaSummary as $empresa => $data) {
            $summaryRows[] = array_pad([
                $empresa,
                $data['horas_paralizacion'] ?? 0,
                number_format($data['importe_paralizacion'] ?? 0, 2) . ' €',
                $data['horas_grua'] ?? 0,
                number_format($data['importe_grua'] ?? 0, 2) . ' €',
                $data['horas_almacen'] ?? 0,
                number_format($data['importe'] ?? 0, 2) . ' €',
                number_format($data['total'] ?? 0, 2) . ' €'
            ], $numColumns, '');
        }

        // 🔹 Construcción del resumen por cliente y obra
        $titleRowClienteObra = array_pad(['Resumen por Cliente y Obra'], $numColumns, '');
        $summaryHeaderClienteObra = array_pad([
            'Cliente - Obra',
            'Horas Paralización',
            'Importe Paralización',
            'Horas Grua',
            'Importe Grua',
            'Horas Almacén',
            'Importe',
            'Total'
        ], $numColumns, '');

        $summaryRowsClienteObra = [];
        foreach ($this->clienteObraSummary as $clienteObra => $data) {
            $summaryRowsClienteObra[] = array_pad([
                $clienteObra,
                $data['horas_paralizacion'] ?? 0,
                number_format($data['importe_paralizacion'] ?? 0, 2) . ' €',
                $data['horas_grua'] ?? 0,
                number_format($data['importe_grua'] ?? 0, 2) . ' €',
                $data['horas_almacen'] ?? 0,
                number_format($data['importe'] ?? 0, 2) . ' €',
                number_format($data['total'] ?? 0, 2) . ' €'
            ], $numColumns, '');
        }

        // 🔹 Combinar la tabla principal con los dos resúmenes
        $allRows = array_merge(
            $mainRows,
            [$blankRow, $titleRow, $summaryHeader],
            $summaryRows,
            [$blankRow, $titleRowClienteObra, $summaryHeaderClienteObra],
            $summaryRowsClienteObra
        );

        return collect($allRows);
    }

    public function headings(): array
    {
        return [
            'Salida',
            'Cliente',
            'Obra',
            'Empresa de Transporte',
            'Camión',
            'Horas Paralización',
            'Importe Paralización',
            'Horas Grua',
            'Importe Grua',
            'Horas Almacén',
            'Importe',
            'Fecha',
            'Estado'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // 🔹 Estilo de la cabecera principal: fondo azul claro y negrita
                $headerStyle = [
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => '000000'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'ADD8E6'],
                    ],
                ];

                // 🔹 Aplicar estilo a la cabecera principal (A1:M1)
                $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

                // 🔹 Calcular la fila del título del primer resumen (Empresa Transporte)
                // Se asume que hay 1 fila de cabecera + las filas generadas por las salidas
                $mainRowsCount = count($this->salidas->flatMap(function ($salida) {
                    return $salida->salidaClientes;
                })) + 1;
                $blankRowNum = $mainRowsCount + 1;
                $summaryTitleRow = $blankRowNum + 1;
                $summaryHeaderRow = $summaryTitleRow + 1;

                // 🔹 Aplicar negrita al título del primer resumen
                $sheet->getStyle("A{$summaryTitleRow}")->applyFromArray(['font' => ['bold' => true]]);

                // 🔹 Aplicar estilo al encabezado del resumen por empresa
                $sheet->getStyle("A{$summaryHeaderRow}:H{$summaryHeaderRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'ADD8E6'],
                    ],
                ]);

                // 🔹 Calcular la fila del título del segundo resumen (Cliente y Obra)
                $empresaSummaryRowsCount = count($this->empresaSummary);
                $secondBlankRowNum = $summaryHeaderRow + $empresaSummaryRowsCount + 1;
                $secondSummaryTitleRow = $secondBlankRowNum + 1;
                $secondSummaryHeaderRow = $secondSummaryTitleRow + 1;

                // 🔹 Aplicar negrita al título del segundo resumen
                $sheet->getStyle("A{$secondSummaryTitleRow}")->applyFromArray(['font' => ['bold' => true]]);

                // 🔹 Aplicar estilo al encabezado del resumen por cliente y obra
                $sheet->getStyle("A{$secondSummaryHeaderRow}:H{$secondSummaryHeaderRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'ADD8E6'],
                    ],
                ]);
            },
        ];
    }
}
