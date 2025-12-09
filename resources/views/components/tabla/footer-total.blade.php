{{-- Footer con total (ej: peso total) --}}
@props([
    'colspan' => 10,
    'label' => 'Total',
    'value' => '0'
])

<tfoot>
    <tr class="bg-white border-t border-gray-200">
        <td colspan="{{ $colspan }}" class="px-6 py-3 sticky right-0 bg-white">
            <div class="flex justify-end items-center gap-4 text-sm text-gray-700">
                <span class="font-semibold">{{ $label }}:</span>
                <span class="text-base font-bold text-emerald-700">
                    {{ $value }}
                </span>
            </div>
        </td>
    </tr>
</tfoot>
