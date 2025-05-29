<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductosExport implements FromCollection
{
    protected $codigos;

    public function __construct($codigos)
    {
        $this->codigos = $codigos;
    }

    public function collection()
    {
        return collect($this->codigos);
    }
}
