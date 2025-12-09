{{-- Estado vacío cuando no hay registros --}}
@props([
    'colspan' => 10,
    'mensaje' => 'No hay registros disponibles'
])

<tr>
    <td colspan="{{ $colspan }}" class="text-center py-5 text-gray-500 bg-gray-50">
        {{ $mensaje }}
    </td>
</tr>
