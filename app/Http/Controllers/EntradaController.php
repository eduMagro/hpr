<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\Ubicacion;
use App\Models\User;
use App\Models\EntradaProducto;
use App\Models\Producto;
use App\Models\PedidoProducto;
use App\Models\Pedido;
use App\Models\Elemento;
use App\Models\ProductoBase;
use App\Models\Fabricante;
use App\Models\Distribuidor;
use App\Models\Movimiento;
use App\Models\Obra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
use ZipArchive;


class EntradaController extends Controller
{
    //------------------------------------------------------------------------------------ FILTROS
    // ===================================================================

    private function aplicarFiltrosEntradas($query, Request $request)
    {
        // ===== Filtros =====

        // Línea de pedido (numérico exacto)
        if ($request->filled('pedido_producto_id')) {
            $valor = trim((string) $request->pedido_producto_id);
            $query->whereRaw('CAST(pedido_producto_id AS CHAR) LIKE ?', ['%' . $valor . '%']);
        }

        // Albarán - contains, case-insensitive
        if ($request->filled('albaran')) {
            $albaran = trim($request->albaran);
            $query->whereRaw('LOWER(albaran) LIKE ?', ['%' . mb_strtolower($albaran, 'UTF-8') . '%']);
        }

        // Código SAGE - contains, case-insensitive
        if ($request->filled('codigo_sage')) {
            $codigoSage = trim($request->codigo_sage);
            $query->whereRaw('LOWER(codigo_sage) LIKE ?', ['%' . mb_strtolower($codigoSage, 'UTF-8') . '%']);
        }

        // Nave por nombre (relación ->nave->obra) - contains, case-insensitive
        if ($request->filled('obra')) {
            $texto = trim($request->obra);
            $query->whereHas('nave', function ($q) use ($texto) {
                $q->whereRaw('LOWER(obra) LIKE ?', ['%' . mb_strtolower($texto, 'UTF-8') . '%']);
            });
        }

        // Nave por ID (si lo envías desde un select)
        if ($request->filled('nave_id') && is_numeric($request->nave_id)) {
            $query->where('nave_id', (int) $request->nave_id);
        }

        // ✅ FILTRO PRODUCTO BASE CON 3 CAMPOS SEPARADOS (tipo, diámetro, longitud)
        if ($request->filled('producto_tipo') || $request->filled('producto_diametro') || $request->filled('producto_longitud')) {
            $tipo      = $request->filled('producto_tipo')      ? mb_strtolower(trim($request->producto_tipo), 'UTF-8') : null;
            $diametro  = $request->filled('producto_diametro')  ? mb_strtolower(trim($request->producto_diametro), 'UTF-8') : null;
            $longitud  = $request->filled('producto_longitud')  ? mb_strtolower(trim($request->producto_longitud), 'UTF-8') : null;

            $query->whereHas('pedidoProducto.productoBase', function ($q) use ($tipo, $diametro, $longitud) {
                if ($tipo !== null) {
                    $q->whereRaw('LOWER(tipo) LIKE ?', ['%' . $tipo . '%']);
                }
                if ($diametro !== null) {
                    $q->whereRaw('LOWER(diametro) LIKE ?', ['%' . $diametro . '%']);
                }
                if ($longitud !== null) {
                    $q->whereRaw('LOWER(longitud) LIKE ?', ['%' . $longitud . '%']);
                }
            });
        }

        // Pedido por código (relación pedido) - contains, case-insensitive
        if ($request->filled('pedido_codigo')) {
            $codigo = trim($request->pedido_codigo);
            $query->whereHas('pedido', function ($q) use ($codigo) {
                $q->whereRaw('LOWER(codigo) LIKE ?', ['%' . mb_strtolower($codigo, 'UTF-8') . '%']);
            });
        }

        // Usuario (relación user) - contains, case-insensitive
        if ($request->filled('usuario')) {
            $usuario = trim($request->usuario);
            $query->whereHas('user', function ($q) use ($usuario) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($usuario, 'UTF-8') . '%']);
            });
        }

        // ===== Orden =====
        $sort  = $request->input('sort', 'created_at');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        // Limpia órdenes previas
        $query->reorder();

        switch ($sort) {
            case 'pedido_producto_id':
            case 'albaran':
            case 'created_at':
                $query->orderBy($sort, $order);
                break;

            case 'codigo_sage':
                $query->orderBy('codigo_sage', $order);
                break;

            case 'nave_id':
            case 'nave':
                // Ordenar por nombre de la nave (obras.obra) en vez de por id
                $query->leftJoin('obras as o', 'entradas.nave_id', '=', 'o.id')
                    ->orderBy('o.obra', $order)
                    ->select('entradas.*');
                break;

            case 'pedido_codigo':
                // subselect por pedido.codigo
                $query->orderBy(
                    \App\Models\Pedido::select('codigo')->whereColumn('pedidos.id', 'entradas.pedido_id'),
                    $order
                );
                break;

            case 'usuario':
                // subselect por users.name
                $query->orderBy(
                    \App\Models\User::select('name')->whereColumn('users.id', 'entradas.usuario_id'),
                    $order
                );
                break;

            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        return $query;
    }

    private function filtrosActivosEntradas(Request $request): array
    {
        $f = [];

        if ($request->filled('pedido_producto_id')) {
            $f[] = 'ID línea: <strong>' . (int)$request->pedido_producto_id . '</strong>';
        }

        if ($request->filled('albaran')) {
            $f[] = 'Albarán: <strong>' . e($request->albaran) . '</strong>';
        }

        if ($request->filled('codigo_sage')) {
            $f[] = 'Código SAGE: <strong>' . e($request->codigo_sage) . '</strong>';
        }

        if ($request->filled('obra')) {
            $f[] = 'Nave: <strong>' . e($request->obra) . '</strong>';
        }

        if ($request->filled('nave_id')) {
            $f[] = 'Nave ID: <strong>' . (int)$request->nave_id . '</strong>';
        }

        // ✅ FILTROS ACTIVOS PARA PRODUCTO BASE (3 campos separados)
        $productoFiltros = [];

        if ($request->filled('producto_tipo')) {
            $productoFiltros[] = 'Tipo: ' . e($request->producto_tipo);
        }

        if ($request->filled('producto_diametro')) {
            $productoFiltros[] = 'Ø' . e($request->producto_diametro);
        }

        if ($request->filled('producto_longitud')) {
            $productoFiltros[] = e($request->producto_longitud) . 'm';
        }

        if (!empty($productoFiltros)) {
            $f[] = 'Producto: <strong>' . implode(' | ', $productoFiltros) . '</strong>';
        }

        if ($request->filled('pedido_codigo')) {
            $f[] = 'Pedido compra: <strong>' . e($request->pedido_codigo) . '</strong>';
        }

        if ($request->filled('usuario')) {
            $f[] = 'Usuario: <strong>' . e($request->usuario) . '</strong>';
        }

        if ($request->filled('sort')) {
            $map = [
                'pedido_producto_id' => 'ID Línea Pedido',
                'albaran'            => 'Albarán',
                'codigo_sage'        => 'Código SAGE',
                'nave'               => 'Nave',
                'nave_id'            => 'Nave',
                'pedido_codigo'      => 'Pedido Compra',
                'usuario'            => 'Usuario',
                'created_at'         => 'Fecha',
            ];
            $orden = strtolower($request->input('order', 'desc')) === 'asc' ? 'ascendente' : 'descendente';
            $f[] = 'Ordenado por <strong>' . ($map[$request->sort] ?? $request->sort) . '</strong> en orden <strong>' . $orden . '</strong>';
        }

        if ($request->filled('per_page')) {
            $f[] = 'Mostrando <strong>' . (int)$request->per_page . '</strong> por página';
        }

        return $f;
    }

    private function getOrdenamientoEntradas(string $columna, string $titulo): string
    {
        $currentSort  = request('sort');
        $currentOrder = request('order', 'desc');
        $isSorted     = $currentSort === $columna;
        $nextOrder    = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = $isSorted
            ? ($currentOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down')
            : 'fas fa-sort';

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . $url . '" class="text-white text-decoration-none">' . $titulo . ' <i class="' . $icon . '"></i></a>';
    }

    // Mostrar todas las entradas
    public function index(Request $request)
    {
        // 🔐 Operario → a pedidos
        if (auth()->user()->rol === 'operario') {
            return redirect()->route('pedidos.index');
        }

        try {
            $query = Entrada::with([
                'ubicacion',
                'user:id,name',
                'productos.productoBase',
                'productos.fabricante',
                'pedido:id,codigo',
                'nave',                           // ✅ Para mostrar nombre de la nave
                'pedidoProducto.productoBase',    // ✅ CRÍTICO para mostrar producto base
            ])->withCount('productos');

            // Filtros + orden
            $this->aplicarFiltrosEntradas($query, $request);

            // Cabeceras ordenables
            $ordenables = [
                'pedido_producto_id' => $this->getOrdenamientoEntradas('pedido_producto_id', 'ID Línea Pedido'),
                'albaran'            => $this->getOrdenamientoEntradas('albaran', 'Albarán'),
                'codigo_sage'        => $this->getOrdenamientoEntradas('codigo_sage', 'Código SAGE'),
                'nave_id'            => $this->getOrdenamientoEntradas('nave_id', 'Nave'),
                'pedido_codigo'      => $this->getOrdenamientoEntradas('pedido_codigo', 'Pedido Compra'),
                'usuario'            => $this->getOrdenamientoEntradas('usuario', 'Usuario'),
                'created_at'         => $this->getOrdenamientoEntradas('created_at', 'Fecha'),
            ];

            $filtrosActivos = $this->filtrosActivosEntradas($request);
            $fabricantes    = Fabricante::select('id', 'nombre')->get();
            $distribuidores = Distribuidor::select('id', 'nombre')->get();

            $perPage  = (int) $request->input('per_page', 10);
            $entradas = $query->paginate($perPage)->appends($request->all());

            return view('entradas.index', compact(
                'entradas',
                'fabricantes',
                'distribuidores',
                'filtrosActivos',
                'ordenables'
            ));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }


    public function create()
    {
        // 1) Ubicaciones completas (incluye 'almacen')
        $ubicaciones = Ubicacion::select('id', 'nombre', 'almacen')->get()->map(function ($u) {
            $u->nombre_sin_prefijo = Str::after($u->nombre, 'Almacén ');
            return $u;
        });

        $usuarios      = User::all();
        $productosBase = ProductoBase::orderBy('tipo')->orderBy('diametro')->orderBy('longitud')->get();
        $fabricantes   = Fabricante::orderBy('nombre')->get();

        // 2) Último producto del usuario
        $ultimoProducto = Producto::with(['entrada', 'productoBase'])
            ->whereHas('entrada', fn($q) => $q->where('usuario_id', auth()->id()))
            ->latest()
            ->first();

        $ultimaColada         = $ultimoProducto?->n_colada;
        $ultimoProductoBaseId = $ultimoProducto?->producto_base_id;
        $ultimoFabricanteId   = $ultimoProducto?->fabricante_id ?? $ultimoProducto?->productoBase?->fabricante_id;
        $ultimaUbicacionId    = $ultimoProducto?->ubicacion_id;

        // 3) OBRAS del cliente cuya empresa like %paco reyes%
        //    (ajusta el nombre del modelo/relación si tu Obra tiene otra relación con Cliente)
        $obras = Obra::select('id', 'obra')
            ->whereHas('cliente', function ($q) {
                $q->where('empresa', 'like', '%paco reyes%');
            })
            ->orderBy('obra')
            ->get();

        // Mapa obra_id => código de almacén (0A/0B/AL) según el texto de 'obra'
        $obraAlmacenes = $obras->mapWithKeys(function ($o) {
            $nombre = Str::lower($o->obra);
            $code = Str::contains($nombre, 'nave a') ? '0A'
                : (Str::contains($nombre, 'nave b') ? '0B' : 'AL');
            return [$o->id => $code];
        });

        $obraActualId = $ultimoProducto?->obra_id ?? null;

        // 4) Vista
        return view('entradas.create', compact(
            'ubicaciones',
            'usuarios',
            'productosBase',
            'fabricantes',
            'ultimaColada',
            'ultimoProductoBaseId',
            'ultimoFabricanteId',
            'ultimaUbicacionId',
            'obras',
            'obraActualId',
            'obraAlmacenes' // 👈 NUEVO
        ));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // 1) Validación
            $request->validate([
                'codigo'            => ['required', 'string', 'unique:productos,codigo', 'max:20', 'regex:/^MP.*/i'],
                'codigo_2'          => ['nullable', 'string', 'unique:productos,codigo', 'max:20', 'regex:/^MP.*/i'],
                'fabricante_id'     => ['required', 'exists:fabricantes,id'],
                'albaran'           => ['required', 'string', 'min:1', 'max:30'],
                'pedido_id'         => ['nullable', 'exists:pedidos,id'],
                'producto_base_id'  => ['required', 'exists:productos_base,id'],
                'n_colada'          => ['required', 'string', 'max:50'],
                'n_paquete'         => ['required', 'string', 'max:50'],
                'n_colada_2'        => ['nullable', 'string', 'max:50'],
                'n_paquete_2'       => ['nullable', 'string', 'max:50'],
                'peso'              => ['required', 'numeric', 'min:1'],
                'ubicacion_id'      => ['nullable', 'integer', 'exists:ubicaciones,id'], // ✅ clave correcta
                'obra_id'           => ['required', 'integer', 'exists:obras,id'],       // ✅ requerido para ambos productos
                'otros'             => ['nullable', 'string', 'max:255'],
            ], [
                'codigo.required'   => 'El código generado es obligatorio.',
                'codigo.string'     => 'El código debe ser una cadena de texto.',
                'codigo.unique'     => 'Ese código ya existe.',
                'codigo.max'        => 'El código no puede tener más de 20 caracteres.',
                'codigo.regex'      => 'El código debe empezar por MP.',

                'codigo_2.string'   => 'El segundo código debe ser una cadena de texto.',
                'codigo_2.unique'   => 'El segundo código ya existe.',
                'codigo_2.max'      => 'El segundo código no puede tener más de 20 caracteres.',

                'fabricante_id.required' => 'El fabricante es obligatorio.',
                'fabricante_id.exists'   => 'El fabricante seleccionado no es válido.',

                'albaran.required'  => 'El albarán es obligatorio.',
                'albaran.string'    => 'El albarán debe ser una cadena de texto.',
                'albaran.min'       => 'El albarán debe tener al menos 1 carácter.',
                'albaran.max'       => 'El albarán no puede tener más de 30 caracteres.',

                'pedido_id.exists'        => 'El pedido seleccionado no es válido.',
                'producto_base_id.required' => 'El producto base es obligatorio.',
                'producto_base_id.exists'  => 'El producto base seleccionado no es válido.',

                'n_colada.required' => 'El número de colada es obligatorio.',
                'n_colada.string'   => 'El número de colada debe ser una cadena de texto.',
                'n_colada.max'      => 'El número de colada no puede tener más de 50 caracteres.',

                'n_paquete.required' => 'El número de paquete es obligatorio.',
                'n_paquete.string'  => 'El número de paquete debe ser una cadena de texto.',
                'n_paquete.max'     => 'El número de paquete no puede tener más de 50 caracteres.',

                'n_colada_2.string' => 'El segundo número de colada debe ser una cadena de texto.',
                'n_colada_2.max'    => 'El segundo número de colada no puede tener más de 50 caracteres.',

                'n_paquete_2.string' => 'El segundo número de paquete debe ser una cadena de texto.',
                'n_paquete_2.max'   => 'El segundo número de paquete no puede tener más de 50 caracteres.',

                'peso.required'     => 'El peso es obligatorio.',
                'peso.numeric'      => 'El peso debe ser un número.',
                'peso.min'          => 'El peso debe ser mayor que cero.',

                'ubicacion_id.integer' => 'La ubicación debe ser un número entero.',
                'ubicacion_id.exists'  => 'La ubicación seleccionada no es válida.',

                'obra_id.required'  => 'Debes seleccionar un almacén (obra).',
                'obra_id.integer'   => 'La obra debe ser un número entero.',
                'obra_id.exists'    => 'La obra seleccionada no es válida.',

                'otros.string'      => 'El campo "otros" debe ser una cadena de texto.',
                'otros.max'         => 'El campo "otros" no puede tener más de 255 caracteres.',
            ]);

            // 2) Normalizaciones / cálculos
            $esDoble         = $request->filled('codigo_2') && $request->filled('n_colada_2') && $request->filled('n_paquete_2');
            $pesoTotal       = round((float)$request->peso, 3);
            $pesoPorPaquete  = $esDoble ? round($pesoTotal / 2, 3) : $pesoTotal;

            $codigo1 = strtoupper(trim($request->codigo));
            $codigo2 = $request->filled('codigo_2') ? strtoupper(trim($request->codigo_2)) : null;

            $fabricanteNombre = optional(\App\Models\Fabricante::find($request->fabricante_id))->nombre ?? '—';
            $otrosTexto       = trim((string)($request->otros ?? ''));
            $otrosComun       = 'Alta manual. Fabricante: ' . $fabricanteNombre . ($otrosTexto ? " | {$otrosTexto}" : '');

            // 3) Pedido producto (si aplica)
            $pedidoProductoId = null;
            if ($request->filled('pedido_id')) {
                $pedidoProducto = DB::table('pedido_productos')
                    ->where('pedido_id', $request->pedido_id)
                    ->where('producto_base_id', $request->producto_base_id)
                    ->where('estado', '!=', 'completado')
                    ->orderBy('fecha_estimada_entrega')
                    ->first();

                if ($pedidoProducto) {
                    $pedidoProductoId = $pedidoProducto->id;
                }
            }

            // 4) Crear Entrada
            $entrada = Entrada::create([
                'albaran'            => $request->albaran,
                'usuario_id'         => auth()->id(),
                'peso_total'         => $pesoTotal,
                'estado'             => 'cerrado',
                'otros'              => $otrosTexto ?: null,
                'pedido_id'          => $request->pedido_id,
                'pedido_producto_id' => $pedidoProductoId,
            ]);

            // 5) Crear primer producto
            $producto1 = Producto::create([
                'codigo'           => $codigo1,
                'producto_base_id' => $request->producto_base_id,
                'fabricante_id'    => $request->fabricante_id,
                'entrada_id'       => $entrada->id,
                'n_colada'         => $request->n_colada,
                'n_paquete'        => $request->n_paquete,
                'peso_inicial'     => $pesoPorPaquete,
                'peso_stock'       => $pesoPorPaquete,
                'estado'           => 'almacenado',
                'obra_id'          => $request->obra_id,
                'ubicacion_id'     => $request->ubicacion_id,
                'maquina_id'       => null,
                'otros'            => $otrosComun,
            ]);

            // 6) Crear segundo producto (si aplica)
            if ($esDoble) {
                Producto::create([
                    'codigo'           => $codigo2, // seguro: ya null-safe
                    'producto_base_id' => $request->producto_base_id,
                    'fabricante_id'    => $request->fabricante_id,
                    'entrada_id'       => $entrada->id,
                    'n_colada'         => $request->n_colada_2,
                    'n_paquete'        => $request->n_paquete_2,
                    'peso_inicial'     => $pesoPorPaquete,
                    'peso_stock'       => $pesoPorPaquete,
                    'estado'           => 'almacenado',
                    'obra_id'          => $request->obra_id,
                    'ubicacion_id'     => $request->ubicacion_id,
                    'maquina_id'       => null,
                    'otros'            => $otrosComun,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('productos.index')
                ->with('success', 'Entrada registrada correctamente.');
        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }


    public function edit($id)
    {
        $entrada = Entrada::findOrFail($id);  // Encuentra la entrada por su ID
        $ubicaciones = Ubicacion::all();  // Cargar todas las ubicaciones
        return view('entradas.edit', compact('entrada', 'ubicaciones'));
    }

    public function update(Request $request, Entrada $entrada)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'codigo_sage' => 'nullable|string|max:50',
            ]);



            $estado = $entrada->pedidoProducto->estado ?? null;

            if (!in_array($estado, ['completado', 'facturado'])) {
                throw ValidationException::withMessages([
                    'codigo_sage' => "Solo se puede editar el código SAGE si la línea está en estado 'completado' o 'facturado'."
                ]);
            }

            $entrada->update($validated);

            // ✅ Cambiar estado de línea según si hay código_sage o no
            if ($entrada->pedidoProducto) {
                $nuevoEstado = $entrada->codigo_sage ? 'facturado' : 'completado';
                Log::info("Línea de pedido actualizada a estado: $nuevoEstado");

                $entrada->pedidoProducto->update([
                    'estado' => $nuevoEstado,
                ]);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Entrada actualizada correctamente.',
                    'data'    => $entrada->fresh()
                ]);
            }

            return redirect()
                ->route('entradas.index')
                ->with('success', 'Entrada actualizada correctamente.');
        } catch (ValidationException $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors'  => $e->errors()
                ], 422);
            }

            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ocurrió un error en el servidor' . $e,
                    'error'   => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }


    public function subirPdf(Request $request)
    {
        $request->validate([
            'entrada_id'   => 'required|exists:entradas,id',
            'albaran_pdf'  => 'required|file|mimes:pdf|max:5120', // Máx. 5MB
        ], [
            'albaran_pdf.required' => 'Debes seleccionar un archivo PDF.',
            'albaran_pdf.mimes'    => 'El archivo debe ser un PDF.',
            'albaran_pdf.max'      => 'El archivo no puede superar los 5MB.',
        ]);

        $entrada = Entrada::findOrFail($request->entrada_id);

        // Borrar archivo anterior si existía
        if ($entrada->pdf_albaran) {
            Storage::disk('private')->delete("albaranes_entrada/{$entrada->pdf_albaran}");
        }

        // Guardar nuevo archivo
        $nombreArchivo = 'albaran_' . $entrada->id . '_' . time() . '.pdf';
        $request->file('albaran_pdf')->storeAs('albaranes_entrada', $nombreArchivo, 'private');

        // Guardar nombre en la base de datos
        $entrada->pdf_albaran = $nombreArchivo;
        $entrada->save();

        return redirect()->back()->with('success', 'PDF del albarán subido correctamente.');
    }

    public function descargarPdfFiltrados(Request $request)
    {
        $query = Entrada::query();
        $this->aplicarFiltrosEntradas($query, $request);
        $query->whereNotNull('pdf_albaran');

        $entradas = $query->get();

        if ($entradas->isEmpty()) {
            return redirect()->back()->with('error', 'No hay archivos PDF asociados a las entradas filtradas.');
        }

        $disk = Storage::disk('private');
        $zipPath = tempnam(sys_get_temp_dir(), 'entradas_pdf_');

        if ($zipPath === false) {
            return redirect()->back()->with('error', 'No se pudo preparar la descarga.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'No se pudo generar el archivo ZIP.');
        }

        $agregados = 0;
        foreach ($entradas as $entrada) {
            $relativePath = "albaranes_entrada/{$entrada->pdf_albaran}";
            if (!$entrada->pdf_albaran || !$disk->exists($relativePath)) {
                continue;
            }

            $nombre = 'entrada_' . $entrada->id;
            if ($entrada->albaran) {
                $nombre .= '_' . Str::slug($entrada->albaran);
            }
            $nombre .= '.pdf';

            $zip->addFile($disk->path($relativePath), $nombre);
            $agregados++;
        }

        $zip->close();

        if ($agregados === 0) {
            @unlink($zipPath);
            return redirect()->back()->with('error', 'No se encontraron archivos PDF disponibles para descargar.');
        }

        return response()->download($zipPath, 'entradas_filtradas_' . now()->format('YmdHis') . '.zip')
            ->deleteFileAfterSend(true);
    }

    public function descargarPdf($id)
    {
        $entrada = Entrada::findOrFail($id);
        $ruta = "albaranes_entrada/{$entrada->pdf_albaran}";

        if (!$entrada->pdf_albaran || !Storage::disk('private')->exists($ruta)) {
            abort(404, 'PDF no encontrado.');
        }

        return response()->file(Storage::disk('private')->path($ruta), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Albaran_' . $entrada->id . '.pdf"',
        ]);
    }
    public function cerrar(Request $request, $id)
    {
        // Necesitamos saber qué movimiento estás completando
        $request->validate([
            'movimiento_id' => ['required', 'exists:movimientos,id'],
        ], [
            'movimiento_id.required' => 'Falta el movimiento que estás completando.',
            'movimiento_id.exists'   => 'El movimiento indicado no existe.',
        ]);

        DB::transaction(function () use ($request, $id) {
            // 1) Cargar y bloquear recursos
            /** @var \App\Models\Entrada $entrada */
            $entrada = Entrada::with(['pedido'])->lockForUpdate()->findOrFail($id);

            if ($entrada->estado === 'cerrado') {
                abort(400, 'Este albarán ya está cerrado.');
            }

            /** @var \App\Models\Movimiento $movimiento */
            $movimiento = Movimiento::lockForUpdate()->findOrFail($request->movimiento_id);

            if ($movimiento->tipo !== 'entrada') {
                abort(422, 'El movimiento indicado no es de tipo entrada.');
            }
            if ($movimiento->estado === 'completado') {
                abort(422, 'Ese movimiento ya estaba completado.');
            }

            // Deben pertenecer al mismo pedido
            if ((int)$movimiento->pedido_id !== (int)$entrada->pedido_id) {
                abort(422, 'El movimiento y el albarán pertenecen a pedidos distintos.');
            }

            /** @var \App\Models\PedidoProducto $pivot */
            $pivot = PedidoProducto::lockForUpdate()->findOrFail($movimiento->pedido_producto_id);

            // 2) Si la entrada está asociada a otra línea, revisa integridad y reasigna
            if ((int)$entrada->pedido_producto_id !== (int)$pivot->id) {
                // Verificar que todos los productos de la entrada coinciden en producto_base_id con la línea del movimiento
                $mismatch = Producto::where('entrada_id', $entrada->id)
                    ->where('producto_base_id', '!=', $pivot->producto_base_id)
                    ->count();

                if ($mismatch > 0) {
                    abort(422, 'No se puede asociar este albarán a la línea del movimiento porque contiene productos de otro producto base.');
                }

                // Reasignar la entrada a la línea del movimiento
                $entrada->pedido_producto_id = $pivot->id;
                $entrada->save();
            }

            // 3) Peso recepcionado para ESTA línea (sumando todos los productos de todas las entradas de la línea)
            $pesoRecepcionado = Producto::where('producto_base_id', $pivot->producto_base_id)
                ->whereHas('entrada', fn($q) => $q->where('pedido_producto_id', $pivot->id))
                ->sum('peso_inicial');

            Log::info('📦 Cierre de albarán por movimiento', [
                'entrada_id'           => $entrada->id,
                'movimiento_id'        => $movimiento->id,
                'pedido_producto_id'   => $pivot->id,
                'producto_base_id'     => $pivot->producto_base_id,
                'cantidad_pedida'      => $pivot->cantidad,
                'peso_recepcionado'    => $pesoRecepcionado,
            ]);

            // 4) Estado de la línea
            $estado = match (true) {
                $pesoRecepcionado >= $pivot->cantidad * 0.8 => 'completado',
                $pesoRecepcionado > 0 => 'parcial',
                default => 'pendiente',
            };

            PedidoProducto::whereKey($pivot->id)->update([
                'cantidad_recepcionada' => $pesoRecepcionado,
                'estado'                => $estado,
                // Si esta fecha era "prevista original", quítala. Si es "última recepción", entonces ok.
                'fecha_estimada_entrega' => now(),
            ]);

            // 5) Cerrar entrada
            $entrada->estado = 'cerrado';
            $entrada->save();

            // 6) Completar el movimiento que estás cerrando y, de paso, todos los pendientes de esa misma línea
            Movimiento::where('id', $movimiento->id)
                ->orWhere(function ($q) use ($entrada, $pivot) {
                    $q->where('pedido_id', $entrada->pedido_id)
                        ->where('pedido_producto_id', $pivot->id)
                        ->where('estado', '!=', 'completado');
                })
                ->lockForUpdate()
                ->update([
                    'estado'          => 'completado',
                    'ejecutado_por'   => auth()->id(),
                    'fecha_ejecucion' => now(),
                ]);

            // 7) ¿Pedido completo?
            $lineas = PedidoProducto::where('pedido_id', $entrada->pedido_id)->get();
            $todosCompletados = $lineas->every(fn($l) => $l->estado === 'completado');

            if ($todosCompletados) {
                $entrada->pedido->estado = 'completado';
                $entrada->pedido->save();
                Log::info('✅ Pedido completado automáticamente', ['pedido_id' => $entrada->pedido->id]);
            } else {
                Log::info('ℹ️ Pedido con líneas pendientes/parciales', ['pedido_id' => $entrada->pedido->id]);
            }

            Log::info('✅ Línea de pedido actualizada (cierre desde movimiento)', [
                'pedido_producto_id' => $pivot->id,
                'nuevo_estado'       => $estado,
                'peso_recepcionado'  => $pesoRecepcionado,
            ]);
        });

        return redirect()->route('maquinas.index')->with('success', 'Albarán cerrado correctamente.');
    }

    // Eliminar una entrada y sus productos asociados
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            // Buscar la entrada a eliminar
            $entrada = Entrada::findOrFail($id);

            // Eliminar los productos asociados a la entrada
            $entrada->productos()->delete();

            // Eliminar la entrada
            $entrada->delete();
            DB::commit();  // Confirmamos la transacción
            return redirect()->route('entradas.index')->with('success', 'Entrada eliminada correctamente.');
        } catch (Exception $e) {
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error al eliminar la entrada: ' . $e->getMessage());
        }
    }
}
