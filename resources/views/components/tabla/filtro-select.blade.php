{{-- Select de filtro estándar --}}
@props([
    'model' => '',
    'placeholder' => 'Todos'
])

<th class="p-1 border">
    <select
        wire:model.live="{{ $model }}"
        class="w-full text-xs px-2 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
    >
        <option value="">{{ $placeholder }}</option>
        {{ $slot }}
    </select>
</th>
