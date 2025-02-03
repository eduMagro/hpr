<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Salidas') }}
        </h2>
    </x-slot>

    <!-- Mensajes de Error y Éxito -->
    @if ($errors->any())
        <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li class="text-sm">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '{{ session('error') }}',
                    confirmButtonColor: '#d33'
                });
            });
        </script>
    @endif

    @if (session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'success',
                    text: '{{ session('success') }}',
                    confirmButtonColor: '#28a745'
                });
            });
        </script>
    @endif

    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Progreso de Planillas</h1>

        @foreach ($planillas as $planilla)
            <div class="bg-white shadow-md rounded-lg p-4 mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Planilla: {{ $planilla->codigo_limpio }} (Peso Total: {{ number_format($planilla->peso_total, 2) }} kg)</h2>
                <p>Cliente: {{ $planilla->cliente }} </p>
                <p>Obra: {{ $planilla->nom_obra }} </p>
                <p>Sección: {{ $planilla->seccion }} </p>
                <p>Descripción: {{ $planilla->descripcion }} </p>

                @php
                    $pesoTotalPaquetes = $planilla->paquetes->sum('peso');
                    $pesoRestante = max(0, $planilla->peso_total - $pesoTotalPaquetes);
                    $progreso = ($planilla->peso_total > 0) ? ($pesoTotalPaquetes / $planilla->peso_total) * 100 : 0;
                @endphp

                <!-- Barra de progreso -->
                <div class="w-full bg-gray-200 rounded-full h-6 mt-2">
                    <div class="bg-blue-600 text-xs font-medium text-white text-center p-1 leading-none rounded-full"
                        style="width: {{ $progreso }}%">
                        {{ number_format($progreso, 2) }}%
                    </div>
                </div>

                <p class="mt-2 text-gray-700">Peso empaquetado: <strong>{{ number_format($pesoTotalPaquetes, 2) }} kg</strong></p>
                <p class="text-gray-700">Peso restante: <strong>{{ number_format($pesoRestante, 2) }} kg</strong></p>

                <!-- Paquetes en la planilla -->
                <h3 class="mt-4 text-md font-semibold">Paquetes en esta planilla:</h3>
                @if ($planilla->paquetes->isNotEmpty())
                    <ul class="list-disc list-inside text-gray-700">
                        @foreach ($planilla->paquetes as $paquete)
                            <li>
                                <a href="{{ route('paquetes.index', ['id' => $paquete->id]) }}" class="text-blue-500 hover:underline">
                                    Paquete #{{ $paquete->id }} - Peso: {{ number_format($paquete->peso, 2) }} kg
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500">No hay paquetes creados aún.</p>
                @endif

                <!-- Elementos completos no empaquetados -->
                <h3 class="mt-4 text-md font-semibold">Elementos Completos No Empaquetados:</h3>
                @if ($planilla->elementos->isNotEmpty())
                    <ul class="list-disc list-inside text-gray-700">
                        @foreach ($planilla->elementos as $elemento)
                            <li>
                                Elemento #{{ $elemento->id }} - Peso: {{ number_format($elemento->peso, 2) }} kg
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500">No hay elementos completos sin empaquetar.</p>
                @endif

                <!-- Etiquetas completas no empaquetadas -->
                <h3 class="mt-4 text-md font-semibold">Etiquetas Completas No Empaquetadas:</h3>
                @if ($planilla->etiquetas->isNotEmpty())
                    <ul class="list-disc list-inside text-gray-700">
                        @foreach ($planilla->etiquetas as $etiqueta)
                            <li>
                                Etiqueta #{{ $etiqueta->id }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500">No hay etiquetas completas sin empaquetar.</p>
                @endif
            </div>
        @endforeach
    </div>
</x-app-layout>
