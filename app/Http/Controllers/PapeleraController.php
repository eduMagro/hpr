<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paquete;
use App\Models\Camion;
use App\Models\EmpresaTransporte;
use App\Models\Producto;
use App\Models\Planilla;
use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\Pedido;
use App\Models\PedidoGlobal;
use App\Models\Movimiento;
use App\Models\Entrada;
use App\Models\Salida;
use App\Models\AsignacionTurno;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Obra;
use App\Models\Maquina;
use App\Models\ProductoBase;
use App\Models\Ubicacion;
use App\Models\Alerta;
use App\Models\Departamento;
use App\Models\Seccion;
use App\Models\Turno;
use App\Models\Distribuidor;
use App\Models\Fabricante;
use App\Models\Localizacion;

class PapeleraController extends Controller
{
    public function index()
    {
        // Verificar que el usuario pertenece al departamento "Programador"
        $user = auth()->user();

        $esProgramador = $user->departamentos()
            ->where('nombre', 'Programador')
            ->exists();

        if (!$esProgramador) {
            return back()->with('error', 'No tienes permisos para acceder a la papelera.');
        }

        // La lógica de paginación se maneja en los componentes Livewire
        return view('papelera.index');
    }

    public function restore($model, $id)
    {
        // Verificar que el usuario pertenece al departamento "Programador"
        $user = auth()->user();

        $esProgramador = $user->departamentos()
            ->where('nombre', 'Programador')
            ->exists();

        if (!$esProgramador) {
            return back()->with('error', 'No tienes permisos para restaurar registros.');
        }

        $models = [
            'productos' => \App\Models\Producto::class,
            'planillas' => \App\Models\Planilla::class,
            'etiquetas' => \App\Models\Etiqueta::class,
            'paquetes' => \App\Models\Paquete::class,
            'elementos' => \App\Models\Elemento::class,
            'pedidos' => \App\Models\Pedido::class,
            'pedidos_globales' => \App\Models\PedidoGlobal::class,
            'movimientos' => \App\Models\Movimiento::class,
            'entradas' => \App\Models\Entrada::class,
            'salidas' => \App\Models\Salida::class,
            'asignaciones_turnos' => \App\Models\AsignacionTurno::class,
            'users' => \App\Models\User::class,
            'clientes' => \App\Models\Cliente::class,
            'obras' => \App\Models\Obra::class,
            'maquinas' => \App\Models\Maquina::class,
            'productos_base' => \App\Models\ProductoBase::class,
            'ubicaciones' => \App\Models\Ubicacion::class,
            'alertas' => \App\Models\Alerta::class,
            'departamentos' => \App\Models\Departamento::class,
            'secciones' => \App\Models\Seccion::class,
            'turnos' => \App\Models\Turno::class,
            'camiones' => \App\Models\Camion::class,
            'empresas_transporte' => \App\Models\EmpresaTransporte::class,
            'distribuidores' => \App\Models\Distribuidor::class,
            'fabricantes' => \App\Models\Fabricante::class,
            'localizaciones' => \App\Models\Localizacion::class,
        ];

        if (!array_key_exists($model, $models)) {
            return redirect()->route('papelera.index')->with('error', 'Modelo no encontrado.');
        }

        $record = $models[$model]::onlyTrashed()->findOrFail($id);
        $record->restore();

        return redirect()->route('papelera.index')->with('success', ucfirst(str_replace('_', ' ', $model)) . ' restaurado correctamente.');
    }
}
