<x-app-layout>
    <x-slot name="title">Planificación</x-slot>

    <div class="p-6">
        <div class="overflow-x-auto border rounded-lg shadow-lg bg-white relative">

            <table class="w-full border-collapse text-sm">
                <thead class="bg-gray-100 sticky top-0 z-10">
                    <tr>
                        <th class="w-48 px-4 py-2 text-left border-r font-semibold bg-gray-200 sticky left-0 z-20">
                            Obra
                        </th>
                        @foreach ($fechas as $f)
                            <th class="px-4 py-2 text-center border-r min-w-[120px] whitespace-nowrap">
                                <div class="font-semibold">{{ ucfirst($f['dia']) }}</div>
                                <div class="text-xs text-gray-500">{{ $f['fecha'] }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($obras as $obra)
                        <tr class="hover:bg-gray-50">
                            <!-- Columna fija de la obra -->
                            <td class="px-4 py-2 border-r sticky left-0 bg-white z-10 font-medium">
                                {{ $obra->nombre }}
                            </td>

                            <!-- Días scrollables -->
                            @foreach ($fechas as $f)
                                <td class="px-2 py-3 border-r text-center align-middle">
                                    {{-- Aquí puedes mostrar planillas o salidas filtradas --}}
                                    <div class="flex flex-col gap-1">
                                        @php
                                            $eventosObra = $eventos->filter(function ($e) use ($obra, $f) {
                                                return ($e['extendedProps']['obra_id'] ?? null) == $obra->id &&
                                                    \Carbon\Carbon::parse($e['start'])->isSameDay($f['fecha']);
                                            });
                                        @endphp

                                        @forelse ($eventosObra as $evento)
                                            <div
                                                class="text-xs px-2 py-1 rounded {{ match ($evento['tipo'] ?? '') {
                                                    'planilla' => 'bg-blue-100 text-blue-700',
                                                    'salida' => 'bg-green-100 text-green-700',
                                                    'resumen' => 'bg-gray-100 text-gray-700',
                                                    default => 'bg-gray-50 text-gray-500',
                                                } }}">
                                                {{ $evento['title'] ?? '—' }}
                                            </div>
                                        @empty
                                            <div class="text-gray-300 text-xs">—</div>
                                        @endforelse
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
