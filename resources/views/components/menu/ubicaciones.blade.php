@props(['obras', 'obraActualId' => null])

@php
    // Función que devuelve la ruta hacia el controlador index con el ID de la obra
    function rutaNave($obra)
    {
        return route('ubicaciones.index', ['obra' => $obra->id]);
    }
@endphp

@if ($obras->isNotEmpty())
    <div class="max-w-7xl mx-auto mb-4" x-data="{ open: false }">

        {{-- ===== Menú móvil ===== --}}
        <div class="sm:hidden relative">
            <button @click="open = !open"
                class="w-full px-4 py-2 rounded-lg bg-gradient-to-tr from-gray-900 to-gray-700 hover:from-gray-800 hover:to-gray-900 text-white font-semibold shadow hover:bg-gray-800 transition">
                Naves
            </button>

            <div x-show="open" x-transition @click.away="open = false"
                class="absolute z-30 mt-0 w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200 dark:divide-gray-700"
                x-cloak>
                @foreach ($obras as $obra)
                    @php
                        $active = $obra->id == $obraActualId;
                        $ruta = rutaNave($obra);
                    @endphp
                    <a href="{{ $ruta }}" wire:navigate
                        class="block px-3 py-3 text-sm font-medium transition
                            {{ $active
                                ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white'
                                : 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800/70' }}">
                        🏭 {{ $obra->obra }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ===== Menú escritorio ===== --}}
        <div class="hidden sm:flex w-full gap-3">
            @foreach ($obras as $obra)
                @php
                    $active = $obra->id == $obraActualId;
                    $ruta = rutaNave($obra);
                @endphp
                <a href="{{ $ruta }}" wire:navigate
                    class="flex-1 text-center px-4 py-2 font-semibold rounded-lg border transition-colors duration-200 ease-out
                    {{ $active
                        ? 'bg-gray-900 text-white border-gray-900 shadow'
                        : 'bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-gray-400 dark:hover:border-gray-600' }}">
                    {{ $obra->obra }}
                </a>
            @endforeach
        </div>
    </div>
@endif
