{{-- Filtros para producto base (tipo, diámetro, longitud) usado en varias tablas --}}
@props([
    'modelTipo' => 'producto_tipo',
    'modelDiametro' => 'producto_diametro',
    'modelLongitud' => 'producto_longitud'
])

<th class="py-2 px-1 bg-white">
    <div class="flex gap-2 justify-start">
        <input
            type="text"
            wire:model.live.debounce.300ms="{{ $modelTipo }}"
            placeholder="T"
            class="bg-white text-gray-800 border border-gray-300 rounded-md text-[11px] text-center w-16 h-8 shadow-sm focus:border-gray-700 focus:ring-2 focus:ring-gray-600 focus:outline-none transition"
        />
        <input
            type="text"
            wire:model.live.debounce.300ms="{{ $modelDiametro }}"
            placeholder="Ø"
            class="bg-white text-gray-800 border border-gray-300 rounded-md text-[11px] text-center w-16 h-8 shadow-sm focus:border-gray-700 focus:ring-2 focus:ring-gray-600 focus:outline-none transition"
        />
        <input
            type="text"
            wire:model.live.debounce.300ms="{{ $modelLongitud }}"
            placeholder="L"
            class="bg-white text-gray-800 border border-gray-300 rounded-md text-[11px] text-center w-16 h-8 shadow-sm focus:border-gray-700 focus:ring-2 focus:ring-gray-600 focus:outline-none transition"
        />
    </div>
</th>
