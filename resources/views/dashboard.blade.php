<x-app-layout>
    @php
        $esOperario = auth()->user()->rol == 'operario';
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
                                    'label' => 'Planificación Portes',
                                    'icon' => asset('imagenes/iconos/planificacion.png'),
                                ],
                                [
                                    'route' => 'produccion.trabajadores', // Asegúrate de que esta ruta exista
                                    'label' => 'Planificación Trabajadores',
                                    'icon' => asset('imagenes/iconos/planificacion-trabajadores.png'), // Debes tener esta imagen
                                ],
                                [
                                    'route' => 'produccion.maquinas', // Asegúrate de que esta ruta exista
                                    'label' => 'Planificación Máquinas',
                                    'icon' => asset('imagenes/iconos/planificacion-trabajadores.png'), // Debes tener esta imagen
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
                            @php
                                $permitidosOperario = [
                                    'maquinas.index',
                                    'users.index',
                                    'entradas.index',
                                    'salidas.index',
                                    'alertas.index',
                                ];

                                // Si es operario y la ruta no está permitida, saltamos este ítem
                                if ($esOperario && !in_array($item['route'], $permitidosOperario)) {
                                    continue;
                                }
                            @endphp
                            <a href="{{ $item['route'] ? route($item['route']) : '#' }}"
                                class="w-32 h-32 bg-white rounded-2xl shadow-md flex flex-col items-center justify-center text-center hover:shadow-xl transition duration-300 ease-in-out relative">

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

        <!-- Modal Aceptación de Políticas -->
        <!-- Modal Aceptación de Políticas -->
        <div id="modal-politicas"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-800 bg-opacity-50 hidden">
            <div class="bg-white p-6 rounded-lg w-full max-w-3xl shadow-xl">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Términos y condiciones</h2>

                <!-- 🟦 Área scrolleable SOLO para el contenido legal -->
                <div class="space-y-6 max-h-64 overflow-y-auto border border-gray-200 rounded-md p-4 bg-gray-50">
                    <!-- Política de Privacidad -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-700 mb-2">Política de Privacidad</h3>
                        <p class="text-sm text-gray-700 leading-relaxed">
                            Hierros Paco Reyes S.L., con CIF B90467390, actúa como responsable del tratamiento de los
                            datos personales
                            recabados a través de esta aplicación. Los datos serán tratados con las siguientes
                            finalidades: gestión laboral,
                            generación de nóminas, control de fichajes y turnos, y comunicaciones internas.<br><br>
                            La base legal es la ejecución del contrato laboral y el cumplimiento de obligaciones
                            legales.
                            Los datos se almacenan en servidores dentro del Espacio Económico Europeo y se conservarán
                            durante la relación
                            laboral y hasta 5 años después. Puedes ejercer tus derechos enviando un correo a
                            <strong>rrhh@pacoreyes.com</strong>.
                        </p>
                    </div>

                    <!-- Política de Cookies -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-700 mb-2">Política de Cookies</h3>
                        <p class="text-sm text-gray-700 leading-relaxed">
                            Esta aplicación utiliza cookies técnicas necesarias para el funcionamiento del sistema, así
                            como cookies de personalización para guardar tus preferencias.<br><br>
                            También puede utilizar cookies de análisis si das tu consentimiento, con el fin de mejorar
                            el rendimiento de la plataforma. Las cookies no recogen datos sensibles y puedes configurar
                            su uso desde los ajustes de tu navegador.
                        </p>
                    </div>
                </div>

                <!-- Checkboxes -->
                <form method="POST" action="{{ route('aceptar.politicas') }}" class="space-y-4 mt-6">
                    @csrf

                    <div class="flex items-start space-x-2">
                        <input type="checkbox" id="acepta_privacidad" name="acepta_privacidad" required class="mt-1">
                        <label for="acepta_privacidad" class="text-sm text-gray-700">He leído y acepto la Política de
                            Privacidad</label>
                    </div>

                    <div class="flex items-start space-x-2">
                        <input type="checkbox" id="acepta_cookies" name="acepta_cookies" required class="mt-1">
                        <label for="acepta_cookies" class="text-sm text-gray-700">He leído y acepto la Política de
                            Cookies</label>
                    </div>

                    <input type="hidden" name="ip_usuario" value="{{ request()->ip() }}">
                    <input type="hidden" name="user_agent" value="{{ request()->userAgent() }}">

                    <div class="pt-4 text-right">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            Aceptar y continuar
                        </button>
                    </div>
                </form>
            </div>
        </div>


    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Puedes usar esta condición desde el backend con una variable tipo Blade
            const debeAceptarPoliticas = {{ auth()->user()->acepta_politica_privacidad ? 'false' : 'true' }};

            if (debeAceptarPoliticas) {
                const modal = document.getElementById('modal-politicas');
                modal.classList.remove('hidden');
            }
        });
    </script>

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
    {{-- <script>
        document.addEventListener("DOMContentLoaded", function() {
            fetch("{{ route('alertas.sinLeer') }}")
                .then(response => response.json())
                .then(data => {
                    if (data.cantidad > 0) {
                        document.getElementById("notificacion-alertas-icono").style.display = "block";

                    }
                });
        });
    </script> --}}

</x-app-layout>
