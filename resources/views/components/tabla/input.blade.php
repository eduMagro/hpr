@props([
    'type' => 'text',
    'name' => null,
    'value' => '',
    'placeholder' => '',
])

<input type="{{ $type }}"
    @if ($name) name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}" @endif
    @if (!$name) {{-- Si no hay name, usar solo el value directo (para evitar errores) --}}
        value="{{ $value }}" @endif
    placeholder="{{ $placeholder }}"
    {{ $attributes->merge([
        'class' =>
            'w-full px-2 py-1 border border-gray-300 rounded text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500',
    ]) }} />
