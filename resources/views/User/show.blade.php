<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detalles Usuario') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">


        <!-- GRID PARA TARJETAS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse ($registrosUsuarios as $user)
                <div class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                    <p class="text-gray-500 text-sm">ID Movimiento: 
                        {{ $user->movimientos->id ?? 'Sin movimientos' }}
                    </p>
                    <hr class="my-4">
                    <h2 class="font-semibold text-lg mb-2">ID Entrada: 
                        {{ $user->entradas->id ?? 'Sin entradas' }}
                    </h2>
                
                    
                    
                </div>
            @empty
                <div class="col-span-3 text-center py-4 text-gray-600">No hay usuarios disponibles.</div>
            @endforelse
        </div>
        

        <!-- PAGINACIÓN -->
        @if ($registrosUsuarios instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-6">
                {{ $registrosUsuarios->appends(request()->except('page'))->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
