<?php

namespace App\Livewire;

use App\Models\AsignacionTurno;
use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SubirJustificante extends Component
{
    use WithFileUploads;

    public $userId;
    public $archivo;
    public $fechaSeleccionada;
    public $textoExtraido = '';
    public $fechaDetectada = null;
    public $horasDetectadas = null;
    public $observaciones = '';
    public $procesando = false;
    public $mostrarResultados = false;
    public $error = '';
    public $asignacionesDisponibles = [];
    public $asignacionSeleccionada = null;
    public $ocrDisponible = false;

    protected $rules = [
        'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        'asignacionSeleccionada' => 'required',
        'horasDetectadas' => 'required|numeric|min:0|max:24',
    ];

    protected $messages = [
        'archivo.required' => 'Debes seleccionar un archivo.',
        'archivo.mimes' => 'El archivo debe ser PDF, JPG, JPEG o PNG.',
        'archivo.max' => 'El archivo no puede superar los 10MB.',
        'asignacionSeleccionada.required' => 'Debes seleccionar una asignación de turno.',
        'horasDetectadas.required' => 'Debes indicar las horas justificadas.',
        'horasDetectadas.numeric' => 'Las horas deben ser un número.',
        'horasDetectadas.min' => 'Las horas no pueden ser negativas.',
        'horasDetectadas.max' => 'Las horas no pueden superar 24.',
    ];

    public function mount($userId)
    {
        $this->userId = $userId;
        $this->cargarAsignacionesDisponibles();
        $this->ocrDisponible = $this->verificarOCRDisponible();
    }

    public function cargarAsignacionesDisponibles()
    {
        // Cargar asignaciones de los últimos 60 días sin justificante
        $this->asignacionesDisponibles = AsignacionTurno::where('user_id', $this->userId)
            ->whereNull('justificante_ruta')
            ->where('fecha', '>=', now()->subDays(60))
            ->orderBy('fecha', 'desc')
            ->with(['turno', 'obra'])
            ->get()
            ->map(function ($asignacion) {
                return [
                    'id' => (string) $asignacion->id,
                    'fecha' => $asignacion->fecha->format('Y-m-d'),
                    'fecha_formateada' => $asignacion->fecha->format('d/m/Y'),
                    'turno' => $asignacion->turno->nombre ?? 'Sin turno',
                    'obra' => $asignacion->obra->obra ?? 'Sin obra',
                    'estado' => $asignacion->estado,
                ];
            })
            ->toArray();
    }

    protected function verificarOCRDisponible()
    {
        // Verificar si el paquete de Tesseract está instalado
        if (!class_exists('thiagoalessio\TesseractOCR\TesseractOCR')) {
            return false;
        }

        // Verificar si Tesseract está instalado en el sistema
        return $this->detectarTesseract() !== null;
    }

    public function updatedArchivo()
    {
        $this->validate(['archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240']);
        $this->procesarArchivo();
    }

    public function procesarArchivo()
    {
        $this->procesando = true;
        $this->error = '';
        $this->textoExtraido = '';
        $this->fechaDetectada = null;
        $this->horasDetectadas = 8; // Valor por defecto

        try {
            // Si OCR está disponible, intentar extraer texto
            if ($this->ocrDisponible) {
                $extension = $this->archivo->getClientOriginalExtension();
                $tempPath = $this->archivo->getRealPath();

                // Si es PDF, convertir primera página a imagen
                if (strtolower($extension) === 'pdf') {
                    $tempPath = $this->convertirPdfAImagen($tempPath);
                }

                // Ejecutar OCR con Tesseract
                $this->textoExtraido = $this->ejecutarOCR($tempPath);

                // Analizar el texto para extraer fecha y horas
                $this->analizarTexto($this->textoExtraido);
            }

            $this->mostrarResultados = true;

        } catch (\Exception $e) {
            // Si falla el OCR, permitir continuar en modo manual
            $this->mostrarResultados = true;
            if ($this->ocrDisponible) {
                $this->error = 'No se pudo procesar el OCR: ' . $e->getMessage() . '. Puedes continuar introduciendo los datos manualmente.';
            }
        }

        $this->procesando = false;
    }

    protected function convertirPdfAImagen($pdfPath)
    {
        // Usar Imagick para convertir PDF a imagen
        if (!extension_loaded('imagick')) {
            throw new \Exception('La extensión Imagick no está instalada. Necesaria para procesar PDFs.');
        }

        $imagick = new \Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($pdfPath . '[0]'); // Primera página
        $imagick->setImageFormat('png');

        $tempImagePath = sys_get_temp_dir() . '/justificante_' . uniqid() . '.png';
        $imagick->writeImage($tempImagePath);
        $imagick->clear();
        $imagick->destroy();

        return $tempImagePath;
    }

    protected function ejecutarOCR($imagePath)
    {
        // Verificar si Tesseract está disponible
        $tesseractPath = $this->detectarTesseract();

        if (!$tesseractPath) {
            throw new \Exception('Tesseract OCR no está instalado o no se encuentra en el PATH del sistema.');
        }

        $ocr = new \thiagoalessio\TesseractOCR\TesseractOCR($imagePath);
        $ocr->executable($tesseractPath);

        // Intentar con español, si no está disponible usar inglés
        $tessdataPath = 'C:\Program Files\Tesseract-OCR\tessdata';
        if (file_exists($tessdataPath . '\spa.traineddata')) {
            $ocr->lang('spa');
        } else {
            $ocr->lang('eng');
        }

        return $ocr->run();
    }

    protected function detectarTesseract()
    {
        // Rutas comunes de Tesseract en Windows
        $posiblesRutas = [
            'C:\Program Files\Tesseract-OCR\tesseract.exe',
            'C:\Program Files (x86)\Tesseract-OCR\tesseract.exe',
            'C:\Tesseract-OCR\tesseract.exe',
            'tesseract', // Si está en el PATH
        ];

        foreach ($posiblesRutas as $ruta) {
            if (file_exists($ruta) || $this->comandoExiste($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    protected function comandoExiste($comando)
    {
        $return = shell_exec(sprintf("where %s 2>nul", escapeshellarg($comando)));
        return !empty($return);
    }

    /**
     * Detectar horas desde diferentes formatos de justificante
     */
    protected function detectarHoras($texto)
    {
        // 1. Buscar rango de horas (ej: "de 09:00 a 11:30", "09:00 - 11:30", "desde las 9 hasta las 12")
        $patronesRango = [
            '/de\s*(\d{1,2})[:\.](\d{2})\s*a\s*(\d{1,2})[:\.](\d{2})/i',
            '/desde\s*(?:las\s*)?(\d{1,2})[:\.](\d{2})\s*hasta\s*(?:las\s*)?(\d{1,2})[:\.](\d{2})/i',
            '/(\d{1,2})[:\.](\d{2})\s*[-–]\s*(\d{1,2})[:\.](\d{2})/',
            '/entre\s*(?:las\s*)?(\d{1,2})[:\.](\d{2})\s*y\s*(?:las\s*)?(\d{1,2})[:\.](\d{2})/i',
        ];

        foreach ($patronesRango as $patron) {
            if (preg_match($patron, $texto, $matches)) {
                $horaInicio = (int)$matches[1] + (int)$matches[2] / 60;
                $horaFin = (int)$matches[3] + (int)$matches[4] / 60;
                if ($horaFin > $horaInicio) {
                    return round($horaFin - $horaInicio, 1);
                }
            }
        }

        // 2. Buscar hora entrada/citación y hora salida/alta (formato médico)
        $horaEntrada = null;
        $horaSalida = null;

        $patronesEntrada = [
            '/cita(?:ci[oó]n)?[:\s]*(\d{1,2})[:\.](\d{2})/i',
            '/entrada[:\s]*(\d{1,2})[:\.](\d{2})/i',
            '/inicio[:\s]*(\d{1,2})[:\.](\d{2})/i',
            '/llegada[:\s]*(\d{1,2})[:\.](\d{2})/i',
            '/hora[:\s]*(\d{1,2})[:\.](\d{2})/i',
        ];

        $patronesSalida = [
            '/alta[:\s]*(\d{1,2})[:\.](\d{2})/i',
            '/salida[:\s]*(\d{1,2})[:\.](\d{2})/i',
            '/fin(?:al)?[:\s]*(\d{1,2})[:\.](\d{2})/i',
            '/t[eé]rmino[:\s]*(\d{1,2})[:\.](\d{2})/i',
        ];

        foreach ($patronesEntrada as $patron) {
            if (preg_match($patron, $texto, $matches)) {
                $horaEntrada = (int)$matches[1] + (int)$matches[2] / 60;
                break;
            }
        }

        foreach ($patronesSalida as $patron) {
            if (preg_match($patron, $texto, $matches)) {
                $horaSalida = (int)$matches[1] + (int)$matches[2] / 60;
                break;
            }
        }

        if ($horaEntrada !== null && $horaSalida !== null && $horaSalida > $horaEntrada) {
            return round($horaSalida - $horaEntrada, 1);
        }

        // 3. Buscar duración directa (ej: "2 horas", "3h", "duración: 4 horas")
        $patronesDuracion = [
            '/duraci[oó]n[:\s]*(\d+(?:[.,]\d+)?)\s*h(?:oras?)?/i',
            '/(\d+(?:[.,]\d+)?)\s*horas?\s*(?:de\s*)?(?:duraci[oó]n|asistencia|consulta|cita)/i',
            '/(?:tiempo|permanencia)[:\s]*(\d+(?:[.,]\d+)?)\s*h(?:oras?)?/i',
            '/(\d+(?:[.,]\d+)?)\s*h(?:oras?)?\b/i',
        ];

        foreach ($patronesDuracion as $patron) {
            if (preg_match($patron, $texto, $matches)) {
                $horas = (float) str_replace(',', '.', $matches[1]);
                if ($horas > 0 && $horas <= 24) {
                    return $horas;
                }
            }
        }

        // 4. Detectar jornada completa
        if (preg_match('/jornada\s*completa|todo\s*el\s*d[ií]a|d[ií]a\s*completo/i', $texto)) {
            return 8;
        }

        // 5. Detectar media jornada
        if (preg_match('/media\s*jornada|medio\s*d[ií]a/i', $texto)) {
            return 4;
        }

        // Por defecto, jornada completa
        return 8;
    }

    protected function analizarTexto($texto)
    {
        // Limpiar el texto de espacios extra para mejor detección
        $textoLimpio = preg_replace('/\s+/', ' ', $texto);

        // Buscar fechas en formato común español (dd/mm/yyyy, dd-mm-yyyy, etc.)
        // Permitir espacios opcionales entre los componentes
        $patronesFecha = [
            '/(\d{1,2})\s*[\/\-]\s*(\d{1,2})\s*[\/\-]\s*(\d{4})/',  // dd/mm/yyyy o dd-mm-yyyy con espacios
            '/(\d{1,2})\s+de\s+(\w+)\s+de\s+(\d{4})/i', // dd de mes de yyyy
            '/d[ií]a\s*:?\s*(\d{1,2})\s*[\/\-]\s*(\d{1,2})\s*[\/\-]\s*(\d{4})/i', // día: dd/mm/yyyy
        ];

        foreach ($patronesFecha as $patron) {
            if (preg_match($patron, $textoLimpio, $matches) || preg_match($patron, $texto, $matches)) {
                try {
                    if (count($matches) >= 4 && is_numeric($matches[2])) {
                        // Formato dd/mm/yyyy
                        $dia = (int) $matches[1];
                        $mes = (int) $matches[2];
                        $anio = (int) $matches[3];

                        // Validar que sea una fecha válida
                        if ($dia >= 1 && $dia <= 31 && $mes >= 1 && $mes <= 12 && $anio >= 2020 && $anio <= 2030) {
                            $this->fechaDetectada = Carbon::createFromDate($anio, $mes, $dia)->format('Y-m-d');
                            break;
                        }
                    } else if (count($matches) >= 4) {
                        // Formato con nombre de mes
                        $meses = [
                            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
                            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
                            'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
                        ];
                        $mesTexto = strtolower($matches[2]);
                        if (isset($meses[$mesTexto])) {
                            $this->fechaDetectada = Carbon::createFromDate($matches[3], $meses[$mesTexto], $matches[1])
                                ->format('Y-m-d');
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Buscar horas en el texto
        $this->horasDetectadas = $this->detectarHoras($texto);

        // Si se detectó fecha, intentar seleccionar automáticamente la asignación correspondiente
        if ($this->fechaDetectada) {
            $encontrada = false;
            foreach ($this->asignacionesDisponibles as $key => $asignacion) {
                if ($asignacion['fecha'] === $this->fechaDetectada) {
                    $this->asignacionSeleccionada = $asignacion['id'];
                    $encontrada = true;
                    break;
                }
            }

            // Si no existe asignación para esa fecha, crearla
            if (!$encontrada) {
                try {
                    $nuevaAsignacion = AsignacionTurno::create([
                        'user_id' => $this->userId,
                        'fecha' => Carbon::parse($this->fechaDetectada),
                        'estado' => 'justificado',
                    ]);

                    $nuevaAsignacionArray = [
                        'id' => (string) $nuevaAsignacion->id,
                        'fecha' => $this->fechaDetectada,
                        'fecha_formateada' => Carbon::parse($this->fechaDetectada)->format('d/m/Y'),
                        'turno' => 'Sin turno',
                        'obra' => 'Sin obra',
                        'estado' => 'justificado',
                    ];

                    // Añadir al inicio del array para que aparezca primero
                    array_unshift($this->asignacionesDisponibles, $nuevaAsignacionArray);

                    $this->asignacionSeleccionada = (string) $nuevaAsignacion->id;
                } catch (\Exception $e) {
                    $this->error = 'Error al crear asignación: ' . $e->getMessage();
                }
            }
        }

        // Valor por defecto de horas si no se detectaron
        if (!$this->horasDetectadas) {
            $this->horasDetectadas = 8; // Jornada completa por defecto
        }
    }

    public function guardarJustificante()
    {
        $this->validate();

        try {
            $user = User::find($this->userId);
            $asignacion = AsignacionTurno::find($this->asignacionSeleccionada);

            if (!$asignacion) {
                $this->error = 'No se encontró la asignación seleccionada.';
                return;
            }

            // Crear directorio si no existe
            $nombreCarpeta = str_replace(' ', '_', $user->name) . '_' . $user->id;
            $rutaBase = "documentos/{$nombreCarpeta}/justificantes_asistencia";

            // Guardar archivo
            $nombreArchivo = $asignacion->fecha->format('Y-m-d') . '_' . time() . '.' . $this->archivo->getClientOriginalExtension();
            $rutaArchivo = $this->archivo->storeAs($rutaBase, $nombreArchivo, 'public');

            // Actualizar asignación
            $asignacion->update([
                'justificante_ruta' => $rutaArchivo,
                'horas_justificadas' => $this->horasDetectadas,
                'justificante_observaciones' => $this->observaciones ?: $this->textoExtraido,
                'justificante_subido_at' => now(),
            ]);

            // Resetear formulario
            $this->reset(['archivo', 'textoExtraido', 'fechaDetectada', 'horasDetectadas', 'observaciones', 'mostrarResultados', 'asignacionSeleccionada']);
            $this->cargarAsignacionesDisponibles();

            session()->flash('justificante_success', 'Justificante guardado correctamente.');
            $this->dispatch('justificante-guardado');

        } catch (\Exception $e) {
            $this->error = 'Error al guardar el justificante: ' . $e->getMessage();
        }
    }

    public function cancelar()
    {
        $this->reset(['archivo', 'textoExtraido', 'fechaDetectada', 'horasDetectadas', 'observaciones', 'mostrarResultados', 'asignacionSeleccionada', 'error']);
    }

    public function render()
    {
        return view('livewire.subir-justificante');
    }
}
