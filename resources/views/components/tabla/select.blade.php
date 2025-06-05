@props(['name', 'options' => [], 'selected' => null, 'empty' => 'Seleccionar...'])

<select name="{{ $name }}" id="{{ $name }}"
    {{ $attributes->merge([
        'class' =>
            'w-full px-2 py-1 border border-gray-300 rounded text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500',
    ]) }}>
    @if ($empty !== false)
        <option value="">{{ $empty }}</option>
    @endif

    @foreach ($options as $key => $label)
        <option value="{{ $key }}" {{ $key == $selected ? 'selected' : '' }}>
            {{ $label }}
        </option>
    @endforeach
</select>
