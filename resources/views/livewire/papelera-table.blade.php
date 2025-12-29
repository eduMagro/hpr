<div wire:key="papelera-{{ $modelKey }}">
    @if ($total > 0)
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    {{ $icono }} {{ $nombre }}
                    <span class="px-3 py-1 bg-red-100 text-red-700 text-sm rounded-full">
                        {{ $total }}
                    </span>
                </h3>
                <div class="flex items-center gap-3">
                    <div wire:loading class="text-sm text-blue-600">
                        Cargando...
                    </div>
                    <button wire:click="resetear"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center gap-1"
                        title="Restablecer vista">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                ID
                            </th>
                            @foreach ($campos as $campo)
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                    {{ ucfirst(str_replace('.', ' ', str_replace('_', ' ', $campo))) }}
                                </th>
                            @endforeach
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Eliminado
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($registros as $registro)
                            <tr wire:key="registro-{{ $registro->id }}" class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ $registro->id }}
                                </td>
                                @foreach ($campos as $campo)
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        @php
                                            $valor = $registro;
                                            foreach (explode('.', $campo) as $part) {
                                                $valor = $valor->{$part} ?? 'N/A';
                                                if ($valor === 'N/A') {
                                                    break;
                                                }
                                            }
                                        @endphp
                                        {{ $valor ?? 'N/A' }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    {{ $registro->deleted_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button"
                                        wire:click="restaurar({{ $registro->id }})"
                                        wire:confirm="¿Estás seguro de que deseas restaurar este registro?"
                                        class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition duration-150">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                            </path>
                                        </svg>
                                        Restaurar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Paginación Livewire --}}
            @if ($registros->hasPages())
                <x-tabla.paginacion-livewire :paginador="$registros" />
            @endif
        </div>
    @endif
</div>
