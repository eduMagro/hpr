{{-- Filtros para producto base (tipo, diámetro, longitud) usado en varias tablas --}}
@props([
    'modelTipo' => 'producto_tipo',
    'modelDiametro' => 'producto_diametro',
    'modelLongitud' => 'producto_longitud'
])

<th class="py-1 px-0 border">
    <div class="flex gap-2 justify-center">
        <input
            type="text"
            wire:model.live.debounce.300ms="{{ $modelTipo }}"
            placeholder="T"
            class="bg-white text-blue-900 border border-gray-300 rounded text-[10px] text-center w-14 h-6 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
        />
        <input
            type="text"
            wire:model.live.debounce.300ms="{{ $modelDiametro }}"
            placeholder="Ø"
            class="bg-white text-blue-900 border border-gray-300 rounded text-[10px] text-center w-14 h-6 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
        />
        <input
            type="text"
            wire:model.live.debounce.300ms="{{ $modelLongitud }}"
            placeholder="L"
            class="bg-white text-blue-900 border border-gray-300 rounded text-[10px] text-center w-14 h-6 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
        />
    </div>
</th>
