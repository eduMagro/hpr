<x-app-layout>
    <x-slot name="title">Empresas - {{ config('app.name') }}</x-slot>
    <x-clave.modal-clave seccion="nominas" />
    <div class="py-6 px-4">

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <!-- Botones de navegación y título alineados a la izquierda -->
            <div class="flex flex-col gap-4 mb-6">
                <div class="flex gap-3 flex-wrap">
                    <a href="{{ route('nominas.index') }}"
                        class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow transition">
                        ➕ Nóminas
                    </a>
                    <a href="{{ route('nomina.simulacion') }}"
                        class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded shadow transition">
                        🧮 Simulación Nóminas
                    </a>
                </div>

                <h1 class="text-2xl font-bold text-gray-800">
                    📥 Importar Nóminas
                </h1>
            </div>

            <!-- Formulario -->
            <form action="{{ route('nominas.dividir') }}" method="POST" enctype="multipart/form-data"
                class="space-y-4">
                @csrf

                <div>
                    <label for="archivo" class="block text-sm font-medium text-gray-700 mb-1">
                        Selecciona el PDF con las nóminas
                    </label>
                    <input type="file" name="archivo" id="archivo" accept=".pdf"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    @error('archivo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">
                        🚀 Importar y dividir
                    </button>
                </div>
            </form>
        </div>

        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Listado Empresas</h3>
        <div class="bg-white shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-4 py-2 border">ID</th>
                        <th class="px-4 py-2 border">Nombre</th>
                        <th class="px-4 py-2 border">Dirección</th>
                        <th class="px-4 py-2 border">Localidad</th>
                        <th class="px-4 py-2 border">Provincia</th>
                        <th class="px-4 py-2 border">C.P.</th>
                        <th class="px-4 py-2 border">Teléfono</th>
                        <th class="px-4 py-2 border">Email</th>
                        <th class="px-4 py-2 border">NIF</th>
                        <th class="px-4 py-2 border">Nº S.S.</th>
                        <th class="px-4 py-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    @forelse ($empresas as $empresa)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border text-center">{{ $empresa->id }}</td>
                            <td class="px-4 py-2 border">{{ $empresa->nombre }}</td>
                            <td class="px-4 py-2 border">{{ $empresa->direccion }}</td>
                            <td class="px-4 py-2 border">{{ $empresa->localidad }}</td>
                            <td class="px-4 py-2 border">{{ $empresa->provincia }}</td>
                            <td class="px-4 py-2 border text-center">{{ $empresa->codigo_postal }}</td>
                            <td class="px-4 py-2 border text-center">{{ $empresa->telefono }}</td>
                            <td class="px-4 py-2 border">{{ $empresa->email }}</td>
                            <td class="px-4 py-2 border text-center">{{ $empresa->nif }}</td>
                            <td class="px-4 py-2 border text-center">{{ $empresa->numero_ss }}</td>
                            <td class="px-4 py-2 border text-center">
                                <a href="{{ route('empresas.show', $empresa->id) }}"
                                    class="text-blue-600 hover:underline">Ver</a>
                                <span class="mx-1">|</span>
                                <a href="{{ route('empresas.edit', $empresa->id) }}"
                                    class="text-green-600 hover:underline">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-4 text-gray-500">No hay empresas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Porcentajes Seguridad Social</h3>
        <div class="bg-white shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-4 py-2 border">ID</th>
                        <th class="px-4 py-2 border">Concepto</th>
                        <th class="px-4 py-2 border">Porcentaje (%)</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    @forelse ($porcentajes_ss as $registro)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border text-center">{{ $registro->id }}</td>
                            <td class="px-4 py-2 border">{{ $registro->tipo_aportacion }}</td>
                            <td class="px-4 py-2 border text-center">
                                {{ number_format($registro->porcentaje, 2, ',', '.') }} %</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-gray-500">No hay datos de porcentajes
                                disponibles.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>


        </div>
        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Tramos IRPF</h3>
        <div class="bg-white shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-4 py-2 border text-center">ID</th>
                        <th class="px-4 py-2 border text-center">Desde (€)</th>
                        <th class="px-4 py-2 border text-center">Hasta (€)</th>
                        <th class="px-4 py-2 border text-center">Porcentaje (%)</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    @forelse ($tramos as $tramo)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border text-center">{{ $tramo->id }}</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($tramo->tramo_inicial, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 border text-right">
                                {{ $tramo->tramo_final !== null ? number_format($tramo->tramo_final, 2, ',', '.') : 'Sin límite' }}
                            </td>
                            <td class="px-4 py-2 border text-center">
                                {{ number_format($tramo->porcentaje, 2, ',', '.') }} %
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-500">No hay tramos IRPF
                                registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Convenios por Categoría</h3>
        <div class="bg-white shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-4 py-2 border text-center">ID</th>
                        <th class="px-4 py-2 border">Categoría</th>
                        <th class="px-4 py-2 border text-right">Salario Base</th>
                        <th class="px-4 py-2 border text-right">Liquido Minimo Pactado</th>
                        <th class="px-4 py-2 border text-right">Plus Asistencia</th>
                        <th class="px-4 py-2 border text-right">Plus Actividad</th>
                        <th class="px-4 py-2 border text-right">Plus Productividad</th>
                        <th class="px-4 py-2 border text-right">Plus Absentismo</th>
                        <th class="px-4 py-2 border text-right">Plus Transporte</th>
                        <th class="px-4 py-2 border text-right">Prorrateo Extras</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    @forelse ($convenio as $convenio)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border text-center">{{ $convenio->id }}</td>
                            <td class="px-4 py-2 border">{{ $convenio->categoria->nombre ?? 'Sin categoría' }}
                            </td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->salario_base, 2, ',', '.') }} €</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->liquido_minimo_pactado, 2, ',', '.') }} €</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->plus_asistencia, 2, ',', '.') }} €</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->plus_actividad, 2, ',', '.') }} €</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->plus_productividad, 2, ',', '.') }} €</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->plus_absentismo, 2, ',', '.') }} €</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->plus_transporte, 2, ',', '.') }} €</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->prorrateo_pagasextras, 2, ',', '.') }} €</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-gray-500">No hay convenios
                                registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</x-app-layout>
