{{-- Estado vacío cuando no hay registros --}}
@props([
    'colspan' => 10,
    'mensaje' => 'No hay registros disponibles'
])

<tr>
    <td colspan="{{ $colspan }}" class="text-center py-4 text-gray-500">
        {{ $mensaje }}
    </td>
</tr>
