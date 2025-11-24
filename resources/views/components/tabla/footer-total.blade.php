{{-- Footer con total (ej: peso total) --}}
@props([
    'colspan' => 10,
    'label' => 'Total',
    'value' => '0'
])

<tfoot>
    <tr class="bg-gradient-to-r from-blue-50 to-blue-100 border-t border-blue-300">
        <td colspan="{{ $colspan }}" class="px-6 py-3">
            <div class="flex justify-end items-center gap-4 text-sm text-gray-700">
                <span class="font-semibold">{{ $label }}:</span>
                <span class="text-base font-bold text-blue-800">
                    {{ $value }}
                </span>
            </div>
        </td>
    </tr>
</tfoot>
