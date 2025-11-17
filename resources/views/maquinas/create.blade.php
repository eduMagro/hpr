<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Máquinas') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3">
                    <h2 class="text-lg font-bold">Crear Nueva Máquina</h2>
                </div>
                <div class="p-6">
                    <form action="{{ route('maquinas.store') }}" method="POST">
                        @csrf

                        {{-- Información Básica --}}
                        <div class="mb-6">
                            <h3 class="text-sm font-bold text-gray-700 uppercase mb-3 pb-2 border-b border-gray-200">
                                Información Básica
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="codigo" class="block text-xs font-semibold text-gray-600 mb-1">
                                        Código *
                                    </label>
                                    <x-tabla.input name="codigo" placeholder="Código" required />
                                </div>
                                <div>
                                    <label for="nombre" class="block text-xs font-semibold text-gray-600 mb-1">
                                        Nombre *
                                    </label>
                                    <x-tabla.input name="nombre" placeholder="Nombre de la máquina" required />
                                </div>
                                <div>
                                    <label for="obra_id" class="block text-xs font-semibold text-gray-600 mb-1">
                                        Obra asignada
                                    </label>
                                    <x-tabla.select name="obra_id" :options="$obras->pluck('obra', 'id')->toArray()" empty="Seleccionar obra" />
                                </div>
                            </div>
                        </div>

                        {{-- Configuración de Tipo --}}
                        <div class="mb-6">
                            <h3 class="text-sm font-bold text-gray-700 uppercase mb-3 pb-2 border-b border-gray-200">
                                Tipo y Material
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="tipo" class="block text-xs font-semibold text-gray-600 mb-1">
                                        Tipo de Máquina
                                    </label>
                                    <x-tabla.select name="tipo" :options="[
                                        'grua' => 'Grúa',
                                        'cortadora_dobladora' => 'Cortadora y Dobladora',
                                        'estribadora' => 'Estribadora',
                                        'ensambladora' => 'Ensambladora',
                                        'soldadora' => 'Soldadora',
                                        'cortadora_manual' => 'Cortadora Manual',
                                        'dobladora_manual' => 'Dobladora Manual',
                                    ]" empty="Seleccionar función" />
                                </div>
                                <div>
                                    <label for="tipo_material" class="block text-xs font-semibold text-gray-600 mb-1">
                                        Tipo de Material
                                    </label>
                                    <x-tabla.select name="tipo_material" :options="[
                                        'barra' => 'Barra',
                                        'encarretado' => 'Encarretado',
                                    ]" empty="Seleccionar tipo" />
                                </div>
                            </div>
                        </div>

                        {{-- Especificaciones Técnicas --}}
                        <div class="mb-6">
                            <h3 class="text-sm font-bold text-gray-700 uppercase mb-3 pb-2 border-b border-gray-200">
                                Especificaciones Técnicas
                            </h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <label for="diametro_min" class="block text-xs font-semibold text-gray-600 mb-1">
                                        Ø Mínimo
                                    </label>
                                    <x-tabla.select name="diametro_min" :options="array_combine([8, 10, 12, 16, 20, 25, 32], [8, 10, 12, 16, 20, 25, 32])"
                                        empty="Mín." />
                                </div>
                                <div>
                                    <label for="diametro_max" class="block text-xs font-semibold text-gray-600 mb-1">
                                        Ø Máximo
                                    </label>
                                    <x-tabla.select name="diametro_max" :options="array_combine([8, 10, 12, 16, 20, 25, 32], [8, 10, 12, 16, 20, 25, 32])"
                                        empty="Máx." />
                                </div>
                                <div>
                                    <label for="peso_min" class="block text-xs font-semibold text-gray-600 mb-1">
                                        Peso Mínimo
                                    </label>
                                    <x-tabla.select name="peso_min" :options="[
                                        '3000' => '3000 kg',
                                        '5000' => '5000 kg',
                                        'barras' => 'Barras',
                                    ]" empty="Mín." />
                                </div>
                                <div>
                                    <label for="peso_max" class="block text-xs font-semibold text-gray-600 mb-1">
                                        Peso Máximo
                                    </label>
                                    <x-tabla.select name="peso_max" :options="[
                                        '3000' => '3000 kg',
                                        '5000' => '5000 kg',
                                        'barras' => 'Barras',
                                    ]" empty="Máx." />
                                </div>
                            </div>
                        </div>

                        {{-- Dimensiones --}}
                        <div class="mb-6">
                            <h3 class="text-sm font-bold text-gray-700 uppercase mb-3 pb-2 border-b border-gray-200">
                                Dimensiones
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="ancho_m" class="block text-xs font-semibold text-gray-600 mb-1">
                                        Ancho (metros)
                                    </label>
                                    <x-tabla.input name="ancho_m" type="number" step="0.01" placeholder="Ej: 1.20" />
                                </div>
                                <div>
                                    <label for="largo_m" class="block text-xs font-semibold text-gray-600 mb-1">
                                        Largo (metros)
                                    </label>
                                    <x-tabla.input name="largo_m" type="number" step="0.01" placeholder="Ej: 3.50" />
                                </div>
                            </div>
                        </div>

                        {{-- Botones --}}
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                            <a href="{{ route('maquinas.index') }}"
                                class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded text-sm transition">
                                Cancelar
                            </a>
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded text-sm transition shadow-sm">
                                💾 Crear Máquina
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
