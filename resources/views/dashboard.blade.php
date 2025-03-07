<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Bienvenido, :name. Elige una tarea', ['name' => strtok(auth()->user()->name, ' ')]) }}
        </h2>

    </x-slot>

    <div class="py-4 lg:py-12 ">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="icon-container">
                        <div class="icon-card">
                            <a href="{{ route('productos.index') }}">
                                <img src="https://img.icons8.com/?size=100&id=nY1AjCB9y7SY&format=png&color=000000"
                                    alt="Materiales">
                                <span>Materiales</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="{{ route('ubicaciones.index') }}">
                                <img src="https://img.icons8.com/?size=100&id=n4PINSDv4htA&format=png&color=000000"
                                    alt="Ubicaciones">
                                <span>Ubicaciones</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="{{ route('entradas.index') }}">
                                <img src="https://img.icons8.com/?size=100&id=45Nxpks5EYHE&format=png&color=000000"
                                    alt="Entradas">
                                <span>Entradas</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="{{ route('salidas.index') }}">
                                <img src="https://img.icons8.com/?size=100&id=rGLEtwiD51Dw&format=png&color=000000"
                                    alt="Salidas">
                                <span>Salidas</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="{{ route('users.index') }}">
                                <img src="https://img.icons8.com/?size=100&id=NzllL1yxqOEc&format=png&color=000000"
                                    alt="Usuarios">
                                <span>Usuarios</span>
                            </a>
                        </div>

                        <div class="icon-card">
                            <a href="{{ route('movimientos.index') }}">
                                <img src="https://img.icons8.com/color/96/swap.png" alt="Movimientos">
                                <span>Movimientos</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="{{ route('maquinas.index') }}">
                                <img src="https://img.icons8.com/arcade/100/cnc-machine.png" alt="Máquinas" />
                                <span>Máquinas</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="{{ route('planillas.index') }}">
                                <img width="100" height="100"
                                    src="https://img.icons8.com/arcade/64/terms-and-conditions.png"
                                    alt="terms-and-conditions" />
                                <span>Planillas</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="{{ route('clientes.index') }}">
                                <img width="100" height="100"
                                    src="https://img.icons8.com/?size=100&id=HjcUJuI6Siqo&format=png&color=000000"
                                    alt="terms-and-conditions" />
                                <span>Clientes</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="{{ route('empresas-transporte.index') }}">
                                <img width="100" height="100"
                                    src="https://img.icons8.com/dusk/64/interstate-truck.png" alt="Transporte" />
                                <span>Transporte</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="{{ route('estadisticas.index') }}">
                                <img src="https://img.icons8.com/color/96/graph.png" alt="Reportes">
                                <span>Estadísticas</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="#">
                                <img src="https://img.icons8.com/color/96/settings.png" alt="Configuración">
                                <span>Mantenimiento</span>
                            </a>
                        </div>
                        <div class="icon-card relative">
                            <a href="{{ route('alertas.index') }}" class="relative">
                                <img src="https://img.icons8.com/?size=100&id=xaInJjDQEige&format=png&color=000000"
                                    alt="Alertas">
                                <img id="notificacion-alertas-icono"
                                    src="https://img.icons8.com/color/48/high-priority.png" alt="Alerta"
                                    class="absolute top-0 right-0 w-6 h-6 animate-alerta">

                                <span>Alertas</span>
                            </a>
                        </div>



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
