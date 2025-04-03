<x-app-layout>
    <x-slot name="title">Empresas - {{ config('app.name') }}</x-slot>

    <div class="py-6 px-4">
        <div class="flex justify-end mb-4 px-4">
            <a href="{{ route('nominas.index') }}"
                class="inline-block bg-blue-600 hover:bg-blue-900 text-white font-semibold py-2 px-4 rounded shadow transition">
                ➕ Nóminas
            </a>
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
                            <td colspan="11" class="text-center py-4 text-gray-500">No hay empresas registradas.</td>
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
                            <td class="px-4 py-2 border">{{ $registro->concepto }}</td>
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
                            <td colspan="4" class="text-center py-4 text-gray-500">No hay tramos IRPF registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Convenios por Categoría</h3>
        <div class="mt-10 bg-white shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-4 py-2 border text-center">ID</th>
                        <th class="px-4 py-2 border">Categoría</th>
                        <th class="px-4 py-2 border text-right">Salario Base</th>
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
                            <td class="px-4 py-2 border">{{ $convenio->categoria->nombre ?? 'Sin categoría' }}</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->salario_base, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->plus_asistencia, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->plus_actividad, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->plus_productividad, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->plus_absentismo, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->plus_transporte, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->prorrateo_pagasextras, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-gray-500">No hay convenios registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</x-app-layout>
