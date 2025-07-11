<?php

namespace App\Http\Controllers;

use App\Models\PedidoGlobal;
use App\Models\Fabricante;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;

class PedidoGlobalController extends Controller
{

    private function aplicarFiltrosPedidosGlobales($query, Request $request)
    {
        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%' . $request->codigo . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fabricante')) {
            $query->whereHas('fabricante', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->fabricante . '%');
            });
        }

        $sortBy = $request->input('sort', 'codigo');
        $order = $request->input('order', 'desc');
        $query->orderByRaw("CAST({$sortBy} AS CHAR) {$order}");

        return $query;
    }
    private function filtrosActivosPedidosGlobales(Request $request): array
    {
        $filtros = [];

        if ($request->filled('codigo')) {
            $filtros[] = 'Código global: <strong>' . $request->codigo . '</strong>';
        }

        if ($request->filled('fabricante')) {
            $filtros[] = 'Fabricante: <strong>' . $request->fabricante . '</strong>';
        }

        if ($request->filled('estado')) {
            $filtros[] = 'Estado: <strong>' . ucfirst($request->estado) . '</strong>';
        }

        if ($request->filled('sort')) {
            $sorts = [
                'codigo' => 'Código',
                'fabricante' => 'Fabricante',
                'cantidad_total' => 'Cantidad total',
                'estado' => 'Estado',
            ];
            $orden = $request->order == 'desc' ? 'descendente' : 'ascendente';
            $filtros[] = 'Ordenado por <strong>' . ($sorts[$request->sort] ?? $request->sort) . "</strong> en orden <strong>$orden</strong>";
        }

        if ($request->filled('per_page')) {
            $filtros[] = 'Mostrando <strong>' . $request->per_page . '</strong> registros por página';
        }

        return $filtros;
    }
    private function getOrdenamientoPedidosGlobales(string $columna, string $titulo): string
    {
        $currentSort = request('sort');
        $currentOrder = request('order');
        $isSorted = $currentSort === $columna;
        $nextOrder = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = $isSorted
            ? ($currentOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down')
            : 'fas fa-sort';

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . $url . '" class="text-white text-decoration-none">' .
            $titulo . ' <i class="' . $icon . '"></i></a>';
    }

    public function index(Request $request)
    {
        $query = PedidoGlobal::with(['fabricante', 'pedidos']);

        // Aplicar filtros
        $this->aplicarFiltrosPedidosGlobales($query, $request);

        // Paginación configurable
        $perPage = $request->input('per_page', 10);
        $pedidosGlobales = $query->paginate($perPage)->appends($request->all());

        $filtrosActivos = $this->filtrosActivosPedidosGlobales($request);
        $ordenables = [
            'codigo' => $this->getOrdenamientoPedidosGlobales('codigo', 'Código'),
            'fabricante' => $this->getOrdenamientoPedidosGlobales('fabricante', 'Fabricante'),
            'cantidad_total' => $this->getOrdenamientoPedidosGlobales('cantidad_total', 'Cantidad total'),
            'estado' => $this->getOrdenamientoPedidosGlobales('estado', 'Estado'),
        ];

        $fabricantes = Fabricante::select('id', 'nombre')->get();

        return view('pedidos_globales.index', compact('pedidosGlobales', 'filtrosActivos', 'ordenables', 'fabricantes'));
    }

    // Mostrar formulario de creación
    public function create()
    {
        $fabricantes = Fabricante::orderBy('nombre')->get();
        return view('pedidos_globales.create', compact('fabricantes'));
    }

    public function store(Request $request)
    {
        try {
            // Validación de datos
            $validated = $request->validate([
                'cantidad_total' => 'required|numeric|min:0',
                'fabricante_id' => 'nullable|exists:fabricantes,id',
            ]);

            // Crear nuevo pedido global
            $pedidoGlobal = new PedidoGlobal();
            $pedidoGlobal->codigo = PedidoGlobal::generarCodigo();
            $pedidoGlobal->cantidad_total = $validated['cantidad_total'];
            $pedidoGlobal->fabricante_id = $validated['fabricante_id'];
            $pedidoGlobal->estado = 'pendiente';
            $pedidoGlobal->save();

            // Si es una petición AJAX (JavaScript)
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'id' => $pedidoGlobal->id,
                    'codigo' => $pedidoGlobal->codigo,
                ]);
            }

            // Petición normal (formulario HTML tradicional)
            return redirect()->route('pedidos_globales.index')
                ->with('success', 'Pedido global creado correctamente.');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $e->errors(),
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            Log::error('Error al crear pedido global: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Ocurrió un error inesperado.')->withInput();
        }
    }


    // Mostrar formulario de edición
    public function edit(PedidoGlobal $pedidoGlobal)
    {
        //
    }

    // Actualizar un pedido global
    public function update(Request $request, $id)
    {
        try {
            // Validar los datos
            $request->validate([
                'cantidad_total' => 'required|numeric|min:0',
                'fabricante_id' => 'nullable|exists:fabricantes,id',
                'estado' => 'required|string|in:pendiente,en curso,completado,cancelado',
            ], [
                'cantidad_total.required' => 'La cantidad total es obligatoria.',
                'cantidad_total.numeric' => 'La cantidad debe ser un número.',
                'cantidad_total.min' => 'La cantidad no puede ser negativa.',

                'fabricante_id.exists' => 'El fabricante seleccionado no existe.',

                'estado.required' => 'El estado es obligatorio.',
                'estado.string' => 'El estado debe ser una cadena de texto.',
                'estado.in' => 'El estado debe ser: pendiente, en curso, completado o cancelado.',
            ]);

            // Buscar el pedido global
            $pedido = PedidoGlobal::find($id);
            if (!$pedido) {
                return response()->json(['error' => 'Pedido global no encontrado.'], 404);
            }

            // Actualizar campos
            $resultado = $pedido->update([
                'cantidad_total' => $request->cantidad_total,
                'fabricante_id' => $request->fabricante_id,
                'estado' => $request->estado,
            ]);

            if (!$resultado) {
                return response()->json(['error' => 'No se pudo actualizar el pedido global.'], 500);
            }

            return response()->json(['success' => 'Pedido global actualizado correctamente.']);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error inesperado: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $pedidoGlobal = PedidoGlobal::findOrFail($id);
        $pedidoGlobal->delete();

        return redirect()->route('pedidos_globales.index')
            ->with('success', 'Pedido global eliminado correctamente.');
    }
}
