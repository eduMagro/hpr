<x-app-layout>

    <div class="py-4 lg:py-12 ">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div
                        class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 px-6 justify-items-center">

                        @foreach ($items as $item)
                            @php
                                // Filtrado por permisos del operario
                                if ($esOperario && !in_array($item['route'], $permitidosOperario)) {
                                    continue;
                                }

                                // Filtrado por departamentos si es oficina
                                if ($esOficina && !array_intersect($departamentosUsuario, $item['departamentos'])) {
                                    continue;
                                }
                            @endphp

                            <a href="{{ $item['route'] ? route($item['route']) : '#' }}"
                                class="w-32 h-32 bg-white rounded-2xl shadow-md flex flex-col items-center justify-center text-center hover:shadow-xl transition duration-300 ease-in-out relative">

                                <img src="{{ $item['icon'] }}" alt="{{ $item['label'] }}" class="w-20 h-20 mb-2">
                                <span class="text-sm font-medium text-gray-700">{{ $item['label'] }}</span>

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
        <div id="modal-politicas"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-800 bg-opacity-50 hidden">
            <div class="bg-white p-6 rounded-lg w-full max-w-3xl shadow-xl">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Términos y condiciones</h2>

                <!-- 🟦 Área scrolleable SOLO para el contenido legal -->
                <div class="space-y-6 max-h-64 overflow-y-auto border border-gray-200 rounded-md p-4 bg-gray-50">
                    <!-- Política de Privacidad -->
                    <div>
                        <p class="text-sm text-gray-700 leading-relaxed space-y-4">
                            <strong>🛡️ Política de Privacidad</strong><br><br>

                            <strong>1. Responsable del Tratamiento</strong><br>
                            En cumplimiento del Reglamento (UE) 2016/679 (RGPD) y la Ley Orgánica 3/2018 (LOPDGDD), se
                            informa que los datos personales facilitados a través de esta aplicación serán tratados
                            por:<br><br>
                            Responsable: Hierros Paco Reyes S.L.<br>
                            CIF: B90467390<br>
                            Dirección: [Indicar dirección postal]<br>
                            Correo electrónico de contacto: eduardo.magro@pacoreyes.com<br><br>

                            <strong>2. Finalidad del Tratamiento</strong><br>
                            Los datos personales de los trabajadores se tratarán con las siguientes finalidades:<br>
                            - Gestión de la relación laboral y administrativa.<br>
                            - Gestión de turnos, fichajes, vacaciones, incidencias y horas trabajadas.<br>
                            - Generación, cálculo y control de nóminas.<br>
                            - Asignación de tareas y seguimiento de la producción.<br>
                            - Control de acceso y presencia en el entorno de trabajo.<br>
                            - Envío de notificaciones internas o recordatorios mediante sistemas automatizados (previo
                            consentimiento).<br>
                            - Registro puntual de la ubicación geográfica del trabajador exclusivamente en el momento
                            del fichaje, con el fin de verificar que se realiza desde el centro de trabajo o zonas
                            autorizadas.<br><br>

                            <strong>3. Legitimación del Tratamiento</strong><br>
                            El tratamiento de sus datos personales se basa en:<br>
                            - La ejecución del contrato laboral entre la empresa y el trabajador.<br>
                            - El cumplimiento de obligaciones legales en materia laboral, fiscal y de Seguridad
                            Social.<br>
                            - El consentimiento expreso del trabajador para funcionalidades opcionales, como el envío de
                            recordatorios o comunicaciones internas por medios electrónicos.<br>
                            - El interés legítimo de la empresa en garantizar que los fichajes se realicen correctamente
                            desde ubicaciones autorizadas, utilizando geolocalización puntual con fines de control de
                            asistencia.<br><br>

                            <strong>4. Categorías de Datos Tratados</strong><br>
                            Los datos personales tratados a través de esta aplicación incluyen:<br>
                            - Nombre y apellidos<br>
                            - DNI/NIE<br>
                            - Correo electrónico personal y corporativo<br>
                            - Categoría profesional y departamento<br>
                            - Datos relacionados con su jornada, turnos, presencia, incidencias, vacaciones, nóminas y
                            productividad<br>
                            - Ubicación geográfica del dispositivo, únicamente en el momento del fichaje de entrada o
                            salida<br><br>

                            <strong>5. Conservación de los Datos</strong><br>
                            Los datos serán conservados mientras se mantenga la relación laboral con el trabajador y,
                            una vez finalizada, durante un período de hasta cinco (5) años, salvo que exista una
                            obligación legal que exija su conservación durante un plazo superior.<br><br>

                            <strong>6. Destinatarios y Encargados del Tratamiento</strong><br>
                            Los datos no se cederán a terceros, salvo obligación legal. No obstante, podrán ser tratados
                            por proveedores de servicios externos que actúan como encargados del tratamiento, tales
                            como:<br>
                            - Proveedores de hosting y servidores ubicados en el Espacio Económico Europeo (EEE)<br>
                            - Proveedores de servicios tecnológicos para el funcionamiento de la aplicación (CDNs,
                            fuentes web, etc.)<br>
                            - Herramientas de análisis de uso (como Google Analytics), solo en caso de haber sido
                            aceptadas expresamente por el usuario<br><br>

                            <strong>7. Derechos del Usuario</strong><br>
                            El trabajador podrá ejercer los siguientes derechos en cualquier momento:<br>
                            - Acceder a sus datos personales<br>
                            - Solicitar la rectificación de los datos inexactos<br>
                            - Solicitar la supresión de sus datos<br>
                            - Oponerse al tratamiento<br>
                            - Solicitar la limitación del tratamiento<br>
                            - Solicitar la portabilidad de los datos<br><br>
                            Para ejercer estos derechos deberá enviar una solicitud por escrito a rrhh@pacoreyes.com,
                            adjuntando copia de su documento identificativo.<br><br>

                            <strong>8. Seguridad de los Datos</strong><br>
                            La empresa ha adoptado todas las medidas técnicas y organizativas necesarias para garantizar
                            la integridad, confidencialidad y disponibilidad de los datos personales almacenados, en
                            cumplimiento del artículo 32 del RGPD.
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
