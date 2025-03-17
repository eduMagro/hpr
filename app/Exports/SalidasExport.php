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
    protected $clientSummary;

    public function __construct(Collection $salidas, array $clientSummary)
    {
        $this->salidas = $salidas;
        $this->clientSummary = $clientSummary;
    }

    public function collection()
    {
        $numColumns = 14; // Número total de columnas en la tabla principal

        // Construir las filas principales de la tabla de salidas
        $mainRows = $this->salidas->map(function ($salida) {
            return [
                $salida->codigo_salida,                                     // Código de salida
                implode(', ', $salida->clientes->pluck('empresa')->toArray()), // Cliente(s)
                $cliente->obrasUnicas ?? 'N/A', // Obra(s)
                $salida->empresaTransporte->nombre,                         // Empresa de transporte
                $salida->camion->modelo . ' - ' . $salida->camion->matricula, // Modelo y matrícula del camión
                $salida->clientes->sum('pivot.horas_paralizacion') ?? '0',  // Horas de paralización
                $salida->clientes->sum('pivot.importe_paralizacion') ?? '0', // Importe paralización
                $salida->clientes->sum('pivot.horas_grua') ?? '0',          // Horas grúa
                $salida->clientes->sum('pivot.importe_grua') ?? '0',        // Importe grúa
                $salida->clientes->sum('pivot.horas_almacen') ?? '0',       // Horas almacén
                $salida->clientes->sum('pivot.importe') ?? '0',             // Importe total
                $salida->fecha_salida ?? 'Sin fecha',                       // Fecha de salida
                ucfirst($salida->estado)                                    // Estado de la salida
            ];
        })->toArray();

        // Construcción del resumen por cliente
        $blankRow = array_fill(0, $numColumns, ''); // Fila en blanco
        $titleRow = array_pad(['Resumen por Cliente'], $numColumns, ''); // Título del resumen
        $summaryHeader = array_pad([
            'Cliente',
            'Horas Paralización',
            'Importe Paralización',
            'Horas Grúa',
            'Importe Grúa',
            'Horas Almacén',
            'Importe Almacén',
            'Total Cliente'
        ], $numColumns, '');

        $summaryRows = [];
        foreach ($this->clientSummary as $cliente => $data) {
            $summaryRows[] = array_pad([
                $cliente,
                $data['horas_paralizacion'],
                number_format($data['importe_paralizacion'], 2) . ' €',
                $data['horas_grua'],
                number_format($data['importe_grua'], 2) . ' €',
                $data['horas_almacen'],
                number_format($data['importe'], 2) . ' €',
                number_format($data['total'], 2) . ' €'
            ], $numColumns, '');
        }

        // Combinar las tablas en un solo array
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
                // Definir estilo de cabecera (fondo azul claro)
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

                // Aplicar estilo a la cabecera principal (A1:M1)
                $event->sheet->getDelegate()->getStyle('A1:M1')->applyFromArray($headerStyle);

                // Aplicar estilo al título del resumen por cliente
                $mainRowsCount = count($this->salidas);
                $summaryTitleRow = $mainRowsCount + 3;
                $event->sheet->getDelegate()->getStyle("A{$summaryTitleRow}")->applyFromArray($headerStyle);
            },
        ];
    }
}
