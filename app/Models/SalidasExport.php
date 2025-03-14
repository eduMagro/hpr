<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class SalidasExport implements FromCollection, WithHeadings
{
    protected $salidas;

    public function __construct(Collection $salidas)
    {
        $this->salidas = $salidas;
    }

    public function collection()
    {
        // Convierte tus salidas en un collection de arreglos, por ejemplo:
        return $this->salidas->map(function ($salida) {
            return [
                'Código Salida'      => $salida->codigo_salida,
                'Cliente(s)'         => implode(', ', $salida->clientesUnicos),
                'Obra(s)'            => implode(', ', $salida->obrasUnicas),
                'Empresa'            => $salida->empresaTransporte->nombre,
                'Camión'             => $salida->camion->modelo . ' - ' . $salida->camion->matricula,
                'Importe'            => $salida->importe,
                'Paralización'       => $salida->paralizacion,
                'Horas'              => $salida->horas,
                'Horas/Almacén'      => $salida->horas_almacen,
                'Fecha Salida'       => $salida->fecha_salida,
                'Estado'             => $salida->estado,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Código Salida',
            'Cliente(s)',
            'Obra(s)',
            'Empresa',
            'Camión',
            'Importe',
            'Paralización',
            'Horas',
            'Horas/Almacén',
            'Fecha Salida',
            'Estado'
        ];
    }
}
