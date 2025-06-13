@php
    $rutaActual = request()->route()->getName();
@endphp

<div class="w-full" x-data="{ open: false }">
    <!-- Menú móvil -->
    <div class="sm:hidden relative">
        <button @click="open = !open"
            class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 shadow transition">
            Menú
        </button>

        <div x-show="open" x-transition @click.away="open = false"
            class="absolute z-30 mt-0 w-1/2 bg-white border border-gray-200 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200"
            x-cloak>

            <a href="{{ route('productos.index') }}"
                class="block px-2 py-3 transition text-sm font-medium 
                {{ $rutaActual === 'productos.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                🧱 Productos
            </a>

            <a href="{{ route('movimientos.index') }}"
                class="block px-2 py-3 transition text-sm font-medium 
                {{ $rutaActual === 'movimientos.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                🔄 Movimientos
            </a>

            <a href="{{ route('entradas.index') }}"
                class="block px-2 py-3 transition text-sm font-medium 
                {{ $rutaActual === 'entradas.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                ⬅️ Entradas
            </a>

            <a href="{{ route('salidas.index') }}"
                class="block px-2 py-3 transition text-sm font-medium 
                {{ $rutaActual === 'salidas.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                ➡️ Salidas
            </a>
        </div>
    </div>

    <!-- Menú escritorio -->
    <div class="hidden sm:flex sm:mt-0 w-full">
        <a href="{{ route('productos.index') }}"
            class="flex-1 text-center px-4 py-2 rounded-none first:rounded-l-lg transition font-semibold
            {{ $rutaActual === 'productos.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
            🧱 Productos
        </a>

        <a href="{{ route('movimientos.index') }}"
            class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
            {{ $rutaActual === 'movimientos.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
            🔄 Movimientos
        </a>

        <a href="{{ route('entradas.index') }}"
            class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
            {{ $rutaActual === 'entradas.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
            ⬅️ Entradas
        </a>

        <a href="{{ route('salidas.index') }}"
            class="flex-1 text-center px-4 py-2 rounded-none last:rounded-r-lg transition font-semibold
            {{ $rutaActual === 'salidas.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
            ➡️ Salidas
        </a>
    </div>
</div>
