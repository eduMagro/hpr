@props(['campo', 'sortActual', 'orderActual', 'texto', 'padding' => 'px-3 py-3'])

<th class="{{ $padding }} cursor-pointer hover:bg-white/10 transition-colors select-none text-left text-[11px] font-semibold uppercase tracking-wide text-white/90"
    wire:click="sortBy('{{ $campo }}')">
    <div class="flex items-center justify-start gap-2">
        <span>{{ $texto }}</span>
        <span class="inline-flex flex-col text-xs leading-none">
            @if($sortActual === $campo)
                @if($orderActual === 'asc')
                    <span class="text-white">▲</span>
                    <span class="text-gray-400 opacity-80">▼</span>
                @else
                    <span class="text-gray-400 opacity-80">▲</span>
                    <span class="text-white">▼</span>
                @endif
            @else
                <span class="text-gray-400 opacity-70">▲</span>
                <span class="text-gray-400 opacity-70">▼</span>
            @endif
        </span>
    </div>
</th>
