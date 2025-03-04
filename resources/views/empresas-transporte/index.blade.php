<x-app-layout>
    <x-slot name="title">Crear Salidas - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('salidas.index') }}" class="text-blue-600">
                {{ __('Salidas') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Crear Nuevas Salidas de Camiones') }}
        </h2>
    </x-slot>

    <div class="container mx-auto p-6">
        <h1>Empresas de Transporte</h1>

        @foreach ($empresasTransporte as $empresa)
            <div class="empresa">
                <h2>{{ $empresa->nombre }}</h2>
                <p><strong>Teléfono:</strong> {{ $empresa->telefono }}</p>
                <p><strong>Email:</strong> {{ $empresa->email }}</p>

                <h3>Camiones</h3>
                <ul>
                    @foreach ($empresa->camiones as $camion)
                        <li>
                            <strong>Matrícula:</strong> {{ $camion->matricula }} <br>
                            <strong>Modelo:</strong> {{ $camion->modelo }} <br>
                            <strong>Año:</strong> {{ $camion->año }} <br>
                            <strong>Capacidad:</strong> {{ $camion->capacidad }} <br>
                            <strong>Estado:</strong> {{ $camion->estado }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</x-app-layout>
