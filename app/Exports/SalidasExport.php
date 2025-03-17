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

    public function __construct(Collection $salidas)
    {
        $this->salidas = $salidas;
    }

    public function collection()
    {
        $numColumns = 14; // Total de columnas según la vista

        // Calcular los campos dinámicos para cada salida
        // Asumimos que la relación "paquetes" está cargada
        $this->salidas->each(function ($salida) {
            // Extraer planillas únicas de la relación "paquetes"
            $planillas = $salida->paquetes->pluck('planilla')->unique()->filter()->values();
            $salida->planillasUnicas = $planillas;

            // Clientes únicos: extraer el atributo "cliente" de cada planilla
            $salida->clientesUnicos = collect($planillas)
                ->map(fn($planilla) => $planilla->cliente)
                ->unique()
                ->filter()
                ->values();

            // Obras únicas: extraer el atributo "nom_obra" de cada planilla
            $salida->obrasUnicas = collect($planillas)
                ->map(fn($planilla) => $planilla->nom_obra)
                ->unique()
                ->filter()
                ->values();
        });

        // Construir las filas principales usando los valores ya computados
        $mainRows = $this->salidas->map(function ($salida) {
            return [
                $salida->codigo_salida,                                      // Salida
                implode(', ', $salida->clientesUnicos->toArray()),            // Cliente(s)
                implode(', ', $salida->obrasUnicas->toArray()),               // Obra(s)
                $salida->empresaTransporte->nombre,                           // Empresa
                $salida->camion->modelo . ' - ' . $salida->camion->matricula,   // Camión
                $salida->horas_paralizacion ?? '0',                             // Horas paralización
                $salida->importe_paralizacion ?? '0',                           // Importe paralización
                $salida->horas_grua ?? '0',                                     // Horas Grua
                $salida->importe_grua ?? '0',                                   // Importe Grua
                $salida->horas_almacen ?? '0',                                  // Horas Almacén
                $salida->importe ?? '0',                                        // Importe
                $salida->fecha_salida ?? 'Sin fecha',                           // Fecha
                ucfirst($salida->estado)
            ];
        })->toArray();

        // Calcular el resumen por cliente
        $clientSummary = [];
        foreach ($this->salidas as $salida) {
            $importe = $salida->importe ?? 0;
            foreach ($salida->clientesUnicos as $cliente) {
                if ($cliente) {
                    if (!isset($clientSummary[$cliente])) {
                        $clientSummary[$cliente] = 0;
                    }
                    $clientSummary[$cliente] += $importe;
                }
            }
        }

        // Preparar el bloque de resumen por cliente
        $blankRow = array_fill(0, $numColumns, '');
        $titleRow = array_pad(['Resumen por Cliente'], $numColumns, '');
        $summaryHeader = array_pad(['Cliente', 'Total Importe'], $numColumns, '');
        $summaryRows = [];
        foreach ($clientSummary as $cliente => $total) {
            $summaryRows[] = array_pad([$cliente, number_format($total, 2) . ' €'], $numColumns, '');
        }

        // Combinar la tabla principal y el bloque del resumen
        $allRows = array_merge($mainRows, [$blankRow, $titleRow, $summaryHeader], $summaryRows);

        return collect($allRows);
    }

    public function headings(): array
    {
        return [
            'Salida',
            'Cliente',
            'Obra',
            'Empresa',
            'Camión',
            'Horas paralización',
            'Importe paralización',
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
                // Definir el estilo: fondo azul claro (ADD8E6) y letra negra en negrita
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

                // Aplicar el estilo a la fila de encabezados principal (fila 1)
                $event->sheet->getDelegate()->getStyle('A1:M1')->applyFromArray($headerStyle);

                // Calcular la fila del título del resumen (fila en blanco + título)
                $mainRowsCount = count($this->salidas);
                $summaryTitleRow = $mainRowsCount + 3;

                // Aplicar el estilo solo a la celda A del título del resumen
                $event->sheet->getDelegate()->getStyle("A{$summaryTitleRow}")->applyFromArray($headerStyle);
            },
        ];
    }
}
