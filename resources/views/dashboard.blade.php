<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Bienvenido. ¿Qué deseas hacer?') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="icon-container">
                        <div class="icon-card">
                            <a href="{{ route('entradas.index') }}">
                                <img src="https://img.icons8.com/?size=100&id=nY1AjCB9y7SY&format=png&color=000000" alt="Materiales">
                                <span>Materiales</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="{{ route('entradas.index') }}">
                                <img src="https://img.icons8.com/?size=100&id=n4PINSDv4htA&format=png&color=000000" alt="Ubicaciones">
                                <span>Ubicaciones</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="{{ route('entradas.index') }}">
                                <img src="https://img.icons8.com/?size=100&id=45Nxpks5EYHE&format=png&color=000000" alt="Entradas">
                                <span>Entradas</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="#">
                                <img src="https://img.icons8.com/?size=100&id=rGLEtwiD51Dw&format=png&color=000000" alt="Salidas">
                                <span>Salidas</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="#">
                                <img src="https://img.icons8.com/?size=100&id=NzllL1yxqOEc&format=png&color=000000" alt="Usuarios">
                                <span>Usuarios</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="#">
                                <img src="https://img.icons8.com/color/96/swap.png" alt="Movimientos">
                                <span>Movimientos</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="#">
                                <img src="https://img.icons8.com/?size=100&id=xaInJjDQEige&format=png&color=000000" alt="Alertas">
                                <span>Alertas</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="#">
                                <img src="https://img.icons8.com/color/96/graph.png" alt="Reportes">
                                <span>Reportes</span>
                            </a>
                        </div>
                        <div class="icon-card">
                            <a href="#">
                                <img src="https://img.icons8.com/color/96/settings.png" alt="Configuración">
                                <span>Configuración</span>
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
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); /* Ajusta el tamaño mínimo de los iconos */
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
</x-app-layout>
