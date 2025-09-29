<?php

namespace App\Http\Controllers;

use App\Models\Distribuidor;
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
        if ($request->filled('distribuidor')) {
            $query->whereHas('distribuidor', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->distribuidor . '%');
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
        if ($request->filled('distribuidor')) {
            $filtros[] = 'Distribuidor: <strong>' . $request->distribuidor . '</strong>';
        }

        if ($request->filled('estado')) {
            $filtros[] = 'Estado: <strong>' . ucfirst($request->estado) . '</strong>';
        }

        if ($request->filled('sort')) {
            $sorts = [
                'codigo' => 'Código',
                'fabricante' => 'Fabricante',
                'distribuidor' => 'Distribuidor',
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
        $codigosPedidosMaquila = ['PCG25/0005'];

        // Tabla principal (excluye maquila)
        $query = PedidoGlobal::with(['fabricante', 'distribuidor', 'pedidos'])
            ->whereNotIn('codigo', $codigosPedidosMaquila);

        $this->aplicarFiltrosPedidosGlobales($query, $request);

        $queryFiltrado = (clone $query);

        $perPage = $request->input('per_page', 10);
        $pedidosGlobales = $query->paginate($perPage)->appends($request->all());

        // Tabla maquila (incluye SOLO esos códigos). La paginamos también.
        $queryMaquila = PedidoGlobal::with(['fabricante', 'distribuidor', 'pedidos'])
            ->whereIn('codigo', $codigosPedidosMaquila);

        // OJO: usa un nombre distinto para el select de “por página” de esta tabla
        $perPageMaquila = $request->input('per_page_maquila', 50);
        $pedidosMaquila = $queryMaquila->paginate($perPageMaquila)->appends($request->all());

        // Filtros activos / ordenables (principal)
        $filtrosActivos = $this->filtrosActivosPedidosGlobales($request);
        $ordenables = [
            'codigo'            => $this->getOrdenamientoPedidosGlobales('codigo', 'Código'),
            'fabricante'        => $this->getOrdenamientoPedidosGlobales('fabricante', 'Fabricante'),
            'distribuidor'      => $this->getOrdenamientoPedidosGlobales('distribuidor', 'Distribuidor'),
            'precio_referencia' => $this->getOrdenamientoPedidosGlobales('precio_referencia', 'Precio Ref.'),
            'cantidad_total'    => $this->getOrdenamientoPedidosGlobales('cantidad_total', 'Cantidad total'),
            'estado'            => $this->getOrdenamientoPedidosGlobales('estado', 'Estado'),
        ];

        $fabricantes = Fabricante::select('id', 'nombre')->get();
        $distribuidores = Distribuidor::select('id', 'nombre')->get();

        // Totales filtrados (principal)
        $totalFiltradoCantidadTotal = (clone $queryFiltrado)->sum('cantidad_total');

        $totalFiltradoCantidadRestante = 0.0;
        (clone $queryFiltrado)
            ->select('pedidos_globales.*')
            ->orderBy('id')
            ->lazyById(1000, 'id')
            ->each(function ($p) use (&$totalFiltradoCantidadRestante) {
                $totalFiltradoCantidadRestante += (float) ($p->cantidad_restante ?? 0);
            });

        $totalesFiltrados = [
            'cantidad_total'    => $totalFiltradoCantidadTotal,
            'cantidad_restante' => $totalFiltradoCantidadRestante,
        ];

        // Totales maquila (del conjunto completo, NO solo de la página)
        $totalesMaquila = [
            'cantidad_total'    => (clone $queryMaquila)->sum('cantidad_total'),
            'cantidad_restante' => (clone $queryMaquila)->get()->sum(function ($p) {
                return (float) ($p->cantidad_restante ?? 0);
            }),
        ];

        return view('pedidos_globales.index', compact(
            'pedidosGlobales',
            'pedidosMaquila',
            'totalesMaquila',
            'filtrosActivos',
            'ordenables',
            'fabricantes',
            'distribuidores',
            'totalesFiltrados'
        ));
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
                'distribuidor_id' => 'nullable|exists:distribuidores,id',
            ]);

            // Crear nuevo pedido global
            $pedidoGlobal = new PedidoGlobal();
            $pedidoGlobal->codigo = PedidoGlobal::generarCodigo();
            $pedidoGlobal->cantidad_total = $validated['cantidad_total'];
            $pedidoGlobal->fabricante_id = $validated['fabricante_id'];
            $pedidoGlobal->distribuidor_id = $validated['distribuidor_id'] ?? null; // Asignar distribuidor si existe
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
                'precio_referencia' => 'nullable|numeric|min:0|max:9999999999.9999',
            ], [
                'cantidad_total.required' => 'La cantidad total es obligatoria.',
                'cantidad_total.numeric' => 'La cantidad debe ser un número.',
                'cantidad_total.min' => 'La cantidad no puede ser negativa.',

                'fabricante_id.exists' => 'El fabricante seleccionado no existe.',

                'estado.required' => 'El estado es obligatorio.',
                'estado.string' => 'El estado debe ser una cadena de texto.',
                'estado.in' => 'El estado debe ser: pendiente, en curso, completado o cancelado.',

                'precio_referencia.numeric' => 'El precio debe ser un número válido.',
                'precio_referencia.min' => 'El precio no puede ser negativo.',
                'precio_referencia.max' => 'El precio es demasiado alto.',
            ]);

            // Buscar el pedido global
            $pedido = PedidoGlobal::find($id);
            if (!$pedido) {
                return response()->json(['error' => 'Pedido global no encontrado.'], 404);
            }

            // Actualizar campos
            $resultado = $pedido->update([
                'cantidad_total'      => $request->cantidad_total,
                'fabricante_id'       => $request->fabricante_id,
                'estado'              => $request->estado,
                'precio_referencia'   => $request->precio_referencia,
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
        \Log::info('Borrando pedido global ' . ($pedidoGlobal->codigo ?? ('ID ' . $pedidoGlobal->id)) . ' por el usuario ' . (auth()->user()->nombre_completo ?? 'desconocido'));
        $pedidoGlobal->delete();

        return redirect()->route('pedidos_globales.index')
            ->with('success', 'Pedido global eliminado correctamente.');
    }
}
