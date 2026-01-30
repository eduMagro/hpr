<?php

namespace App\Http\Controllers;

use App\Models\SolicitudCompra;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SolicitudCompraController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $user = auth()->user();

        // Mis solicitudes
        $misSolicitudes = SolicitudCompra::with(['creador', 'encargado'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Solicitudes por aprobar (Visible para administradores o encargados)
        // Por ahora, asumimos que 'admin' o rol 'oficina' pueden aprobar.
        // O si hay un rol específico 'encargado'.
        // Ajustar lógica de permisos según necesidades reales.
        $pendientesAprobar = collect();
        if ($user->esOficina() || $user->rol === 'admin' || $user->esAdminDepartamento()) {
            $pendientesAprobar = SolicitudCompra::with(['creador'])
                ->where('estado', 'pendiente')
                ->orderBy('created_at', 'asc')
                ->get();
        }

        // Obtener usuarios encargados para el select (si fuera necesario, aunque el plan dice enviar a "Jose Manuel")
        // En este caso, simplemente se crea y queda pendiente en el pool.

        return view('solicitud_compra.index', compact('misSolicitudes', 'pendientesAprobar'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|string',
        ]);

        SolicitudCompra::create([
            'user_id' => auth()->id(),
            'descripcion' => $request->descripcion,
            'estado' => 'pendiente',
        ]);

        return redirect()->route('solicitudes-compra.index')->with('success', 'Solicitud creada correctamente.');
    }

    public function aprobar($id)
    {
        // Validar permisos de aprobación aquí
        $user = auth()->user();
        if (!($user->esOficina() || $user->rol === 'admin' || $user->esAdminDepartamento())) {
            return back()->with('error', 'No tienes permisos para aprobar.');
        }

        $solicitud = SolicitudCompra::findOrFail($id);

        if ($solicitud->estado !== 'pendiente') {
            return back()->with('error', 'La solicitud no está pendiente.');
        }

        $solicitud->update([
            'estado' => 'aprobada',
            'encargado_id' => $user->id,
            'fecha_aprobacion' => now(),
            'token_qr' => (string) Str::uuid(), // Generar UUID único
        ]);

        return back()->with('success', 'Solicitud aprobada y QR generado.');
    }

    public function rechazar($id)
    {
        // Validar permisos
        $user = auth()->user();
        if (!($user->esOficina() || $user->rol === 'admin' || $user->esAdminDepartamento())) {
            return back()->with('error', 'No tienes permisos para rechazar.');
        }

        $solicitud = SolicitudCompra::findOrFail($id);
        $solicitud->update([
            'estado' => 'rechazada',
            'encargado_id' => $user->id, // El que rechaza
        ]);

        return back()->with('success', 'Solicitud rechazada.');
    }

    // API para Big Mat
    public function apiVerificar($token)
    {
        $solicitud = SolicitudCompra::with('creador')
            ->where('token_qr', $token)
            ->first();

        if (!$solicitud || $solicitud->estado !== 'aprobada') {
            return response()->json([
                'valido' => false,
                'mensaje' => 'Solicitud no encontrada o no válida.'
            ], 404);
        }

        return response()->json([
            'valido' => true,
            'data' => [
                'id' => $solicitud->id,
                'comprador' => $solicitud->creador ? $solicitud->creador->nombre_completo : 'Desconocido',
                'dni_comprador' => $solicitud->creador ? $solicitud->creador->dni : '',
                'descripcion' => $solicitud->descripcion,
                'fecha_aprobacion' => $solicitud->fecha_aprobacion->format('Y-m-d H:i:s'),
                'encargado' => $solicitud->encargado ? $solicitud->encargado->nombre_completo : 'Sistema',
            ]
        ]);
    }

    // Renderizar QR en modal (devuelve HTML o imagen)
    public function verQr($id)
    {
        $solicitud = SolicitudCompra::findOrFail($id);

        if (!$solicitud->token_qr) {
            return response()->json(['error' => 'No tiene QR asignado'], 400);
        }

        $url = $solicitud->url_qr;

        // Generar SVG del QR
        $qrCode = QrCode::size(300)->generate($url);

        return response()->json([
            'qr_svg' => (string) $qrCode,
            'url' => $url,
            'titulo' => 'QR de Solicitud #' . $solicitud->id
        ]);
    }
}
