{{-- Input de filtro estándar --}}
@props([
    'model' => '',
    'placeholder' => '',
    'type' => 'text'
])

<th class="p-2 bg-gray-50">
    <input
        type="{{ $type }}"
        wire:model.live.debounce.300ms="{{ $model }}"
        placeholder="{{ $placeholder }}"
        class="w-full text-xs border border-gray-300 rounded px-2 py-2 text-gray-800 bg-gray-50 shadow-sm focus:border-gray-700 focus:ring-2 focus:ring-gray-600 focus:outline-none transition"
    />
</th>
