<x-app-layout>
    <div class="py-4 lg:py-12 ">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div
                        class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 px-6 justify-items-center">

                        @foreach ($items as $item)
                            <a href="{{ $item['route'] ? route($item['route']) : '#' }}" wire:navigate
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

            @if(Auth::user()->esOficina() || Auth::user()->esAdminDepartamento())
                <!-- Asistente Virtual Ferrallin -->
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 border-b border-gray-800 bg-gray-900">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-gray-800 flex items-center justify-center shadow-lg overflow-hidden">
                                    <img src="{{ asset('imagenes/iconos/asistente-sin-fondo.png') }}" alt="Ferrallin" class="w-10 h-10 object-contain">
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-white">FERRALLIN</h2>
                                    <p class="text-sm text-gray-300 flex items-center gap-2">
                                        <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                        Asistente Virtual Inteligente
                                    </p>
                                </div>
                            </div>
                            <a href="{{ route('asistente.index') }}" wire:navigate
                               class="bg-gray-800 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center gap-2 border border-gray-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                Abrir Chat
                            </a>
                        </div>
                    </div>
                    <div class="p-6 bg-gray-50">
                        <p class="text-gray-700 mb-4">
                            Ferrallin es tu asistente virtual potenciado por inteligencia artificial. Puede ayudarte con consultas sobre el sistema, gestión de información y mucho más.
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition">
                                <div class="flex items-start gap-2">
                                    <span class="text-2xl">🔍</span>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 text-sm">Consultas SQL</h4>
                                        <p class="text-xs text-gray-600">Realiza búsquedas en la base de datos</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition">
                                <div class="flex items-start gap-2">
                                    <span class="text-2xl">💡</span>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 text-sm">Información</h4>
                                        <p class="text-xs text-gray-600">Obtén datos del sistema al instante</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition">
                                <div class="flex items-start gap-2">
                                    <span class="text-2xl">⚙️</span>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 text-sm">Gestión</h4>
                                        <p class="text-xs text-gray-600">Ayuda con tareas administrativas</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-8 text-center">
                <a href="#" onclick="document.getElementById('modal-politicas').classList.remove('hidden')"
                    class="text-sm text-blue-600 hover:underline">
                    Políticas de privacidad y cookies
                </a>
            </div>

            @if(Auth::id() === 1)
            <!-- Botón de prueba de notificaciones (solo para usuario ID 1) -->
            <div class="mt-4 text-center" x-data="{ loading: false, message: '' }">
                <button
                    @click="
                        loading = true;
                        message = '';
                        fetch('{{ route('fcm.test') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ message: 'Notificación de prueba enviada desde el dashboard' })
                        })
                        .then(r => r.json())
                        .then(data => {
                            loading = false;
                            message = data.success ? 'Notificación enviada!' : 'Error al enviar';
                            setTimeout(() => message = '', 3000);
                        })
                        .catch(e => {
                            loading = false;
                            message = 'Error de conexión';
                            setTimeout(() => message = '', 3000);
                        });
                    "
                    :disabled="loading"
                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-medium rounded-lg transition-colors"
                >
                    <svg x-show="!loading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <svg x-show="loading" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="loading ? 'Enviando...' : 'Enviar notificación de prueba'"></span>
                </button>
                <p x-show="message" x-text="message" class="mt-2 text-sm" :class="message.includes('Error') ? 'text-red-600' : 'text-green-600'"></p>
            </div>
            @endif
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

                <!-- Notificaciones Push -->
                <div x-data="{
                    status: 'default',
                    init() {
                        this.checkStatus();
                    },
                    checkStatus() {
                        if (!('Notification' in window) || !('serviceWorker' in navigator)) {
                            this.status = 'unsupported';
                            return;
                        }
                        this.status = Notification.permission;
                    },
                    async activarNotificaciones() {
                        this.status = 'loading';
                        try {
                            if (typeof window.FirebasePush === 'undefined') {
                                console.error('FirebasePush no está disponible');
                                this.status = 'default';
                                return;
                            }
                            await window.FirebasePush.init();
                            const token = await window.FirebasePush.requestPermission();
                            if (token) {
                                this.status = 'granted';
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        title: 'Notificaciones activadas',
                                        text: 'Recibirás alertas importantes en tu dispositivo.',
                                        icon: 'success',
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 3000
                                    });
                                }
                            } else {
                                this.checkStatus();
                            }
                        } catch (error) {
                            console.error('Error activando notificaciones:', error);
                            this.checkStatus();
                        }
                    }
                }" class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800">Notificaciones Push</h4>
                                <p class="text-xs text-gray-600">Recibe alertas importantes incluso cuando no estés en la aplicación</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span x-cloak x-show="status === 'loading'" class="text-sm text-gray-500">
                                <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                            <span x-cloak x-show="status === 'granted'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Activadas
                            </span>
                            <span x-cloak x-show="status === 'denied'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                Bloqueadas
                            </span>
                            <span x-cloak x-show="status === 'unsupported'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                No soportado
                            </span>
                            <button x-cloak x-show="status === 'default'"
                                    @click="activarNotificaciones()"
                                    class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
                                Activar
                            </button>
                        </div>
                    </div>
                    <p x-cloak x-show="status === 'denied'" class="mt-2 text-xs text-red-600">
                        Has bloqueado las notificaciones. Para activarlas, haz clic en el icono del candado en la barra de direcciones de tu navegador y permite las notificaciones.
                    </p>
                </div>

                <!-- Checkboxes -->
                <form method="POST" action="{{ route('politicas.aceptar') }}" class="space-y-4 mt-6">
                    @csrf

                    <div class="flex items-start space-x-2">
                        <input type="checkbox" id="acepta_privacidad" name="acepta_privacidad"
                            {{ auth()->user()->acepta_politica_privacidad ? 'checked' : '' }} required class="mt-1">
                        <label for="acepta_privacidad" class="text-sm text-gray-700">
                            He leído y acepto la Política de Privacidad
                        </label>
                    </div>

                    <div class="flex items-start space-x-2">
                        <input type="checkbox" id="acepta_cookies" name="acepta_cookies"
                            {{ auth()->user()->acepta_politica_cookies ? 'checked' : '' }} required class="mt-1">
                        <label for="acepta_cookies" class="text-sm text-gray-700">
                            He leído y acepto la Política de Cookies
                        </label>
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
