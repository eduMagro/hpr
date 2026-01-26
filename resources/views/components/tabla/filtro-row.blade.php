{{-- Fila de filtros con estilos consistentes --}}
<tr {{ $attributes->merge(['class' => 'filters-row text-center']) }}>
    {{ $slot }}
</tr>
