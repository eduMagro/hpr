{{-- Select de filtro estándar --}}
@props([
    'model' => '',
    'placeholder' => 'Todos'
])

<th class="p-2 bg-white">
    <select
        wire:model.live="{{ $model }}"
        class="w-full text-xs px-3 py-2 border border-gray-300 rounded-md text-gray-800 bg-white shadow-sm focus:border-gray-700 focus:ring-2 focus:ring-gray-600 focus:outline-none transition"
    >
        <option value="">{{ $placeholder }}</option>
        {{ $slot }}
    </select>
</th>
