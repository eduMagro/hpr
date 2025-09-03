@props(['obras', 'obraActualId' => null])

@php
    use Illuminate\Support\Str;

    $clienteEsPacoReyes =
        $obras->isNotEmpty() &&
        Str::contains(Str::lower(optional($obras->first()->cliente)->empresa), 'hierros paco reyes');

    $colorBase = 'blue';

    $colores = [
        'bg' => "bg-$colorBase-600",
        'bgHover' => "hover:bg-$colorBase-700",
        'bgActivo' => "bg-$colorBase-800",
        'txt' => 'text-white',
        'txtBase' => "text-$colorBase-700",
        'txtHover' => "hover:text-$colorBase-900",
        'bgLite' => "bg-$colorBase-100",
        'borde' => 'border-gray-200',
        'activoTxt' => "text-$colorBase-800",
        'hoverLite' => "hover:bg-$colorBase-50",
    ];
@endphp

@if ($clienteEsPacoReyes && $obras->isNotEmpty())
    <div class="w-full" x-data="{ open: false }">
        {{-- Menú móvil --}}
        <div class="sm:hidden relative mb-4">
            <button @click="open = !open"
                class="w-1/2 {{ $colores['bg'] }} {{ $colores['bgHover'] }} {{ $colores['txt'] }} font-semibold px-4 py-2 shadow transition">
                Obras
            </button>

            <div x-show="open" x-transition @click.away="open = false"
                class="absolute z-30 mt-0 w-1/2 bg-white border {{ $colores['borde'] }} rounded-b-lg shadow-xl overflow-hidden divide-y {{ $colores['borde'] }}"
                x-cloak>
                @foreach ($obras as $obra)
                    @php
                        $active = $obra->id == $obraActualId;
                    @endphp

                    <a href="{{ request()->fullUrlWithQuery(['obra' => $obra->id]) }}"
                        class="relative block px-2 py-3 text-sm font-medium transition
        {{ $active
            ? $colores['bgLite'] . ' ' . $colores['activoTxt'] . ' font-semibold'
            : $colores['txtBase'] . ' ' . $colores['hoverLite'] . ' ' . $colores['txtHover'] }}">
                        🏗️ {{ $obra->obra }}
                    </a>
                @endforeach

            </div>
        </div>

        {{-- Menú escritorio --}}
        <div class="hidden sm:flex w-full mb-4">
            @foreach ($obras as $obra)
                @php
                    $active = $obra->id == $obraActualId;
                @endphp

                <a href="{{ request()->fullUrlWithQuery(['obra' => $obra->id]) }}"
                    class="relative flex-1 text-center px-4 py-2 font-semibold transition
        {{ $active
            ? $colores['bgActivo'] . ' ' . $colores['txt']
            : $colores['bg'] . ' ' . $colores['bgHover'] . ' ' . $colores['txt'] }}">
                    {{ $obra->obra }}
                </a>
            @endforeach


        </div>
    </div>
@endif
