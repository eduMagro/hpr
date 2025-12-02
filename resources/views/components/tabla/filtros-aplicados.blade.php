@props(['filtros' => []])

@if (count($filtros))
    <div class="bg-gray-900/90 border border-gray-800 text-white text-sm px-4 py-2 rounded-lg shadow-sm mt-2 mb-4">
        <strong class="font-semibold">Filtros aplicados:</strong>
        <span>{!! implode(', ', $filtros) !!}</span>
    </div>
@endif
