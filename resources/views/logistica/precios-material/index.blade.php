<x-app-layout>
    <x-slot name="title">Precios Material - {{ config('app.name') }}</x-slot>

    <div class="px-4 py-4">
        <div class="container mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Configuracion de Precios de Material</h1>

            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- INFO FORMULA --}}
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <p class="text-sm text-blue-700">
                    <strong>Formula:</strong> Coste = (Precio Referencia + Incremento Diametro + Incremento Formato) x Toneladas
                </p>
                <p class="text-xs text-blue-600 mt-1">
                    El incremento de formato se busca en este orden: 1) Excepcion distribuidor+fabricante, 2) Excepcion fabricante, 3) Formato base
                </p>
            </div>

            {{-- CONFIGURACION GENERAL --}}
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Configuracion General</h2>
                <form action="{{ route('precios-material.config.update') }}" method="POST" class="flex flex-wrap items-end gap-4">
                    @csrf
                    @method('PUT')
                    <div class="flex-1 min-w-[300px]">
                        <label class="block text-gray-700 text-sm font-medium mb-1">Producto Base de Referencia</label>
                        <p class="text-xs text-gray-500 mb-2">Los incrementos se calculan respecto a este producto</p>
                        <select name="producto_base_referencia_id" class="w-full p-2 border rounded">
                            <option value="">-- Seleccionar --</option>
                            <optgroup label="Barras">
                                @foreach ($productosBase->filter(fn($p) => strtolower($p->tipo ?? '') !== 'encarretado') as $pb)
                                    <option value="{{ $pb->id }}" {{ $productoBaseReferenciaId == $pb->id ? 'selected' : '' }}>
                                        {{ $pb->diametro }}mm a {{ $pb->longitud }}m
                                    </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Encarretado">
                                @foreach ($productosBase->filter(fn($p) => strtolower($p->tipo ?? '') === 'encarretado') as $pb)
                                    <option value="{{ $pb->id }}" {{ $productoBaseReferenciaId == $pb->id ? 'selected' : '' }}>
                                        {{ $pb->diametro }}mm (encarretado)
                                    </option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Guardar</button>
                </form>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                {{-- INCREMENTOS POR DIAMETRO --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">Incrementos por Diametro</h2>
                    <p class="text-xs text-gray-500 mb-4">euros/tonelada respecto al diametro base</p>
                    <form action="{{ route('precios-material.diametros.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <table class="w-full text-sm border-collapse">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-2 py-1 text-left">Diametro</th>
                                    <th class="px-2 py-1 text-center">Incremento</th>
                                    <th class="px-2 py-1 text-center">Activo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($diametros as $i => $d)
                                    <tr class="border-t {{ $d->incremento == 0 ? 'bg-green-50' : '' }}">
                                        <td class="px-2 py-1">
                                            <input type="hidden" name="diametros[{{ $i }}][id]" value="{{ $d->id }}">
                                            {{ $d->diametro }}mm
                                            @if($d->incremento == 0) <span class="text-green-600 text-xs">(base)</span> @endif
                                        </td>
                                        <td class="px-2 py-1 text-center">
                                            <input type="number" step="0.01" name="diametros[{{ $i }}][incremento]"
                                                value="{{ $d->incremento }}" class="w-20 p-1 border rounded text-center text-sm">
                                        </td>
                                        <td class="px-2 py-1 text-center">
                                            <input type="checkbox" name="diametros[{{ $i }}][activo]" value="1" {{ $d->activo ? 'checked' : '' }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-3 text-right">
                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">Guardar</button>
                        </div>
                    </form>
                </div>

                {{-- INCREMENTOS POR FORMATO (BASE) --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">Incrementos por Formato (Base)</h2>
                    <p class="text-xs text-gray-500 mb-4">Valores por defecto cuando no hay excepcion</p>
                    <form action="{{ route('precios-material.formatos.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <table class="w-full text-sm border-collapse">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-2 py-1 text-left">Formato</th>
                                    <th class="px-2 py-1 text-center">Incremento</th>
                                    <th class="px-2 py-1 text-center">Activo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($formatos as $i => $f)
                                    <tr class="border-t {{ $f->incremento == 0 ? 'bg-green-50' : '' }}">
                                        <td class="px-2 py-1">
                                            <input type="hidden" name="formatos[{{ $i }}][id]" value="{{ $f->id }}">
                                            <span class="font-medium">{{ $f->nombre }}</span>
                                            <br><span class="text-xs text-gray-500">{{ $f->descripcion }}</span>
                                        </td>
                                        <td class="px-2 py-1 text-center">
                                            <input type="number" step="0.01" name="formatos[{{ $i }}][incremento]"
                                                value="{{ $f->incremento }}" class="w-20 p-1 border rounded text-center text-sm">
                                        </td>
                                        <td class="px-2 py-1 text-center">
                                            <input type="checkbox" name="formatos[{{ $i }}][activo]" value="1" {{ $f->activo ? 'checked' : '' }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-3 text-right">
                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- EXCEPCIONES POR FABRICANTE --}}
            <div x-data="{ open: false }" class="bg-white shadow rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Excepciones por Fabricante</h2>
                        <p class="text-xs text-gray-500">Precios especiales para un fabricante (aplica a todos los distribuidores)</p>
                    </div>
                    <button @click="open = true" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">+ Añadir</button>
                </div>

                {{-- Modal --}}
                <div x-show="open" x-transition x-cloak class="fixed inset-0 flex items-center justify-center z-50 bg-gray-800 bg-opacity-50">
                    <div class="bg-white p-6 rounded-lg w-[400px]">
                        <h3 class="text-lg font-semibold mb-4">Nueva Excepcion por Fabricante</h3>
                        <form action="{{ route('precios-material.excepciones.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="distribuidor_id" value="">
                            <div class="mb-3">
                                <label class="block text-sm font-medium mb-1">Fabricante</label>
                                <select name="fabricante_id" class="w-full p-2 border rounded" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach ($fabricantes as $fab)
                                        <option value="{{ $fab->id }}">{{ $fab->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium mb-1">Formato</label>
                                <select name="formato_codigo" class="w-full p-2 border rounded" required>
                                    @foreach ($formatos as $f)
                                        <option value="{{ $f->codigo }}">{{ $f->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium mb-1">Incremento (euros/t)</label>
                                <input type="number" step="0.01" name="incremento" class="w-full p-2 border rounded" required>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Notas</label>
                                <input type="text" name="notas" class="w-full p-2 border rounded">
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="open = false" class="px-3 py-1 bg-gray-400 text-white rounded">Cancelar</button>
                                <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Tabla --}}
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-1 text-left">Fabricante</th>
                            <th class="px-2 py-1 text-left">Formato</th>
                            <th class="px-2 py-1 text-center">Incremento</th>
                            <th class="px-2 py-1 text-left">Notas</th>
                            <th class="px-2 py-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($excepcionesFabricante as $exc)
                            <tr class="border-t">
                                <td class="px-2 py-1 font-medium">{{ $exc->fabricante->nombre ?? '-' }}</td>
                                <td class="px-2 py-1">{{ $exc->formato_codigo }}</td>
                                <td class="px-2 py-1 text-center font-medium">{{ number_format($exc->incremento, 2) }} euros</td>
                                <td class="px-2 py-1 text-xs text-gray-600">{{ $exc->notas }}</td>
                                <td class="px-2 py-1 text-right">
                                    <form action="{{ route('precios-material.excepciones.destroy', $exc) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" onclick="return confirm('Eliminar?')" class="text-red-600 hover:underline text-xs">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-3 text-center text-gray-500">Sin excepciones</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- EXCEPCIONES ESPECIFICAS (DISTRIBUIDOR + FABRICANTE) --}}
            <div x-data="{ open: false }" class="bg-white shadow rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Excepciones Especificas</h2>
                        <p class="text-xs text-gray-500">Precios para combinacion especifica de distribuidor + fabricante</p>
                    </div>
                    <button @click="open = true" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">+ Añadir</button>
                </div>

                {{-- Modal --}}
                <div x-show="open" x-transition x-cloak class="fixed inset-0 flex items-center justify-center z-50 bg-gray-800 bg-opacity-50">
                    <div class="bg-white p-6 rounded-lg w-[400px]">
                        <h3 class="text-lg font-semibold mb-4">Nueva Excepcion Especifica</h3>
                        <form action="{{ route('precios-material.excepciones.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="block text-sm font-medium mb-1">Distribuidor</label>
                                <select name="distribuidor_id" class="w-full p-2 border rounded" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach ($distribuidores as $dist)
                                        <option value="{{ $dist->id }}">{{ $dist->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium mb-1">Fabricante</label>
                                <select name="fabricante_id" class="w-full p-2 border rounded" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach ($fabricantes as $fab)
                                        <option value="{{ $fab->id }}">{{ $fab->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium mb-1">Formato</label>
                                <select name="formato_codigo" class="w-full p-2 border rounded" required>
                                    @foreach ($formatos as $f)
                                        <option value="{{ $f->codigo }}">{{ $f->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium mb-1">Incremento (euros/t)</label>
                                <input type="number" step="0.01" name="incremento" class="w-full p-2 border rounded" required>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Notas</label>
                                <input type="text" name="notas" class="w-full p-2 border rounded">
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="open = false" class="px-3 py-1 bg-gray-400 text-white rounded">Cancelar</button>
                                <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Tabla --}}
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-1 text-left">Distribuidor</th>
                            <th class="px-2 py-1 text-left">Fabricante</th>
                            <th class="px-2 py-1 text-left">Formato</th>
                            <th class="px-2 py-1 text-center">Incremento</th>
                            <th class="px-2 py-1 text-left">Notas</th>
                            <th class="px-2 py-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($excepcionesEspecificas as $exc)
                            <tr class="border-t">
                                <td class="px-2 py-1">{{ $exc->distribuidor->nombre ?? '-' }}</td>
                                <td class="px-2 py-1">{{ $exc->fabricante->nombre ?? '-' }}</td>
                                <td class="px-2 py-1">{{ $exc->formato_codigo }}</td>
                                <td class="px-2 py-1 text-center font-medium">{{ number_format($exc->incremento, 2) }} euros</td>
                                <td class="px-2 py-1 text-xs text-gray-600">{{ $exc->notas }}</td>
                                <td class="px-2 py-1 text-right">
                                    <form action="{{ route('precios-material.excepciones.destroy', $exc) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" onclick="return confirm('Eliminar?')" class="text-red-600 hover:underline text-xs">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-3 text-center text-gray-500">Sin excepciones</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
