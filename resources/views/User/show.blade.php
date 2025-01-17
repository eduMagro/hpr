<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detalles Usuario') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <!-- Detalles del Usuario -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="font-semibold text-xl">ID Usuario: {{ $user->id }}</h3>
            <p class="text-gray-500 text-sm">Nombre: {{ $user->name }}</p>
            <p class="text-gray-500 text-sm">Correo: {{ $user->email }}</p>
			 <p class="text-gray-500 text-sm">Categoría: {{ $user->role }}</p>
        </div>

        <!-- GRID PARA MOVIMIENTOS Y ENTRADAS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mt-6">
            <!-- Movimientos -->
            @forelse ($user->movimientos as $movimiento)
                <div class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                    <p class="text-gray-500 text-sm">ID Movimiento: {{ $movimiento->id }}</p>
                </div>
            @empty
                <div class="col-span-3 text-center py-4 text-gray-600">No hay movimientos disponibles.</div>
            @endforelse

            <!-- Entradas -->
            @forelse ($user->entradas as $entrada)
                <div class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                    <p class="text-gray-500 text-sm">ID Entrada: {{ $entrada->id }}</p>
                </div>
            @empty
                <div class="col-span-3 text-center py-4 text-gray-600">No hay entradas disponibles.</div>
            @endforelse

        </div>
        

        <!-- PAGINACIÓN -->

        @if ($user instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-6">
                {{ $user->appends(request()->except('page'))->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
