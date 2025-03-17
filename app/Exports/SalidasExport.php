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

        // 🔹 Generar las filas principales de la tabla de salidas
        $mainRows = $this->salidas->flatMap(function ($salida) {
            return $salida->clientes->map(function ($cliente) use ($salida) {
                return [
                    $salida->codigo_salida, // Código de salida
                    $cliente->empresa, // Cliente
                    $cliente->obrasUnicas->implode(', ') ?? 'N/A', // 🔹 Usar obrasUnicas correctamente
                    $salida->empresaTransporte->nombre, // Empresa de transporte
                    $salida->camion->modelo . ' - ' . $salida->camion->matricula, // Modelo y matrícula del camión
                    $cliente->pivot->horas_paralizacion ?? 0, // 🔹 Horas de paralización
                    number_format($cliente->pivot->importe_paralizacion ?? 0, 2) . ' €', // 🔹 Importe paralización
                    $cliente->pivot->horas_grua ?? 0, // 🔹 Horas grúa
                    number_format($cliente->pivot->importe_grua ?? 0, 2) . ' €', // 🔹 Importe grúa
                    $cliente->pivot->horas_almacen ?? 0, // 🔹 Horas almacén
                    number_format($cliente->pivot->importe ?? 0, 2) . ' €', // 🔹 Importe total
                    $salida->fecha_salida ?? 'Sin fecha', // Fecha de salida
                    ucfirst($salida->estado), // Estado de la salida
                ];
            });
        })->toArray();

        // 🔹 Construcción del resumen por cliente
        $blankRow = array_fill(0, $numColumns, ''); // Fila en blanco
        $titleRow = array_pad(['Resumen por Cliente'], $numColumns, ''); // Título del resumen
        $summaryHeader = array_pad([
            'Cliente',
            'Horas Paralización',
            'Total Importe Paralización',
            'Horas Grúa',
            'Total Importe Grúa',
            'Horas Almacén',
            'Total Importe',
            'Total Cliente'
        ], $numColumns, '');

        $summaryRows = [];
        foreach ($this->clientSummary as $cliente => $data) {
            $summaryRows[] = array_pad([
                $cliente,
                $data['horas_paralizacion'] ?? 0, // 🔹 Convertir valores null en 0
                number_format($data['importe_paralizacion'] ?? 0, 2) . ' €',
                number_format($data['horas_grua'] ?? 0, 2),
                number_format($data['importe_grua'] ?? 0, 2) . ' €',
                number_format($data['horas_almacen'] ?? 0, 2),
                number_format($data['importe'] ?? 0, 2) . ' €',
                number_format($data['total'] ?? 0, 2) . ' €'
            ], $numColumns, '');
        }

        // 🔹 Combinar la tabla de salidas con el resumen
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
                $sheet = $event->sheet->getDelegate();

                // 🔹 Definir estilo de cabecera (fondo azul claro y negrita)
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

                // 🔹 Calcular fila del título del resumen
                $mainRowsCount = count($this->salidas) + 2; // Sumamos la cabecera y el espacio en blanco
                $summaryTitleRow = $mainRowsCount + 1;
                $summaryHeaderRow = $summaryTitleRow + 1;

                // 🔹 Aplicar negrita al título del resumen
                $sheet->getStyle("A{$summaryTitleRow}")->applyFromArray(['font' => ['bold' => true]]);

                // 🔹 Aplicar negrita al encabezado del resumen por cliente
                $sheet->getStyle("A{$summaryHeaderRow}:H{$summaryHeaderRow}")->applyFromArray([
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
