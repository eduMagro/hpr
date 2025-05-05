<?php
// app/Console/Commands/ImportarFestivos.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Festivo;

class ImportarFestivos extends Command
{
    protected $signature = 'festivos:importar {anio?}';
    protected $description = 'Importa festivos nacionales, autonómicos y locales para un año dado';

    public function handle()
    {
        $anio = $this->argument('anio') ?? date('Y');

        $this->info("Importando festivos para el año $anio...");

        $response = Http::get("https://date.nager.at/api/v3/PublicHolidays/$anio/ES");

        if ($response->failed()) {
            $this->error("Error al obtener festivos.");
            return;
        }

        $festivos = collect($response->json())->filter(function ($holiday) {
            return !isset($holiday['counties']) || in_array('ES-AN', $holiday['counties']);
        });

        foreach ($festivos as $holiday) {
            Festivo::updateOrCreate(
                ['fecha' => $holiday['date'], 'anio' => $anio],
                [
                    'titulo' => $holiday['localName'],
                    'editable' => true, // Ahora todos los festivos serán editables
                ]
            );
        }

        // Festivos locales de Los Palacios
        Festivo::updateOrCreate(
            ['fecha' => "$anio-08-05", 'anio' => $anio],
            ['titulo' => 'Festividad de Nuestra Señora de las Nieves', 'editable' => true]
        );

        Festivo::updateOrCreate(
            ['fecha' => "$anio-09-25", 'anio' => $anio],
            ['titulo' => 'Feria Los Palacios y Vfca', 'editable' => true]
        );

        $this->info("Festivos importados correctamente.");
    }
}
