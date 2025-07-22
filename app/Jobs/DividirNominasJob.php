<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use setasign\Fpdi\Fpdi;
use App\Models\User;
use App\Models\Alerta;
use App\Models\AlertaLeida;

use Illuminate\Support\Facades\DB;

class DividirNominasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rutaAbsoluta;
    protected $mesAnio;

   protected $userId;

    public function __construct($rutaAbsoluta, $mesAnio, $userId)
    {
        $this->rutaAbsoluta = $rutaAbsoluta;
        $this->mesAnio = $mesAnio;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        set_time_limit(0); // sin límite en job

        // Parseamos mes y año
        $fecha = Carbon::createFromFormat('Y-m', $this->mesAnio);
        $mesEnEspañol = ucfirst($fecha->locale('es')->translatedFormat('F'));
        $anio = $fecha->format('Y');

        // Carpeta final
        $carpetaBaseRelativa = 'private/nominas/nominas_' . $anio . '/nomina_' . $mesEnEspañol . '_' . $anio;
        Storage::deleteDirectory($carpetaBaseRelativa);
        Storage::makeDirectory($carpetaBaseRelativa);
        $carpetaBaseAbsoluta = storage_path('app/' . $carpetaBaseRelativa);

        // mapa de DNIs
        $usuarios = User::all();
        $mapaDnis = [];
        foreach ($usuarios as $u) {
            if ($u->dni) {
                $dniNormalizado = strtoupper(preg_replace('/[^A-Z0-9]/', '', $u->dni));
                $mapaDnis[$dniNormalizado] = true;
            }
        }

        // Dividir PDF
        $parser = new Parser();
        $pdf = $parser->parseFile($this->rutaAbsoluta);
        $pages = $pdf->getPages();
        $dnisNoEncontrados = [];

        foreach ($pages as $i => $page) {
            $textoPagina = strtoupper($page->getText());
            $textoNormalizado = preg_replace('/\s+/', '', $textoPagina);

            $pos = strpos($textoNormalizado, 'NºAFILIACION');
            $dniExtraido = null;
            if ($pos !== false && $pos >= 9) {
                $dniExtraido = substr($textoNormalizado, $pos - 9, 9);
                $dniExtraido = strtoupper(preg_replace('/[^A-Z0-9]/', '', $dniExtraido));
            }

            $nombreCarpeta = null;
            if ($dniExtraido && isset($mapaDnis[$dniExtraido])) {
                $nombreCarpeta = $dniExtraido;
                unset($mapaDnis[$dniExtraido]);
            } else {
                $dnisNoEncontrados[] = $dniExtraido ?: 'SIN_DNI_PAGINA_' . ($i + 1);
                $nombreCarpeta = $dniExtraido ?: 'nomina_' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
            }

            $carpetaNominaRelativa = $carpetaBaseRelativa . '/' . $nombreCarpeta;
            if (!Storage::exists($carpetaNominaRelativa)) {
                Storage::makeDirectory($carpetaNominaRelativa);
            }
            $carpetaNominaAbsoluta = $carpetaBaseAbsoluta . DIRECTORY_SEPARATOR . $nombreCarpeta;

            // extraer PDF individual
            $pdfIndividual = new Fpdi();
            $pdfIndividual->setSourceFile($this->rutaAbsoluta);
            $pdfIndividual->AddPage();
            $tpl = $pdfIndividual->importPage($i + 1);
            $pdfIndividual->useTemplate($tpl);

            $rutaSalida = $carpetaNominaAbsoluta . DIRECTORY_SEPARATOR . $nombreCarpeta . '.pdf';
            $pdfIndividual->Output($rutaSalida, 'F');
        }

        // Construimos el mensaje de alerta
            if (!empty($dnisNoEncontrados)) {
                $mensajeAlerta = 'Nóminas importadas. DNIs no encontrados: ' . implode(', ', $dnisNoEncontrados);

try {
    $alerta = Alerta::create([
        'user_id_1'       => $this->userId,
        'user_id_2'       => null,
        'destino'         => null,
        'destinatario'    => null,
        'destinatario_id' => $this->userId,
        'mensaje'         => $mensajeAlerta,
        'tipo'            => 'warning',
    ]);

    AlertaLeida::create([
        'alerta_id' => $alerta->id,
        'user_id'   => $this->userId,
        'leida_en'  => null,
    ]);

   
} catch (\Exception $e) {
    \Log::error('❌ Error creando alerta o alertaLeida: ' . $e->getMessage());
}

            }
    }
}
