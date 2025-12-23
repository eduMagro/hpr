<div>
    <style>
        /* Ocultar flechas de inputs number */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
    <div class="w-full px-6 py-4">

        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

        <script>
            const empresasTransporteData = @json($empresasTransporte);
            const camionesData = @json($camionesJson);
        </script>

        <!-- Tabla con filtros Livewire -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[1400px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white text-4">
                    <tr class="text-center text-xs uppercase">
                        <x-tabla.encabezado-ordenable campo="codigo_salida" :sortActual="$sort" :orderActual="$order"
                            texto="Salida" />
                        <x-tabla.encabezado-ordenable campo="codigo_sage" :sortActual="$sort" :orderActual="$order"
                            texto="Código Sage" />
                        <x-tabla.encabezado-ordenable campo="cliente" :sortActual="$sort" :orderActual="$order"
                            texto="Cliente" />
                        <x-tabla.encabezado-ordenable campo="obra" :sortActual="$sort" :orderActual="$order"
                            texto="Obra" />
                        <x-tabla.encabezado-ordenable campo="empresa_transporte_id" :sortActual="$sort" :orderActual="$order"
                            texto="E. Transporte" />
                        <x-tabla.encabezado-ordenable campo="camion_id" :sortActual="$sort" :orderActual="$order"
                            texto="Camión" />
                        <th class="p-2 border">Peso (kg)</th>
                        <th class="p-2 border">H. Paral.</th>
                        <th class="p-2 border">Imp. Paral.</th>
                        <th class="p-2 border">H. Grúa</th>
                        <th class="p-2 border">Imp. Grúa</th>
                        <th class="p-2 border">H. Almacén</th>
                        <th class="p-2 border">Importe</th>
                        <x-tabla.encabezado-ordenable campo="fecha_salida" :sortActual="$sort" :orderActual="$order"
                            texto="Fecha Entrega" />
                        <x-tabla.encabezado-ordenable campo="estado" :sortActual="$sort" :orderActual="$order"
                            texto="Estado" />
                        <th class="p-2 border">Comentario</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>

                    <tr class="text-center text-xs uppercase bg-blue-400">
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="codigo_salida"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Código...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="codigo_sage"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Sage...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="cliente"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Cliente...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="obra"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Obra...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="empresa_transporte"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Transporte...">
                        </th>
                        <th class="p-1 border"></th>
                        <th class="p-1 border"></th>
                        <th class="p-1 border"></th>
                        <th class="p-1 border"></th>
                        <th class="p-1 border"></th>
                        <th class="p-1 border"></th>
                        <th class="p-1 border"></th>
                        <th class="p-1 border"></th>
                        <th class="p-1 border">
                            <div class="flex flex-col gap-1">
                                <input type="date" wire:model.live="fecha"
                                    class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                    title="Filtrar por día">
                                <select wire:model.live="mes"
                                    class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                                    <option value="">Mes...</option>
                                    @foreach ($mesesDisponibles as $mesItem)
                                        <option value="{{ $mesItem['value'] }}">{{ $mesItem['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </th>
                        <th class="p-1 border">
                            <select wire:model.live="estado"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="completada">Completada</option>
                                <option value="en_transito">En tránsito</option>
                            </select>
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="comentario"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Comentario...">
                        </th>
                        <th class="p-1 border text-center align-middle">
                            <div class="flex justify-center gap-2 items-center h-full">
                                <button wire:click="limpiarFiltros"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                                    title="Restablecer filtros">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                                    </svg>
                                </button>
                                <button wire:click="exportar" title="Descargar registros en Excel"
                                    class="bg-green-600 hover:bg-green-700 text-white rounded text-xs flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="h-6 w-8">
                                        <path fill="#21A366"
                                            d="M6 8c0-1.1.9-2 2-2h32c1.1 0 2 .9 2 2v32c0 1.1-.9 2-2 2H8c-1.1 0-2-.9-2-2V8z" />
                                        <path fill="#107C41" d="M8 8h16v32H8c-1.1 0-2-.9-2-2V10c0-1.1.9-2 2-2z" />
                                        <path fill="#33C481" d="M24 8h16v32H24z" />
                                        <path fill="#fff"
                                            d="M17.2 17h3.6l3.1 5.3 3.1-5.3h3.6l-5.1 8.4 5.3 8.6h-3.7l-3.3-5.6-3.3 5.6h-3.7l5.3-8.6-5.1-8.4z" />
                                    </svg>
                                </button>
                            </div>
                        </th>
                    </tr>
                </thead>

                <tbody class="text-gray-700">
                    @forelse ($salidas as $salida)
                        @foreach ($salida->salidaClientes as $index => $registro)
                            <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-100 text-xs leading-none transition-colors"
                                x-data="{
                                    salidaId: {{ $salida->id }},
                                    clienteId: {{ $registro->cliente_id ?? 'null' }},
                                    obraId: {{ $registro->obra_id ?? 'null' }},
                                    empresaId: {{ $salida->empresa_id ?? 'null' }},
                                    getCamiones() {
                                        return camionesData.filter(c => c.empresa_id == this.empresaId);
                                    }
                                }">

                                {{-- Código Salida (no editable) --}}
                                <td class="p-1 text-center border font-semibold">{{ $salida->codigo_salida }}</td>

                                {{-- Código Sage --}}
                                <td class="p-1 text-center border">
                                    <input type="text" value="{{ $salida->codigo_sage ?? '' }}"
                                        class="w-full text-xs border-0 bg-transparent text-center focus:bg-white focus:border focus:border-blue-500 focus:rounded px-1 py-0.5"
                                        @blur="guardarCampo(salidaId, 'codigo_sage', $event.target.value)"
                                        @keydown.enter="$event.target.blur()">
                                </td>

                                {{-- Cliente (no editable) --}}
                                <td class="p-1 text-center border">{{ $registro->cliente?->empresa ?? 'N/A' }}</td>

                                {{-- Obra (no editable) --}}
                                <td class="p-1 text-center border">{{ $registro->obra?->obra ?? 'N/A' }}</td>

                                {{-- Empresa Transporte --}}
                                <td class="p-1 text-center border">
                                    <select x-model="empresaId"
                                        class="w-full text-xs border-0 bg-transparent text-center focus:bg-white focus:border focus:border-blue-500 focus:rounded px-1 py-0.5"
                                        @change="guardarCampo(salidaId, 'empresa_id', empresaId)">
                                        <option value="">-</option>
                                        @foreach ($empresasTransporte as $empresa)
                                            <option value="{{ $empresa->id }}"
                                                {{ $salida->empresa_id == $empresa->id ? 'selected' : '' }}>
                                                {{ $empresa->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                {{-- Camión --}}
                                <td class="p-1 text-center border">
                                    <select
                                        class="w-full text-xs border-0 bg-transparent text-center focus:bg-white focus:border focus:border-blue-500 focus:rounded px-1 py-0.5"
                                        @change="guardarCampo(salidaId, 'camion_id', $event.target.value)">
                                        <option value="">-</option>
                                        <template x-for="camion in getCamiones()" :key="camion.id">
                                            <option :value="camion.id" x-text="camion.modelo"
                                                :selected="camion.id == {{ $salida->camion_id ?? 'null' }}"></option>
                                        </template>
                                    </select>
                                </td>

                                {{-- Peso total de paquetes de esta obra --}}
                                @php
                                    $pesoObra = $salida->paquetes
                                        ->filter(fn($p) => $p->planilla && $p->planilla->obra_id == $registro->obra_id)
                                        ->sum('peso');
                                @endphp
                                <td class="p-1 text-center border font-semibold text-blue-700">
                                    {{ number_format($pesoObra, 2, ',', '.') }}
                                </td>

                                {{-- Horas Paralización --}}
                                <td class="p-1 text-center border">
                                    <input type="number" step="0.01"
                                        value="{{ $registro->horas_paralizacion ?? 0 }}"
                                        class="w-14 text-xs border-0 bg-transparent text-center focus:bg-white focus:border focus:border-blue-500 focus:rounded px-1 py-0.5"
                                        @blur="guardarCampoRegistro(salidaId, clienteId, obraId, 'horas_paralizacion', $event.target.value)"
                                        @keydown.enter="$event.target.blur()">
                                </td>

                                {{-- Importe Paralización --}}
                                <td class="p-1 text-center border">
                                    <input type="number" step="0.01"
                                        value="{{ $registro->importe_paralizacion ?? 0 }}"
                                        class="w-14 text-xs border-0 bg-transparent text-center focus:bg-white focus:border focus:border-blue-500 focus:rounded px-1 py-0.5"
                                        @blur="guardarCampoRegistro(salidaId, clienteId, obraId, 'importe_paralizacion', $event.target.value)"
                                        @keydown.enter="$event.target.blur()">
                                </td>

                                {{-- Horas Grúa --}}
                                <td class="p-1 text-center border">
                                    <input type="number" step="0.01" value="{{ $registro->horas_grua ?? 0 }}"
                                        class="w-14 text-xs border-0 bg-transparent text-center focus:bg-white focus:border focus:border-blue-500 focus:rounded px-1 py-0.5"
                                        @blur="guardarCampoRegistro(salidaId, clienteId, obraId, 'horas_grua', $event.target.value)"
                                        @keydown.enter="$event.target.blur()">
                                </td>

                                {{-- Importe Grúa --}}
                                <td class="p-1 text-center border">
                                    <input type="number" step="0.01" value="{{ $registro->importe_grua ?? 0 }}"
                                        class="w-14 text-xs border-0 bg-transparent text-center focus:bg-white focus:border focus:border-blue-500 focus:rounded px-1 py-0.5"
                                        @blur="guardarCampoRegistro(salidaId, clienteId, obraId, 'importe_grua', $event.target.value)"
                                        @keydown.enter="$event.target.blur()">
                                </td>

                                {{-- Horas Almacén --}}
                                <td class="p-1 text-center border">
                                    <input type="number" step="0.01" value="{{ $registro->horas_almacen ?? 0 }}"
                                        class="w-14 text-xs border-0 bg-transparent text-center focus:bg-white focus:border focus:border-blue-500 focus:rounded px-1 py-0.5"
                                        @blur="guardarCampoRegistro(salidaId, clienteId, obraId, 'horas_almacen', $event.target.value)"
                                        @keydown.enter="$event.target.blur()">
                                </td>

                                {{-- Importe --}}
                                <td class="p-1 text-center border">
                                    <input type="number" step="0.01" value="{{ $registro->importe ?? 0 }}"
                                        class="w-14 text-xs border-0 bg-transparent text-center focus:bg-white focus:border focus:border-blue-500 focus:rounded px-1 py-0.5"
                                        @blur="guardarCampoRegistro(salidaId, clienteId, obraId, 'importe', $event.target.value)"
                                        @keydown.enter="$event.target.blur()">
                                </td>

                                {{-- Fecha Salida --}}
                                <td class="p-1 text-center border">
                                    @if ($salida->fecha_salida)
                                        {{ \Carbon\Carbon::parse($salida->fecha_salida)->format('d/m/Y H:i') }}
                                    @else
                                        -
                                    @endif
                                </td>

                                {{-- Estado --}}
                                <td class="p-1 text-center border">
                                    <select
                                        class="w-full text-xs border-0 bg-transparent text-center focus:bg-white focus:border focus:border-blue-500 focus:rounded px-1 py-0.5
                                            {{ $salida->estado === 'completada' ? 'text-green-700 font-semibold' : '' }}
                                            {{ $salida->estado === 'pendiente' ? 'text-yellow-700 font-semibold' : '' }}
                                            {{ $salida->estado === 'en_transito' ? 'text-blue-700 font-semibold' : '' }}"
                                        @change="guardarCampo(salidaId, 'estado', $event.target.value)">
                                        <option value="pendiente"
                                            {{ $salida->estado === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                        <option value="completada"
                                            {{ $salida->estado === 'completada' ? 'selected' : '' }}>Completada
                                        </option>
                                        <option value="en_transito"
                                            {{ $salida->estado === 'en_transito' ? 'selected' : '' }}>En tránsito
                                        </option>
                                    </select>
                                </td>

                                {{-- Comentario --}}
                                <td class="p-1 border max-w-[200px]">
                                    <div class="text-xs text-gray-600 truncate"
                                        title="{{ $salida->comentario ?? '' }}">
                                        {{ Str::limit($salida->comentario, 30) ?? '-' }}
                                    </div>
                                </td>

                                {{-- Acciones --}}
                                <td class="px-1 py-1 border text-xs">
                                    <div class="flex items-center space-x-1 justify-center">
                                        <x-tabla.boton-ver :href="route('salidas-ferralla.show', $salida->id)" />
                                        @if (auth()->user()->rol === 'oficina')
                                            <x-tabla.boton-eliminar :action="route('salidas-ferralla.destroy', $salida->id)" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="17" class="text-center py-4 text-gray-500">No hay salidas registradas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación Livewire -->
        <div class="mt-4">
            {{ $salidas->links('vendor.livewire.tailwind') }}
        </div>

        <!-- Resúmenes -->
        @if ($totalSalidasFiltradas > 0)
            <div class="mt-8 space-y-8">
                {{-- Resumen por Empresa Transporte --}}
                @if (!empty($resumenEmpresaTransporte))
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                        <div class="bg-gray-700 text-white px-6 py-3">
                            <h3 class="text-lg font-semibold">
                                Resumen por Empresa de Transporte - {{ $tituloResumen }}
                                <span class="text-sm font-normal">({{ $totalSalidasFiltradas }} salidas)</span>
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-200 text-left text-xs font-medium text-gray-700">
                                        <th class="py-2 px-4 border-b">E. Transporte</th>
                                        <th class="py-2 px-4 border-b text-center">H. Paralización</th>
                                        <th class="py-2 px-4 border-b text-right">Imp. Paralización</th>
                                        <th class="py-2 px-4 border-b text-center">H. Grúa</th>
                                        <th class="py-2 px-4 border-b text-right">Imp. Grúa</th>
                                        <th class="py-2 px-4 border-b text-center">H. Almacén</th>
                                        <th class="py-2 px-4 border-b text-right">Importe</th>
                                        <th class="py-2 px-4 border-b text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totales = [
                                            'horas_paralizacion' => 0,
                                            'importe_paralizacion' => 0,
                                            'horas_grua' => 0,
                                            'importe_grua' => 0,
                                            'horas_almacen' => 0,
                                            'importe' => 0,
                                            'total' => 0,
                                        ];
                                    @endphp
                                    @foreach ($resumenEmpresaTransporte as $empresa => $data)
                                        @php
                                            $totales['horas_paralizacion'] += $data['horas_paralizacion'];
                                            $totales['importe_paralizacion'] += $data['importe_paralizacion'];
                                            $totales['horas_grua'] += $data['horas_grua'];
                                            $totales['importe_grua'] += $data['importe_grua'];
                                            $totales['horas_almacen'] += $data['horas_almacen'];
                                            $totales['importe'] += $data['importe'];
                                            $totales['total'] += $data['total'];
                                        @endphp
                                        <tr class="text-xs hover:bg-gray-50">
                                            <td class="py-2 px-4 border-b font-semibold">{{ $empresa }}</td>
                                            <td class="py-2 px-4 border-b text-center">
                                                {{ number_format($data['horas_paralizacion'], 2) }} h</td>
                                            <td class="py-2 px-4 border-b text-right">
                                                {{ number_format($data['importe_paralizacion'], 2) }} €</td>
                                            <td class="py-2 px-4 border-b text-center">
                                                {{ number_format($data['horas_grua'], 2) }} h</td>
                                            <td class="py-2 px-4 border-b text-right">
                                                {{ number_format($data['importe_grua'], 2) }} €</td>
                                            <td class="py-2 px-4 border-b text-center">
                                                {{ number_format($data['horas_almacen'], 2) }} h</td>
                                            <td class="py-2 px-4 border-b text-right">
                                                {{ number_format($data['importe'], 2) }} €</td>
                                            <td class="py-2 px-4 border-b text-right font-bold text-blue-600">
                                                {{ number_format($data['total'], 2) }} €</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-100 font-bold">
                                    <tr class="text-xs">
                                        <td class="py-2 px-4 border-t">TOTAL</td>
                                        <td class="py-2 px-4 border-t text-center">
                                            {{ number_format($totales['horas_paralizacion'], 2) }} h</td>
                                        <td class="py-2 px-4 border-t text-right">
                                            {{ number_format($totales['importe_paralizacion'], 2) }} €</td>
                                        <td class="py-2 px-4 border-t text-center">
                                            {{ number_format($totales['horas_grua'], 2) }} h</td>
                                        <td class="py-2 px-4 border-t text-right">
                                            {{ number_format($totales['importe_grua'], 2) }} €</td>
                                        <td class="py-2 px-4 border-t text-center">
                                            {{ number_format($totales['horas_almacen'], 2) }} h</td>
                                        <td class="py-2 px-4 border-t text-right">
                                            {{ number_format($totales['importe'], 2) }} €</td>
                                        <td class="py-2 px-4 border-t text-right text-green-600">
                                            {{ number_format($totales['total'], 2) }} €</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Resumen por Cliente y Obra --}}
                @if (!empty($resumenClienteObra))
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                        <div class="bg-gray-700 text-white px-6 py-3">
                            <h3 class="text-lg font-semibold">
                                Resumen por Cliente y Obra - {{ $tituloResumen }}
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-200 text-left text-xs font-medium text-gray-700">
                                        <th class="py-2 px-4 border-b">Cliente - Obra</th>
                                        <th class="py-2 px-4 border-b text-center">H. Paralización</th>
                                        <th class="py-2 px-4 border-b text-right">Imp. Paralización</th>
                                        <th class="py-2 px-4 border-b text-center">H. Grúa</th>
                                        <th class="py-2 px-4 border-b text-right">Imp. Grúa</th>
                                        <th class="py-2 px-4 border-b text-center">H. Almacén</th>
                                        <th class="py-2 px-4 border-b text-right">Importe</th>
                                        <th class="py-2 px-4 border-b text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalesObra = [
                                            'horas_paralizacion' => 0,
                                            'importe_paralizacion' => 0,
                                            'horas_grua' => 0,
                                            'importe_grua' => 0,
                                            'horas_almacen' => 0,
                                            'importe' => 0,
                                            'total' => 0,
                                        ];
                                    @endphp
                                    @foreach ($resumenClienteObra as $clienteObra => $data)
                                        @php
                                            $totalesObra['horas_paralizacion'] += $data['horas_paralizacion'];
                                            $totalesObra['importe_paralizacion'] += $data['importe_paralizacion'];
                                            $totalesObra['horas_grua'] += $data['horas_grua'];
                                            $totalesObra['importe_grua'] += $data['importe_grua'];
                                            $totalesObra['horas_almacen'] += $data['horas_almacen'];
                                            $totalesObra['importe'] += $data['importe'];
                                            $totalesObra['total'] += $data['total'];
                                        @endphp
                                        <tr class="text-xs hover:bg-gray-50">
                                            <td class="py-2 px-4 border-b font-semibold">{{ $clienteObra }}</td>
                                            <td class="py-2 px-4 border-b text-center">
                                                {{ number_format($data['horas_paralizacion'], 2) }} h</td>
                                            <td class="py-2 px-4 border-b text-right">
                                                {{ number_format($data['importe_paralizacion'], 2) }} €</td>
                                            <td class="py-2 px-4 border-b text-center">
                                                {{ number_format($data['horas_grua'], 2) }} h</td>
                                            <td class="py-2 px-4 border-b text-right">
                                                {{ number_format($data['importe_grua'], 2) }} €</td>
                                            <td class="py-2 px-4 border-b text-center">
                                                {{ number_format($data['horas_almacen'], 2) }} h</td>
                                            <td class="py-2 px-4 border-b text-right">
                                                {{ number_format($data['importe'], 2) }} €</td>
                                            <td class="py-2 px-4 border-b text-right font-bold text-blue-600">
                                                {{ number_format($data['total'], 2) }} €</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-100 font-bold">
                                    <tr class="text-xs">
                                        <td class="py-2 px-4 border-t">TOTAL</td>
                                        <td class="py-2 px-4 border-t text-center">
                                            {{ number_format($totalesObra['horas_paralizacion'], 2) }} h</td>
                                        <td class="py-2 px-4 border-t text-right">
                                            {{ number_format($totalesObra['importe_paralizacion'], 2) }} €</td>
                                        <td class="py-2 px-4 border-t text-center">
                                            {{ number_format($totalesObra['horas_grua'], 2) }} h</td>
                                        <td class="py-2 px-4 border-t text-right">
                                            {{ number_format($totalesObra['importe_grua'], 2) }} €</td>
                                        <td class="py-2 px-4 border-t text-center">
                                            {{ number_format($totalesObra['horas_almacen'], 2) }} h</td>
                                        <td class="py-2 px-4 border-t text-right">
                                            {{ number_format($totalesObra['importe'], 2) }} €</td>
                                        <td class="py-2 px-4 border-t text-right text-green-600">
                                            {{ number_format($totalesObra['total'], 2) }} €</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <script>
        // Guardar campo de salida (codigo_sage, empresa_id, camion_id, estado)
        function guardarCampo(salidaId, field, value) {
            fetch(`/salidas-ferralla/${salidaId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        field,
                        value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Feedback visual breve
                        mostrarGuardado();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo guardar el cambio.'
                    });
                });
        }

        // Guardar campo de registro (salida_cliente)
        function guardarCampoRegistro(salidaId, clienteId, obraId, field, value) {
            fetch(`/salidas-ferralla/${salidaId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        field,
                        value,
                        cliente_id: clienteId,
                        obra_id: obraId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarGuardado();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo guardar el cambio.'
                    });
                });
        }

        // Toast de guardado con SweetAlert
        function mostrarGuardado() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: false,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: 'success',
                title: 'Guardado'
            });
        }
    </script>
</div>
