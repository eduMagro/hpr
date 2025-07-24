<x-app-layout>
    <x-slot name="title">Mi Perfil</x-slot>

    <div class="container mx-auto px-4 py-6">

        {{-- ✅ FICHA PARA TRABAJADOR --}}
        @if (auth()->user()->rol === 'operario')
            <x-ficha-operario :user="$user" :resumen="$resumen" />
        @elseif (auth()->user()->rol === 'oficina')
            <x-ficha-oficina :user="$user" :resumen="$resumen" />
        @else
            {{-- --}}
        @endif
    </div>

</x-app-layout>
