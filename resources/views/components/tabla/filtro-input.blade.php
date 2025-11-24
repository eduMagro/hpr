{{-- Input de filtro estándar --}}
@props([
    'model' => '',
    'placeholder' => '',
    'type' => 'text'
])

<th class="p-1 border">
    <input
        type="{{ $type }}"
        wire:model.live.debounce.300ms="{{ $model }}"
        placeholder="{{ $placeholder }}"
        class="w-full text-xs px-2 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
    />
</th>
