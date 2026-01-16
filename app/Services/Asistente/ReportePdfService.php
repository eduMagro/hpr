<?php

namespace App\Services\Asistente;

use App\Models\AsistenteInforme;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportePdfService
{
    /**
     * Genera un PDF a partir de un informe
     */
    public function generarPdf(AsistenteInforme $informe): string
    {
        $plantilla = $this->obtenerPlantilla($informe->tipo);

        $pdf = Pdf::loadView($plantilla, [
            'informe' => $informe,
            'datos' => $informe->datos,
            'resumen' => $informe->resumen,
            'titulo' => $informe->titulo,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'usuario' => $informe->user->name ?? 'Sistema',
        ]);

        // Configurar opciones del PDF
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        // Generar nombre único para el archivo
        $nombreArchivo = 'asistente/informes/' . $informe->tipo . '_' . $informe->id . '_' . Str::random(8) . '.pdf';

        // Guardar el PDF
        Storage::put($nombreArchivo, $pdf->output());

        // Actualizar el informe con la ruta del PDF
        $informe->update(['archivo_pdf' => $nombreArchivo]);

        return $nombreArchivo;
    }

    /**
     * Obtiene la plantilla correspondiente al tipo de informe
     */
    protected function obtenerPlantilla(string $tipo): string
    {
        $plantillas = [
            'stock_general' => 'pdfs.asistente.informe-stock',
            'stock_critico' => 'pdfs.asistente.informe-stock',
            'produccion_diaria' => 'pdfs.asistente.informe-produccion',
            'produccion_semanal' => 'pdfs.asistente.informe-produccion',
            'consumo_maquinas' => 'pdfs.asistente.informe-produccion',
            'peso_obra' => 'pdfs.asistente.informe-generico',
            'planilleros' => 'pdfs.asistente.informe-generico',
            'planillas_pendientes' => 'pdfs.asistente.informe-generico',
        ];

        return $plantillas[$tipo] ?? 'pdfs.asistente.informe-generico';
    }

    /**
     * Descarga el PDF de un informe
     */
    public function descargarPdf(AsistenteInforme $informe)
    {
        // Si no existe el PDF, generarlo
        if (!$informe->tienePdf()) {
            $this->generarPdf($informe);
            $informe->refresh();
        }

        $rutaPdf = $informe->getRutaPdf();

        if (!$rutaPdf || !file_exists($rutaPdf)) {
            throw new \Exception('El archivo PDF no existe o no está disponible.');
        }

        $nombreDescarga = Str::slug($informe->titulo) . '_' . now()->format('Ymd') . '.pdf';

        return response()->download($rutaPdf, $nombreDescarga, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Obtiene el contenido del PDF como stream
     */
    public function obtenerPdfStream(AsistenteInforme $informe)
    {
        // Si no existe el PDF, generarlo
        if (!$informe->tienePdf()) {
            $this->generarPdf($informe);
            $informe->refresh();
        }

        return Storage::get($informe->archivo_pdf);
    }

    /**
     * Limpia los PDFs expirados
     */
    public function limpiarPdfsExpirados(): int
    {
        $informesExpirados = AsistenteInforme::where('expira_at', '<', now())
            ->whereNotNull('archivo_pdf')
            ->get();

        $eliminados = 0;

        foreach ($informesExpirados as $informe) {
            if ($informe->archivo_pdf && Storage::exists($informe->archivo_pdf)) {
                Storage::delete($informe->archivo_pdf);
                $eliminados++;
            }
            $informe->delete();
        }

        return $eliminados;
    }
}
