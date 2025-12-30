<?php

namespace App\Http\Controllers;

use App\Models\InventarioBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventarioBackupController extends Controller
{
    /**
     * Guarda un nuevo snapshot del inventario.
     */
    public function store(Request $request)
    {
        $request->validate([
            'almacen_id' => 'required',
            'data' => 'required|array'
        ]);

        $backup = InventarioBackup::create([
            'user_id' => Auth::id(),
            'almacen_id' => $request->almacen_id,
            'data' => $request->data,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Backup guardado correctamente.',
            'backup' => $backup->load('user')
        ]);
    }

    /**
     * Lista los últimos 3 backups para un almacén específico.
     */
    public function index(Request $request)
    {
        $almacenId = $request->query('almacen_id');

        $backups = InventarioBackup::with('user')
            ->where('almacen_id', $almacenId)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        return response()->json([
            'ok' => true,
            'backups' => $backups
        ]);
    }
}
