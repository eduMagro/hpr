<x-app-layout>
    @php
        $esOperario = auth()->user()->rol == '';
    @endphp
    <div class="py-4 lg:py-12 ">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div
                        class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 px-6 justify-items-center">
                        @php
                            $items = [
                                [
                                    'route' => 'productos.index',
                                    'label' => 'Materiales',
                                    'icon' => asset('imagenes/iconos/materiales.png'),
                                ],
                                [
                                    'route' => 'ubicaciones.index',
                                    'label' => 'Ubicaciones',
                                    'icon' => asset('imagenes/iconos/ubicaciones.png'),
                                ],
                                [
                                    'route' => 'entradas.index',
                                    'label' => 'Entradas',
                                    'icon' => asset('imagenes/iconos/entradas.png'),
                                ],
                                [
                                    'route' => 'salidas.index',
                                    'label' => 'Salidas',
                                    'icon' => asset('imagenes/iconos/salidas.png'),
                                ],
                                [
                                    'route' => 'users.index',
                                    'label' => 'Usuarios',
                                    'icon' => asset('imagenes/iconos/usuarios.png'),
                                ],
                                [
                                    'route' => 'movimientos.index',
                                    'label' => 'Movimientos',
                                    'icon' => asset('imagenes/iconos/movimientos.png'),
                                ],
                                [
                                    'route' => 'maquinas.index',
                                    'label' => 'Máquinas',
                                    'icon' => asset('imagenes/iconos/maquinas.png'),
                                ],
                                [
                                    'route' => 'planillas.index',
                                    'label' => 'Planillas',
                                    'icon' => asset('imagenes/iconos/planillas.png'),
                                ],
                                [
                                    'route' => 'planificacion.index',
                                    'label' => 'Planificación',
                                    'icon' => asset('imagenes/iconos/planificacion.png'),
                                ],
                                [
                                    'route' => 'empresas.index',
                                    'label' => 'Mi Empresa',
                                    'icon' => asset('imagenes/iconos/empresas.png'),
                                ],
                                [
                                    'route' => 'clientes.index',
                                    'label' => 'Clientes',
                                    'icon' => asset('imagenes/iconos/clientes.png'),
                                ],
                                [
                                    'route' => 'empresas-transporte.index',
                                    'label' => 'Transporte',
                                    'icon' => asset('imagenes/iconos/empresas-transporte.png'),
                                ],
                                [
                                    'route' => 'estadisticas.index',
                                    'label' => 'Estadísticas',
                                    'icon' => asset('imagenes/iconos/estadisticas.png'),
                                ],
                                [
                                    'route' => null,
                                    'label' => 'Mantenimiento',
                                    'icon' => asset('imagenes/iconos/mantenimiento.png'),
                                ],

                                [
                                    'route' => 'alertas.index',
                                    'label' => 'Alertas',
                                    'icon' => asset('imagenes/iconos/alertas.png'),
                                ],
                            ];
                        @endphp

                        @foreach ($items as $item)
                            <a href="{{ $item['route'] ? route($item['route']) : '#' }}"
                                class="w-36 h-36 bg-white rounded-2xl shadow-md flex flex-col items-center justify-center text-center hover:shadow-xl transition duration-300 ease-in-out relative {{ $esOperario && $item['route'] !== 'users.index' ? 'pointer-events-none opacity-50' : '' }}">

                                {{-- Icono principal --}}
                                <img src="{{ asset($item['icon']) }}" alt="{{ $item['label'] }}" class="w-20 h-20 mb-2">

                                {{-- Etiqueta --}}
                                <span class="text-sm font-medium text-gray-700">{{ $item['label'] }}</span>

                                {{-- Icono de notificación para alertas --}}
                                @if ($item['route'] === 'alertas.index')
                                    <img id="notificacion-alertas-icono"
                                        src="{{ asset('imagenes/iconos/notificacion.png') }}"
                                        class="absolute top-1 right-1 w-5 h-5 animate-ping hidden" alt="Nueva alerta">
                                @endif
                            </a>
                        @endforeach

                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Estilos CSS directos -->
    <style>
        .icon-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            /* Ajusta el tamaño mínimo de los iconos */
            gap: 20px;
            justify-items: center;
            padding: 20px;
        }

        .icon-card {
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .icon-card:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .icon-card img {
            max-width: 100%;
            height: auto;
            display: block;
            margin-bottom: 10px;
        }

        .icon-card span {
            font-size: 16px;
            color: #333;
            display: block;
            margin-top: 5px;
        }

        /*------------------------- ELEMENTOS DISABLED -----------------------*/
        .disabled-link {
            pointer-events: none;
            cursor: not-allowed;
            opacity: 0.5;
            /* Reduce opacidad para indicar que está deshabilitado */
            text-decoration: none;
            /* Opcional para evitar subrayado */
        }

        /*----------- EXCLAMACION ------------*/

        #notificacion-alertas-icono {
            animation: expandirContraer 1s infinite ease-in-out;
            display: none;
        }

        @keyframes expandirContraer {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.3);
            }

            100% {
                transform: scale(1);
            }
        }


        /* Responsive tweaks */
        @media (max-width: 1024px) {
            .icon-container {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }

            .icon-card {
                padding: 12px;
            }

            .icon-card span {
                font-size: 14px;
            }
        }

        @media (max-width: 768px) {
            .icon-container {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .icon-card {
                padding: 10px;
            }

            .icon-card span {
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .icon-container {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }

            .icon-card {
                padding: 8px;
            }

            .icon-card span {
                font-size: 12px;
            }
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            fetch("{{ route('alertas.sinLeer') }}")
                .then(response => response.json())
                .then(data => {
                    if (data.cantidad > 0) {
                        document.getElementById("notificacion-alertas-icono").style.display = "block";

                    }
                });
        });
    </script>

</x-app-layout>
