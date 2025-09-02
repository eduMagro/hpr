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

class DividirNominasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rutaAbsoluta;
    protected $mesAnio;
    protected $userId;

    public function __construct($rutaAbsoluta, $mesAnio, $userId)
    {
        $this->rutaAbsoluta = $rutaAbsoluta;
        $this->mesAnio      = $mesAnio;
        $this->userId       = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1) Mes y año
        $fecha        = Carbon::createFromFormat('Y-m', $this->mesAnio);
        $mesEnEspañol = ucfirst($fecha->locale('es')->translatedFormat('F'));
        $anio         = $fecha->format('Y');

        // 2) Carpeta base del lote
        $carpetaBaseRelativa = 'private/nominas/nominas_' . $anio . '/nomina_' . $mesEnEspañol . '_' . $anio;
        if (!Storage::exists($carpetaBaseRelativa)) {
            Storage::makeDirectory($carpetaBaseRelativa);
        }

        // 3) Mapa de DNIs -> Empresa normalizada
        //    Estructura: [ 'DNI' => 'EMPRESA_NORMALIZADA' ]
        $usuarios = User::with('empresa')->get();
        $mapaDnis = [];
        foreach ($usuarios as $u) {
            if (!$u->dni) {
                continue;
            }
            $dni = strtoupper(preg_replace('/[^A-Z0-9]/', '', $u->dni));
            $empresa = $u->empresa->nombre ?? 'SIN_EMPRESA';
            $empresaNorm = preg_replace('/[^A-Z0-9_-]/', '_', strtoupper($empresa));
            $mapaDnis[$dni] = $empresaNorm;
        }

        // 4) Parsear PDF origen y agrupar páginas por DNI
        $parser = new Parser();
        $pdfParsed = $parser->parseFile($this->rutaAbsoluta);
        $pages = $pdfParsed->getPages();

        // Un FPDI por DNI (para combinar todas sus páginas en un solo PDF)
        /** @var array<string,Fpdi> $pdfPorDni */
        $pdfPorDni = [];
        /** @var array<string,string> $empresaPorDni */
        $empresaPorDni = [];
        $dnisNoEncontrados = [];

        foreach ($pages as $i => $page) {
            $textoPagina      = strtoupper($page->getText());
            $textoNormalizado = preg_replace('/\s+/', '', $textoPagina);

            // Heurística: 9 chars antes de "NºAFILIACION"
            $pos = strpos($textoNormalizado, 'NºAFILIACION');
            $dniExtraido = null;
            if ($pos !== false && $pos >= 9) {
                $dniExtraido = substr($textoNormalizado, $pos - 9, 9);
                $dniExtraido = strtoupper(preg_replace('/[^A-Z0-9]/', '', $dniExtraido));
            }

            // Clave de agrupación: DNI o fallback "nomina_XXX"
            $clave = $dniExtraido ?: ('nomina_' . str_pad($i + 1, 3, '0', STR_PAD_LEFT));

            // Empresa para ese DNI (o SIN_EMPRESA si no se reconoce)
            if ($dniExtraido && isset($mapaDnis[$dniExtraido])) {
                $empresaNorm = $mapaDnis[$dniExtraido];
            } else {
                $empresaNorm = 'SIN_EMPRESA';
                $dnisNoEncontrados[] = $dniExtraido ?: ('SIN_DNI_PAGINA_' . ($i + 1));
            }

            // Crear FPDI para ese DNI si no existe
            if (!isset($pdfPorDni[$clave])) {
                $fpdi = new Fpdi();
                // La fuente es el PDF original (mismo para todas las páginas)
                $fpdi->setSourceFile($this->rutaAbsoluta);
                $pdfPorDni[$clave] = $fpdi;
                $empresaPorDni[$clave] = $empresaNorm;
            }

            // Añadir la página i+1 al FPDI correspondiente
            $fpdi = $pdfPorDni[$clave];
            $fpdi->AddPage();
            $tpl = $fpdi->importPage($i + 1);
            $fpdi->useTemplate($tpl);
        }

        // 5) Escribir un PDF por DNI en {EMPRESA}/{DNI}/{DNI}.pdf
        foreach ($pdfPorDni as $clave => $fpdi) {
            $empresaNorm = $empresaPorDni[$clave];

            $carpetaEmpresaRelativa = $carpetaBaseRelativa . '/' . $empresaNorm;
            if (!Storage::exists($carpetaEmpresaRelativa)) {
                Storage::makeDirectory($carpetaEmpresaRelativa);
            }

            $carpetaDniRelativa = $carpetaEmpresaRelativa . '/' . $clave;
            if (!Storage::exists($carpetaDniRelativa)) {
                Storage::makeDirectory($carpetaDniRelativa);
            }

            $rutaSalida = storage_path('app/' . $carpetaDniRelativa . '/' . $clave . '.pdf');
            // Guardar el PDF combinado de ese DNI
            $fpdi->Output($rutaSalida, 'F');
        }

        // 6) Alerta con DNIs no encontrados
        if (!empty($dnisNoEncontrados)) {
            $mensajeAlerta = 'Nóminas importadas (' . $mesEnEspañol . ' - ' . $anio . '). DNIs no encontrados: ' . implode(', ', $dnisNoEncontrados);

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
