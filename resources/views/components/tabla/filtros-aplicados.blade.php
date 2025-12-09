@props(['filtros' => []])

@if (count($filtros))
    <div class="bg-blue-500/10 border border-blue-500 text-blue-600 text-sm px-4 py-2 rounded-sm shadow-sm mb-2">
        <strong class="font-semibold">Filtros aplicados:</strong>
        <span>{!! implode(', ', $filtros) !!}</span>
    </div>
@endif
