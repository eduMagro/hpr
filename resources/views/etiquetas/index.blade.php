<x-app-layout>
    <x-slot name="title">Etiquetas - {{ config('app.name') }}</x-slot>

    <style>
        @media (max-width: 767px) {
            .etiquetas-desktop {
                display: none !important;
            }
        }
    </style>

    <div class="w-full hidden">
        <!-- Vista Desktop -->
        <div class="etiquetas-desktop hidden md:block p-4 sm:p-2">
            <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
            <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
                <table class="w-full min-w-[1200px] border border-gray-300 rounded-lg">
                    <thead class="bg-blue-500 text-white text-10">
                        <tr class="text-center text-xs uppercase">
                            <th class="p-2">{!! $ordenables['id'] ?? 'ID' !!}</th>
                            <th class="p-2">Codigo</th>
                            <th class="p-2">Codigo SubEtiqueta</th>
                            <th class="p-2">{!! $ordenables['codigo_planilla'] ?? 'Planilla' !!}</th>
                            <th class="p-2">{!! $ordenables['paquete'] ?? 'Paquete' !!}</th>
                            <th class="p-2">Op 1</th>
                            <th class="p-2">Op 2</th>
                            <th class="p-2">Ens 1</th>
                            <th class="p-2">Ens 2</th>
                            <th class="p-2">Sol 1</th>
                            <th class="p-2">Sol 2</th>
                            <th class="p-2">{!! $ordenables['numero_etiqueta'] ?? 'Número de Etiqueta' !!}</th>
                            <th class="p-2">{!! $ordenables['nombre'] ?? 'Nombre' !!}</th>
                            <th class="p-2">Marca</th>
                            <th class="p-2">{!! $ordenables['peso'] ?? 'Peso (kg)' !!}</th>
                            <th class="p-2">Inicio Fabricación</th>
                            <th class="p-2">Final Fabricación</th>
                            <th class="p-2">Inicio Ensamblado</th>
                            <th class="p-2">Final Ensamblado</th>
                            <th class="p-2">Inicio Soldadura</th>
                            <th class="p-2">Final Soldadura</th>
                            <th class="p-2">{!! $ordenables['estado'] ?? 'Estado' !!}</th>
                            <th class="p-2">Acciones</th>
                        </tr>

                        <tr class="text-center text-xs uppercase">
                            <form method="GET" action="{{ route('etiquetas.index') }}">
                                <th class="p-1 border">
                                    <x-tabla.input name="id" value="{{ request('id') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="codigo" value="{{ request('codigo') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="etiqueta_sub_id" value="{{ request('etiqueta_sub_id') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="codigo_planilla" value="{{ request('codigo_planilla') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="paquete" value="{{ request('paquete') }}" />
                                </th>
                                <th class="p-1 border"></th>
                                <th class="p-1 border"></th>
                                <th class="p-1 border"></th>
                                <th class="p-1 border"></th>
                                <th class="p-1 border"></th>
                                <th class="p-1 border"></th>
                                <th class="p-1 border">
                                    <x-tabla.input name="numero_etiqueta" value="{{ request('numero_etiqueta') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="nombre" value="{{ request('nombre') }}" />
                                </th>
                                <th class="p-1 border"></th>
                                <th class="p-1 border"></th>
                                <th class="p-1 border">
                                    <x-tabla.input type="date" name="inicio_fabricacion"
                                        value="{{ request('inicio_fabricacion') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input type="date" name="final_fabricacion"
                                        value="{{ request('final_fabricacion') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input type="date" name="inicio_ensamblado"
                                        value="{{ request('inicio_ensamblado') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input type="date" name="final_ensamblado"
                                        value="{{ request('final_ensamblado') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input type="date" name="inicio_soldadura"
                                        value="{{ request('inicio_soldadura') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input type="date" name="final_soldadura"
                                        value="{{ request('final_soldadura') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.select name="estado" :options="[
                                        'pendiente' => 'Pendiente',
                                        'fabricando' => 'Fabricando',
                                        'ensamblando' => 'Ensamblando',
                                        'soldando' => 'Soldando',
                                        'completada' => 'Completada',
                                    ]" :selected="request('estado')" wire:navigate empty="Todos" />
                                </th>
                                <x-tabla.botones-filtro ruta="etiquetas.index" />
                            </form>
                        </tr>
                    </thead>

                    <tbody class="text-gray-700 text-sm">
                        @forelse ($etiquetas as $etiqueta)
                            <tr tabindex="0" x-data="{
                                editando: false,
                                etiqueta: @js($etiqueta),
                                original: JSON.parse(JSON.stringify(@js($etiqueta)))
                            }" @dblclick="if(!$event.target.closest('input')) {
                              if(!editando) {
                                editando = true;
                              } else {
                                etiqueta = JSON.parse(JSON.stringify(original));
                                editando = false;
                              }
                            }" @keydown.enter.stop="guardarCambios(etiqueta); editando = false"
                                :class="{ 'bg-yellow-100': editando }"
                                class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">
                                <td class="p-2 text-center border">{{ $etiqueta->id }}</td>
                                <td class="p-2 text-center border">{{ $etiqueta->codigo }}</td>
                                <td class="p-2 text-center border">{{ $etiqueta->etiqueta_sub_id }}</td>
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->planilla_id)
                                        <a href="{{ route('planillas.index', ['planilla_id' => $etiqueta->planilla_id]) }}"
                                            wire:navigate class="text-blue-500 hover:underline">
                                            {{ $etiqueta->planilla->codigo_limpio }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="p-2 text-center border">
                                    @if (isset($etiqueta->paquete->codigo))
                                        <a href="{{ route('paquetes.index', [$etiqueta->paquete_id => $etiqueta->paquete->codigo]) }}"
                                            wire:navigate class="text-blue-500 hover:underline">
                                            {{ $etiqueta->paquete->codigo }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->operario1)
                                        <a href="{{ route('users.index', ['users_id' => $etiqueta->operario1]) }}"
                                            wire:navigate class="text-blue-500 hover:underline">
                                            {{ $etiqueta->operario1->name }}
                                            {{ $etiqueta->operario1->primer_apellido }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->opeario2)
                                        <a href="{{ route('users.index', ['users_id' => $etiqueta->opeario2]) }}"
                                            wire:navigate class="text-blue-500 hover:underline">
                                            {{ $etiqueta->opeario2->name }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->ensamblador1)
                                        <a href="{{ route('users.index', ['users_id' => $etiqueta->ensamblador1]) }}"
                                            wire:navigate class="text-blue-500 hover:underline">
                                            {{ $etiqueta->ensamblador1->name }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->ensamblador2)
                                        <a href="{{ route('users.index', ['users_id' => $etiqueta->ensamblador2]) }}"
                                            wire:navigate class="text-blue-500 hover:underline">
                                            {{ $etiqueta->ensamblador2->name }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="p-2 text-center border">{{ $etiqueta->soldador1->name ?? 'N/A' }}</td>
                                <td class="p-2 text-center border">{{ $etiqueta->soldador2->name ?? 'N/A' }}</td>
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.numero_etiqueta"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="etiqueta.numero_etiqueta"
                                        class="form-control form-control-sm" @click.stop>
                                </td>
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.nombre"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="etiqueta.nombre"
                                        class="form-control form-control-sm" @click.stop>
                                </td>
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.marca"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="etiqueta.marca"
                                        class="form-control form-control-sm" @click.stop>
                                </td>
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.peso"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="etiqueta.peso"
                                        class="form-control form-control-sm" @click.stop>
                                </td>
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_inicio"></span>
                                    </template>
                                    <input x-show="editando" type="date" x-model="etiqueta.fecha_inicio"
                                        class="form-control form-control-sm" @click.stop>
                                </td>
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_finalizacion"></span>
                                    </template>
                                    <input x-show="editando" type="date" x-model="etiqueta.fecha_finalizacion"
                                        class="form-control form-control-sm" @click.stop>
                                </td>
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_inicio_ensamblado"></span>
                                    </template>
                                    <input x-show="editando" type="date" x-model="etiqueta.fecha_inicio_ensamblado"
                                        class="form-control form-control-sm" @click.stop>
                                </td>
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_finalizacion_ensamblado"></span>
                                    </template>
                                    <input x-show="editando" type="date"
                                        x-model="etiqueta.fecha_finalizacion_ensamblado"
                                        class="form-control form-control-sm" @click.stop>
                                </td>
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_inicio_soldadura"></span>
                                    </template>
                                    <input x-show="editando" type="date" x-model="etiqueta.fecha_inicio_soldadura"
                                        class="form-control form-control-sm" @click.stop>
                                </td>
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_finalizacion_soldadura"></span>
                                    </template>
                                    <input x-show="editando" type="date"
                                        x-model="etiqueta.fecha_finalizacion_soldadura"
                                        class="form-control form-control-sm" @click.stop>
                                </td>
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span
                                            x-text="etiqueta.estado ? etiqueta.estado.charAt(0).toUpperCase() + etiqueta.estado.slice(1) : ''"></span>
                                    </template>
                                    <select x-show="editando" x-model="etiqueta.estado" class="form-select w-full"
                                        @click.stop>
                                        <option value="pendiente">Pendiente</option>
                                        <option value="fabricando">Fabricando</option>
                                        <option value="completada">Completada</option>
                                    </select>
                                </td>
                                <td class="px-2 py-2 border text-xs font-bold">
                                    <div class="flex items-center space-x-2 justify-center">
                                        <x-tabla.boton-guardar x-show="editando"
                                            @click="guardarCambios(etiqueta); editando = false" />
                                        <x-tabla.boton-cancelar-edicion x-show="editando" @click="editando = false" />
                                        <template x-if="!editando">
                                            <div class="flex items-center space-x-2">
                                                <x-tabla.boton-editar @click="editando = true" x-show="!editando" />
                                                <button @click="mostrar({{ $etiqueta->id }})" wire:navigate
                                                    class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center"
                                                    title="Ver">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>
                                                <x-tabla.boton-eliminar :action="route('etiquetas.destroy', $etiqueta->id)" />
                                            </div>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="23" class="text-center py-4 text-gray-500">No hay etiquetas registradas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-tabla.paginacion :paginador="$etiquetas" />
        </div>

        <!-- Vista Móvil -->
        <div class="block md:hidden mt-4 space-y-3 pb-6">
            <div class="bg-gradient-to-tr from-gray-900 to-gray-700 text-white rounded-xl p-3 shadow-lg">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex-1">
                        <p class="text-[10px] uppercase tracking-wide text-gray-300">Etiquetas</p>
                        <h2 class="text-base font-semibold">Control de producción</h2>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-3">
                <form method="GET" action="{{ route('etiquetas.index') }}" class="space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">Código</label>
                            <input type="text" name="codigo" value="{{ request('codigo') }}"
                                placeholder="Buscar..."
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">SubEtiqueta</label>
                            <input type="text" name="etiqueta_sub_id" value="{{ request('etiqueta_sub_id') }}"
                                placeholder="Buscar..."
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">Planilla</label>
                            <input type="text" name="codigo_planilla" value="{{ request('codigo_planilla') }}"
                                placeholder="Buscar..."
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">Estado</label>
                            <select name="estado"
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700">
                                <option value="">Todos</option>
                                <option value="pendiente" @selected(request('estado') === 'pendiente')>Pendiente</option>
                                <option value="fabricando" @selected(request('estado') === 'fabricando')>Fabricando</option>
                                <option value="ensamblando" @selected(request('estado') === 'ensamblando')>Ensamblando</option>
                                <option value="soldando" @selected(request('estado') === 'soldando')>Soldando</option>
                                <option value="completada" @selected(request('estado') === 'completada')>Completada</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <a href="{{ route('etiquetas.index') }}"
                            class="text-xs text-gray-600 hover:text-gray-900">Limpiar</a>
                        <button type="submit"
                            class="bg-gray-900 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow hover:bg-gray-800">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            @php
                $mobilePage = max(1, (int) request('mpage', 1));
                $perPage = 10;

                // Query con filtros
                $query = \App\Models\Etiqueta::with(['planilla.cliente', 'planilla.obra', 'paquete']);

                // Aplicar filtros
                if (request('codigo')) {
                    $query->where('codigo', 'like', '%' . request('codigo') . '%');
                }

                if (request('etiqueta_sub_id')) {
                    $query->where('etiqueta_sub_id', 'like', '%' . request('etiqueta_sub_id') . '%');
                }

                if (request('codigo_planilla')) {
                    $query->whereHas('planilla', function ($q) {
                        $q->where('codigo', 'like', '%' . request('codigo_planilla') . '%');
                    });
                }

                if (request('estado')) {
                    $query->where('estado', request('estado'));
                }

                // Obtener etiquetas
                $etiquetasMobile = $query
                    ->latest()
                    ->skip(($mobilePage - 1) * $perPage)
                    ->take($perPage + 1)
                    ->get();

                $hayMasEtiquetas = $etiquetasMobile->count() > $perPage;

                if ($hayMasEtiquetas) {
                    $etiquetasMobile = $etiquetasMobile->take($perPage);
                }
            @endphp

            <div class="space-y-2">
                @forelse ($etiquetasMobile as $etiqueta)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div
                            class="bg-gradient-to-tr from-gray-900 to-gray-700 text-white px-3 py-2 flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-[9px] text-gray-300">SubEtiqueta</p>
                                <h3 class="text-sm font-semibold tracking-tight truncate">
                                    {{ $etiqueta->etiqueta_sub_id }}</h3>
                                <p class="text-[9px] text-gray-300 mt-0.5 truncate">{{ $etiqueta->nombre ?? '—' }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-semibold bg-white/10 border border-white/20">
                                    {{ strtoupper($etiqueta->estado ?? '—') }}
                                </span>
                            </div>
                        </div>

                        <div class="p-2.5 space-y-2 text-xs text-gray-700">
                            <div class="grid grid-cols-2 gap-2 text-[10px]">
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Planilla</p>
                                    <p class="font-semibold text-gray-900 truncate">
                                        {{ optional($etiqueta->planilla)->codigo_limpio ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Peso</p>
                                    <p class="font-semibold text-gray-900">
                                        {{ number_format($etiqueta->peso ?? 0, 2) }} kg</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Paquete</p>
                                    <p class="font-semibold text-gray-900 truncate">
                                        {{ optional($etiqueta->paquete)->codigo ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Cliente</p>
                                    <p class="font-semibold text-gray-900 truncate">
                                        {{ optional(optional($etiqueta->planilla)->cliente)->empresa ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-1 text-[10px] font-semibold pt-1">
                                <button onclick="abrirModalEditar({{ $etiqueta->id }}, @js($etiqueta))"
                                    class="px-2 py-1 rounded-lg bg-blue-500 text-white hover:bg-blue-600">
                                    Modificar
                                </button>
                                @if ($etiqueta->planilla_id)
                                    <a href="{{ route('planillas.show', $etiqueta->planilla_id) }}"
                                        class="px-2 py-1 rounded-lg bg-blue-200 text-blue-900 hover:bg-blue-300">
                                        Planilla
                                    </a>
                                @endif
                                @if (isset($etiqueta->paquete->codigo))
                                    <a href="{{ route('paquetes.index', ['codigo' => $etiqueta->paquete->codigo]) }}"
                                        class="px-2 py-1 rounded-lg bg-emerald-200 text-emerald-900 hover:bg-emerald-300">
                                        Paquete
                                    </a>
                                @endif
                                <a href="{{ route('etiquetas.index', ['codigo' => $etiqueta->codigo]) }}"
                                    class="px-2 py-1 rounded-lg bg-amber-200 text-amber-900 hover:bg-amber-300">
                                    Filtrar
                                </a>
                                <form action="{{ route('etiquetas.destroy', $etiqueta->id) }}" method="POST"
                                    class="inline"
                                    onsubmit="return confirm('¿Eliminar esta etiqueta? Esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-2 py-1 rounded-lg bg-red-200 text-red-800 hover:bg-red-300">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 text-center text-xs text-gray-600">
                        No hay etiquetas disponibles.
                    </div>
                @endforelse

                <!-- Paginación -->
                @if ($etiquetasMobile->count() > 0)
                    <div class="flex justify-between items-center gap-2 pt-2">
                        @if ($mobilePage > 1)
                            <a href="{{ route('etiquetas.index', array_merge(request()->except('mpage'), ['mpage' => $mobilePage - 1])) }}"
                                class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-800 text-xs font-semibold border border-gray-200 hover:bg-gray-200">
                                ← Anterior
                            </a>
                        @else
                            <span
                                class="px-3 py-1.5 rounded-lg bg-gray-50 text-gray-400 text-xs font-semibold border border-gray-100">
                                ← Anterior
                            </span>
                        @endif

                        <span class="text-xs text-gray-600">Página {{ $mobilePage }}</span>

                        @if ($hayMasEtiquetas)
                            <a href="{{ route('etiquetas.index', array_merge(request()->except('mpage'), ['mpage' => $mobilePage + 1])) }}"
                                class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-800 text-xs font-semibold border border-gray-200 hover:bg-gray-200">
                                Siguiente →
                            </a>
                        @else
                            <span
                                class="px-3 py-1.5 rounded-lg bg-gray-50 text-gray-400 text-xs font-semibold border border-gray-100">
                                Siguiente →
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal estilo etiqueta-máquina -->
    <div id="modalEtiqueta" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
        <div class="relative bg-white p-1 rounded-lg">
            <button onclick="imprimirEtiqueta('${subId}')" wire:navigate
                class="absolute top-2 right-10 text-blue-800 hover:text-blue-900 no-print">
                🖨️
            </button>
            <button onclick="cerrarModal()" aria-label="Cerrar"
                class="absolute -top-3 -right-3 bg-white border border-black rounded-full w-7 h-7 flex items-center justify-center text-xl leading-none hover:bg-red-100">
                &times;
            </button>
            <div id="modalEtiquetaBox" style="background-color:#fe7f09; border:1px solid black;"
                class="proceso shadow-xl w-full max-w-3xl rounded-lg overflow-y-auto max-h-[90vh]">
                <div id="modalContent" class="p-2"></div>
            </div>
        </div>
    </div>

    <!-- Modal de Edición (Móvil) -->
    @push('modals')
        <div id="modalEditarEtiqueta" class="hidden fixed inset-0 bg-black/50 z-[99999] flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg transform transition-all">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-base font-bold text-gray-800">Modificar Etiqueta</h2>
                    <button onclick="cerrarModalEditar()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form id="formEditarEtiqueta" class="p-4 space-y-3">
                    <input type="hidden" id="edit_etiqueta_id">

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-gray-700 mb-1">Número Etiqueta</label>
                            <input type="text" id="edit_numero_etiqueta"
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-[10px] font-semibold text-gray-700 mb-1">Marca</label>
                            <input type="text" id="edit_marca"
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-[10px] font-semibold text-gray-700 mb-1">Nombre</label>
                            <input type="text" id="edit_nombre"
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-[10px] font-semibold text-gray-700 mb-1">Peso (kg)</label>
                            <input type="number" step="0.01" id="edit_peso"
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-[10px] font-semibold text-gray-700 mb-1">Estado</label>
                            <select id="edit_estado"
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500">
                                <option value="pendiente">Pendiente</option>
                                <option value="fabricando">Fabricando</option>
                                <option value="ensamblando">Ensamblando</option>
                                <option value="soldando">Soldando</option>
                                <option value="completada">Completada</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-semibold text-gray-700 mb-1">Inicio Fabricación</label>
                            <input type="date" id="edit_fecha_inicio"
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-[10px] font-semibold text-gray-700 mb-1">Fin Fabricación</label>
                            <input type="date" id="edit_fecha_finalizacion"
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-[10px] font-semibold text-gray-700 mb-1">Inicio Ensamblado</label>
                            <input type="date" id="edit_fecha_inicio_ensamblado"
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-[10px] font-semibold text-gray-700 mb-1">Fin Ensamblado</label>
                            <input type="date" id="edit_fecha_finalizacion_ensamblado"
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-[10px] font-semibold text-gray-700 mb-1">Inicio Soldadura</label>
                            <input type="date" id="edit_fecha_inicio_soldadura"
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-[10px] font-semibold text-gray-700 mb-1">Fin Soldadura</label>
                            <input type="date" id="edit_fecha_finalizacion_soldadura"
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="button" onclick="cerrarModalEditar()"
                            class="flex-1 px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-xs font-semibold">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="flex-1 px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-semibold">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endpush

    <script>
        window.etiquetasConElementos = @json($etiquetasJson);
    </script>
    <script>
        function mostrar(etiquetaId) {
            const datos = window.etiquetasConElementos[etiquetaId];
            if (!datos) return;

            const subId = datos.etiqueta_sub_id ?? 'N/A';
            const nombre = datos.nombre ?? 'Sin nombre';
            const peso = datos.peso_kg ?? 'N/A';
            const cliente = datos.planilla?.cliente?.empresa ?? 'Sin cliente';
            const obra = datos.planilla?.obra?.obra ?? 'Sin obra';
            const planillaCod = datos.planilla?.codigo_limpio ?? 'N/A';
            const seccion = datos.planilla?.seccion ?? '';
            const etiquetaIdVisual = datos.id ?? 'N/A';

            const html = `
        <div class="text-lg font-semibold">${obra} – ${cliente}</div>
        <div class="text-md mb-2">${planillaCod} – S:${seccion}</div>
        <h3 class="text-lg font-semibold text-black">
            ${subId} ${nombre} – Cal:B500SD – ${peso} kg
        </h3>
        <div class="border-t border-black">
            <canvas id="canvas-modal-${etiquetaId}" class="w-full"></canvas>
        </div>
    `;

            const content = document.getElementById('modalContent');
            content.innerHTML = html;

            const modal = document.getElementById('modalEtiqueta');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            setTimeout(() => {
                dibujarCanvasEtiqueta(`canvas-modal-${etiquetaId}`, datos.elementos);
            }, 50);
        }

        function cerrarModal() {
            const modal = document.getElementById('modalEtiqueta');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.getElementById('modalEtiqueta')?.addEventListener('click', e => {
            if (e.target === e.currentTarget) cerrarModal();
        });
    </script>
    <script src="{{ asset('js/maquinaJS/canvasMaquina.js') }}" defer></script>
    <script src="{{ asset('js/maquinaJS/canvasMaquinaSinBoton.js') }}" defer></script>
    <script>
        function imprimirEtiqueta(etiquetaSubId) {
            const originalCanvas = document.querySelector('#modalContent canvas');
            if (!originalCanvas) return alert('Canvas no encontrado');

            const scale = 2;
            const tmp = document.createElement('canvas');
            tmp.width = originalCanvas.width * scale;
            tmp.height = originalCanvas.height * scale;
            const cctx = tmp.getContext('2d');
            cctx.scale(scale, scale);
            cctx.drawImage(originalCanvas, 0, 0);
            const canvasImg = tmp.toDataURL('image/png');

            const clone = document.getElementById('modalContent').cloneNode(true);
            clone.classList.add('etiqueta-print');
            clone.querySelectorAll('.no-print').forEach(el => el.remove());

            const canvasClone = clone.querySelector('canvas');
            const img = new Image();
            img.src = canvasImg;
            img.style.width = '100%';
            img.style.height = 'auto';
            if (canvasClone) canvasClone.replaceWith(img);

            const tempQR = document.createElement('div');
            document.body.appendChild(tempQR);
            new QRCode(tempQR, {
                text: etiquetaSubId,
                width: 60,
                height: 60
            });

            setTimeout(() => {
                const qrImg = tempQR.querySelector('img');
                const qrBox = document.createElement('div');
                qrBox.className = 'qr-print';
                qrBox.appendChild(qrImg);
                clone.insertBefore(qrBox, clone.firstChild);

                const w = window.open('', '_blank');
                const style = `
<style>
@page { size: A6 landscape; margin: 0; }
body { margin:0; font-family:Arial,sans-serif; }
.etiqueta-print{
    width: 200mm; height: 100mm;
    background:#fe7f09; border:2px solid #000;
    padding:4mm; box-sizing:border-box; position:relative;
}
.etiqueta-print img{ max-width:100%; height:auto; display:block; margin-top:6mm; }
.qr-print{
    position:absolute; top:8mm; right:8mm;
    width:60px; height:60px; border:2px solid #000; padding:0; background:#fff;
}
</style>`;

                w.document.write(`
<html><head><title>Etiqueta ${etiquetaSubId}</title>${style}</head>
<body>${clone.outerHTML}
<script>
  window.onload = () => { window.print(); setTimeout(()=>window.close(),800); };
<\/script>
</body></html>
        `);
                w.document.close();
                tempQR.remove();
            }, 250);
        }
    </script>
    <script>
        function guardarCambios(etiqueta) {
            fetch(`/etiquetas/${etiqueta.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(etiqueta)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        let errorMsg = data.message || "Ha ocurrido un error inesperado.";
                        if (data.errors) {
                            errorMsg = Object.values(data.errors).flat().join("<br>");
                        }
                        Swal.fire({
                            icon: "error",
                            title: "Error al actualizar",
                            html: errorMsg,
                            confirmButtonText: "OK",
                            showCancelButton: true,
                            cancelButtonText: "Reportar Error"
                        }).then((result) => {
                            if (result.dismiss === Swal.DismissReason.cancel) {
                                notificarProgramador(errorMsg);
                            }
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: "error",
                        title: "Error de conexión",
                        text: "No se pudo actualizar la etiqueta. Inténtalo nuevamente.",
                        confirmButtonText: "OK"
                    });
                });
        }

        // Funciones para modal de edición móvil
        function abrirModalEditar(id, etiqueta) {
            document.getElementById('edit_etiqueta_id').value = id;
            document.getElementById('edit_numero_etiqueta').value = etiqueta.numero_etiqueta || '';
            document.getElementById('edit_marca').value = etiqueta.marca || '';
            document.getElementById('edit_nombre').value = etiqueta.nombre || '';
            document.getElementById('edit_peso').value = etiqueta.peso || '';
            document.getElementById('edit_estado').value = etiqueta.estado || 'pendiente';
            document.getElementById('edit_fecha_inicio').value = etiqueta.fecha_inicio || '';
            document.getElementById('edit_fecha_finalizacion').value = etiqueta.fecha_finalizacion || '';
            document.getElementById('edit_fecha_inicio_ensamblado').value = etiqueta.fecha_inicio_ensamblado || '';
            document.getElementById('edit_fecha_finalizacion_ensamblado').value = etiqueta.fecha_finalizacion_ensamblado || '';
            document.getElementById('edit_fecha_inicio_soldadura').value = etiqueta.fecha_inicio_soldadura || '';
            document.getElementById('edit_fecha_finalizacion_soldadura').value = etiqueta.fecha_finalizacion_soldadura || '';

            document.getElementById('modalEditarEtiqueta').classList.remove('hidden');
        }

        function cerrarModalEditar() {
            document.getElementById('modalEditarEtiqueta').classList.add('hidden');
        }

        // Configurar SweetAlert centrado arriba
        const Toast = Swal.mixin({
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        // Manejar submit del formulario de edición
        document.getElementById('formEditarEtiqueta').addEventListener('submit', async function(e) {
            e.preventDefault();

            const id = document.getElementById('edit_etiqueta_id').value;
            const datos = {
                numero_etiqueta: document.getElementById('edit_numero_etiqueta').value,
                marca: document.getElementById('edit_marca').value,
                nombre: document.getElementById('edit_nombre').value,
                peso: document.getElementById('edit_peso').value,
                estado: document.getElementById('edit_estado').value,
                fecha_inicio: document.getElementById('edit_fecha_inicio').value,
                fecha_finalizacion: document.getElementById('edit_fecha_finalizacion').value,
                fecha_inicio_ensamblado: document.getElementById('edit_fecha_inicio_ensamblado').value,
                fecha_finalizacion_ensamblado: document.getElementById('edit_fecha_finalizacion_ensamblado').value,
                fecha_inicio_soldadura: document.getElementById('edit_fecha_inicio_soldadura').value,
                fecha_finalizacion_soldadura: document.getElementById('edit_fecha_finalizacion_soldadura').value,
            };

            try {
                const response = await fetch(`/etiquetas/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(datos)
                });

                const data = await response.json();

                if (data.success) {
                    cerrarModalEditar();
                    Toast.fire({
                        icon: 'success',
                        title: '¡Etiqueta actualizada correctamente!'
                    });
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    let errorMsg = data.message || "Ha ocurrido un error inesperado.";
                    if (data.errors) {
                        errorMsg = Object.values(data.errors).flat().join("<br>");
                    }
                    Toast.fire({
                        icon: 'error',
                        title: errorMsg
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Toast.fire({
                    icon: 'error',
                    title: 'Error de conexión. Inténtalo nuevamente.'
                });
            }
        });
    </script>
</x-app-layout>
