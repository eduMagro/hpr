<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Máquinas') }} wire:navigate
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-blue-600 text-white text-center py-4">
                    <h2 class="text-lg font-bold uppercase">Crear Máquina</h2>
                </div>
                <div class="p-6">
                    <form action="{{ route('maquinas.store') }}" method="POST" class="space-y-5">
                        @csrf

                        {{-- Código --}}
                        <div>
                            <label for="codigo" class="block text-sm font-semibold text-gray-700 uppercase mb-1">
                                Código de la Máquina *
                            </label>
                            <x-tabla.input name="codigo" placeholder="Introduce el código de la máquina" required />
                        </div>

                        {{-- Nombre --}}
                        <div>
                            <label for="nombre" class="block text-sm font-semibold text-gray-700 uppercase mb-1">
                                Nombre de la Máquina *
                            </label>
                            <x-tabla.input name="nombre" placeholder="Introduce el nombre de la máquina" required />
                        </div>

                        {{-- Tipo --}}
                        <div>
                            <label for="tipo" class="block text-sm font-semibold text-gray-700 uppercase mb-1">
                                Tipo de Máquina
                            </label>
                            <x-tabla.select name="tipo" :options="[
                                'grua' => 'Grua',
                                'cortadora_dobladora' => 'Cortadora y Dobladora',
                                'estribadora' => 'Estribadora',
                                'ensambladora' => 'Ensambladora',
                                'soldadora' => 'Soldadora',
                                'cortadora_manual' => 'Cortadora Manual',
                                'dobladora_manual' => 'Dobladora Manual',
                            ]" empty="Selecciona su función" />
                        </div>

                        {{-- Nave (Obra asignada) --}}
                        <div class="mb-4">
                            <label for="obra_id" class="block text-sm font-semibold text-gray-700 uppercase mb-1">
                                Obra asignada
                            </label>
                            <x-tabla.select name="obra_id" :options="$obras->pluck('obra', 'id')->toArray()" empty="Selecciona la Nave" />
                        </div>

                        {{-- Diámetro mínimo --}}
                        <div>
                            <label for="diametro_min" class="block text-sm font-semibold text-gray-700 uppercase mb-1">
                                Diámetro Mínimo
                            </label>
                            <x-tabla.select name="diametro_min" :options="array_combine([8, 10, 12, 16, 20, 25, 32], [8, 10, 12, 16, 20, 25, 32])"
                                empty="Selecciona un diámetro mínimo" />
                        </div>

                        {{-- Diámetro máximo --}}
                        <div>
                            <label for="diametro_max" class="block text-sm font-semibold text-gray-700 uppercase mb-1">
                                Diámetro Máximo
                            </label>
                            <x-tabla.select name="diametro_max" :options="array_combine([8, 10, 12, 16, 20, 25, 32], [8, 10, 12, 16, 20, 25, 32])"
                                empty="Selecciona un diámetro máximo" />
                        </div>

                        {{-- Peso mínimo --}}
                        <div>
                            <label for="peso_min" class="block text-sm font-semibold text-gray-700 uppercase mb-1">
                                Peso Mínimo
                            </label>
                            <x-tabla.select name="peso_min" :options="[
                                '3000' => '3000 kg',
                                '5000' => '5000 kg',
                                'barras' => 'Barras',
                            ]" empty="Selecciona un peso mínimo" />
                        </div>

                        {{-- Peso máximo --}}
                        <div>
                            <label for="peso_max" class="block text-sm font-semibold text-gray-700 uppercase mb-1">
                                Peso Máximo
                            </label>
                            <x-tabla.select name="peso_max" :options="[
                                '3000' => '3000 kg',
                                '5000' => '5000 kg',
                                'barras' => 'Barras',
                            ]" empty="Selecciona un peso máximo" />
                        </div>

                        {{-- Ancho en metros --}}
                        <div>
                            <label for="ancho_m" class="block text-sm font-semibold text-gray-700 uppercase mb-1">
                                Ancho (m)
                            </label>
                            <x-tabla.input name="ancho_m" type="number" step="0.01" placeholder="Ej: 1.20" />
                        </div>

                        {{-- Largo en metros --}}
                        <div>
                            <label for="largo_m" class="block text-sm font-semibold text-gray-700 uppercase mb-1">
                                Largo (m)
                            </label>
                            <x-tabla.input name="largo_m" type="number" step="0.01" placeholder="Ej: 3.50" />
                        </div>

                        {{-- Botón --}}
                        <div class="flex justify-center">
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded text-sm">
                                💾 Registrar Máquina
                            </button>
                        </div>
                    </form>
                </div>
                <div class="bg-gray-100 text-center text-gray-500 text-sm py-2">
                    <small>Todos los campos con * son obligatorios.</small>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
