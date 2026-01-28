<!-- DEBUG: Verificar sesión -->
<script>
    console.log('🔍 Session check:', {
        error: @json(session('error')),
        success: @json(session('success')),
        warning: @json(session('warning')),
        info: @json(session('info'))
    });
</script>

@php
    // Parsear información del dispositivo usando Jenssegers\Agent
    $agent = new \Jenssegers\Agent\Agent();
    $userAgentRaw = request()->userAgent();

    // Navegador
    $browser = $agent->browser() ?: 'Navegador';
    if ($browser === 'Webkit' || $browser === 'WebKit') {
        if (str_contains($userAgentRaw, 'Edg/')) $browser = 'Edge';
        elseif (str_contains($userAgentRaw, 'OPR/')) $browser = 'Opera';
        elseif (str_contains($userAgentRaw, 'Chrome/')) $browser = 'Chrome';
        elseif (str_contains($userAgentRaw, 'Firefox/')) $browser = 'Firefox';
        elseif (str_contains($userAgentRaw, 'Safari/')) $browser = 'Safari';
    }
    $browserVersion = $agent->version($browser);
    $navegador = $browser . ($browserVersion ? ' ' . explode('.', $browserVersion)[0] : '');

    // Sistema operativo
    $platform = $agent->platform() ?: 'Sistema';
    if ($platform === 'AndroidOS') $platform = 'Android';
    elseif ($platform === 'OS X') $platform = 'macOS';
    $platformVersion = $agent->version($platform);
    $sistema = $platform . ($platformVersion && !str_contains($platform, 'Windows') ? ' ' . explode('.', $platformVersion)[0] : '');

    // Tipo de dispositivo
    $tipoDispositivo = $agent->isPhone() ? 'Móvil' : ($agent->isTablet() ? 'Tablet' : 'Escritorio');

    // Dispositivo específico (modelo)
    $dispositivo = $agent->device();
    if ($dispositivo === 'Webkit' || $dispositivo === 'WebKit' || !$dispositivo) {
        $dispositivo = null;
    }

    $infoDispositivo = [
        'navegador' => $navegador,
        'sistema' => $sistema,
        'tipo' => $tipoDispositivo,
        'dispositivo' => $dispositivo,
    ];
@endphp

@if (session('abort'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Acceso denegado',
            text: "{{ session('abort') }}",
        }).then(() => {
            window.location.reload(); // Recarga la página tras el mensaje
        });
    </script>
@endif


{{-- Los listeners de alertas ahora están consolidados en initAlertsPage() al final del archivo --}}



<!-- Interceptor global para errores 500 en AJAX/Livewire -->
<script data-navigate-once>
    (function() {
        // Evitar doble inicialización
        if (window._error500InterceptorInit) return;
        window._error500InterceptorInit = true;

        // Función genérica para mostrar errores con opción de reportar
        window.mostrarErrorConReporte = function(mensaje, titulo = 'Error', detalles = null) {
            const urlActual = window.location.href;
            let mensajeReporte = `Error en: ${urlActual}\nFecha: ${new Date().toLocaleString('es-ES')}\n\nMensaje: ${mensaje}`;
            if (detalles) {
                mensajeReporte += `\n\nDetalles: ${detalles}`;
            }

            Swal.fire({
                icon: 'error',
                title: titulo,
                html: `<p>${mensaje}</p>${detalles ? `<p class="text-sm text-gray-500 mt-2">${detalles}</p>` : ''}`,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Aceptar',
                showCancelButton: true,
                cancelButtonText: 'Reportar Error',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    if (typeof notificarProgramador === 'function') {
                        notificarProgramador(mensajeReporte, titulo);
                    }
                }
            });
        };

        // Helper global para parsear JSON de forma segura con reporte de errores
        window.parseJsonSafe = async function(response, contexto = '') {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                const preview = text.substring(0, 300) + (text.length > 300 ? '...' : '');
                window.mostrarErrorConReporte(
                    'El servidor devolvió una respuesta inválida',
                    'Error de respuesta',
                    `${contexto ? contexto + ': ' : ''}${e.message}\n\nRespuesta del servidor:\n${preview}`
                );
                throw e;
            }
        };

        // Función para mostrar error 500 con SweetAlert
        window.mostrarError500 = function(detalles = null) {
            const urlActual = window.location.href;
            let mensajeReporte = `Error 500 en: ${urlActual}\nFecha: ${new Date().toLocaleString('es-ES')}`;
            if (detalles) {
                mensajeReporte += `\nDetalles: ${detalles}`;
            }

            Swal.fire({
                icon: 'error',
                title: 'Error 500: Error del servidor',
                html: '<p>Ha ocurrido un error interno en el servidor.</p><p class="text-sm text-gray-500 mt-2">Puedes reportar este error para que el equipo técnico lo revise.</p>',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Aceptar',
                showCancelButton: true,
                cancelButtonText: 'Reportar Error',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    if (typeof notificarProgramador === 'function') {
                        notificarProgramador(mensajeReporte, 'Error 500 del servidor');
                    }
                }
            });
        };

        // Interceptar errores de Livewire
        document.addEventListener('livewire:init', () => {
            if (typeof Livewire !== 'undefined') {
                Livewire.hook('request', ({ fail }) => {
                    fail(({ status, content }) => {
                        if (status === 500) {
                            console.error('Error 500 en Livewire:', content);
                            window.mostrarError500(content?.message || 'Error en petición Livewire');
                        }
                    });
                });
            }
        });

        // Interceptar fetch global para errores 500
        const originalFetch = window.fetch;
        window.fetch = async function(...args) {
            try {
                const response = await originalFetch.apply(this, args);
                if (response.status === 500) {
                    const url = typeof args[0] === 'string' ? args[0] : args[0]?.url || 'desconocida';
                    // Excluir URLs no críticas que no deben mostrar error 500
                    const urlsExcluidas = ['/livewire/', '/alertas/sin-leer'];
                    const esUrlExcluida = urlsExcluidas.some(excluida => url.includes(excluida));

                    if (!esUrlExcluida) {
                        console.error('Error 500 en fetch:', url);
                        // Intentar obtener mensaje de error del response
                        const clone = response.clone();
                        try {
                            const data = await clone.json();
                            if (data.server_error) {
                                window.mostrarError500(data.message || 'Error del servidor');
                            }
                        } catch (e) {
                            // Si no es JSON, mostrar error genérico
                            window.mostrarError500(`URL: ${url}`);
                        }
                    }
                }
                return response;
            } catch (error) {
                throw error;
            }
        };
    })();
</script>

<!-- Función para notificar a programadores -->
<script>
    // Información del dispositivo parseada desde el servidor
    window._infoDispositivo = @json($infoDispositivo);

    function notificarProgramador(mensaje, asunto = 'Error reportado por usuario', urlPersonalizada = null) {
        const urlActual = urlPersonalizada || window.location.href;
        const usuario = '{{ auth()->user()->name ?? 'Usuario desconocido' }}';
        const email = '{{ auth()->user()->email ?? 'Email no disponible' }}';
        const dispositivo = window._infoDispositivo;

        // Formatear información del dispositivo
        let infoDispositivo = `${dispositivo.navegador} en ${dispositivo.sistema}`;
        if (dispositivo.tipo !== 'Escritorio') {
            infoDispositivo += ` (${dispositivo.tipo})`;
        }
        if (dispositivo.dispositivo) {
            infoDispositivo += ` - ${dispositivo.dispositivo}`;
        }

        // Mensaje completo con contexto mejorado (sin emojis para compatibilidad DB)
        const mensajeCompleto = `URL: ${urlActual}

Usuario: ${usuario} (${email})
Fecha/Hora: ${new Date().toLocaleString('es-ES')}

Asunto: ${asunto}

Mensaje:
${mensaje}

---
Dispositivo: ${infoDispositivo}`;

        fetch("{{ route('alertas.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .content
                },
                body: JSON.stringify({
                    mensaje: mensajeCompleto,
                    enviar_a_departamentos: ['Programador']
                })
            })
            .then(async resp => {
                if (!resp.ok) {
                    const texto = await resp.text();
                    throw new Error(`HTTP ${resp.status}: ${texto}`);
                }
                return resp.json();
            })
            .then(data => {
                Swal.fire({
                    icon: 'success',
                    title: 'Notificación enviada',
                    text: 'Los técnicos han sido notificados y revisarán las advertencias.',
                    confirmButtonColor: '#28a745'
                });
            })
            .catch(err => {
                console.error('⚠️ Error al enviar notificación:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al enviar',
                    text: 'No se pudo enviar la notificación. Por favor contacte directamente con el equipo técnico.',
                    confirmButtonColor: '#d33'
                });
            });
    }
</script>

<script>
    function initAlertsPage() {
        // Prevenir doble inicialización
        if (document.body.dataset.alertsPageInit === 'true') return;

        console.log('🔍 Inicializando sistema de alertas...');

        // Procesar errores de validación
        @if ($errors->any())
            let erroresHtml = '';
            let erroresTexto = '';
            @foreach ($errors->all() as $error)
                erroresHtml += '<li>{{ $error }}<\/li>';
                erroresTexto += '- {{ $error }}\n';
            @endforeach

            Swal.fire({
                icon: 'error',
                title: 'Errores encontrados',
                html: '<ul>' + erroresHtml + '</ul>',
                confirmButtonColor: '#d33',
                showCancelButton: true,
                cancelButtonText: "Reportar Error"
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    notificarProgramador(erroresTexto);
                }
            });
        @endif

        // Procesar mensaje de éxito
        @if (session('success'))
            const mensaje = @json(session('success'));
            const esImportacion = @json(session('import_report', false));
            const tieneAdvertencias = @json(session('tiene_advertencias', false));
            const nombreArchivo = @json(session('nombre_archivo', null));

            if (esImportacion) {
                const mensajeHtml = mensaje.replace(/\n/g, '<br>');
                const config = {
                    icon: 'success',
                    html: '<div style="text-align: left; font-family: monospace; white-space: pre-wrap;">' +
                        mensajeHtml + '</div>',
                    confirmButtonColor: '#28a745',
                    width: '650px',
                };

                if (tieneAdvertencias) {
                    config.showCancelButton = true;
                    config.cancelButtonText = '⚠️ Reportar Advertencias';
                    config.confirmButtonText = 'Aceptar';
                    config.cancelButtonColor = '#f59e0b';
                }

                Swal.fire(config).then((result) => {
                    if (result.dismiss === Swal.DismissReason.cancel && tieneAdvertencias) {
                        const asunto = nombreArchivo ?
                            `Advertencias en importación: ${nombreArchivo}` :
                            'Advertencias en importación de planillas';
                        notificarProgramador(mensaje, asunto);
                    }
                });
            } else {
                Swal.fire({
                    icon: 'success',
                    text: mensaje,
                    confirmButtonColor: '#28a745'
                }).then(() => {
                    console.log('Operación exitosa:', mensaje);
                });
            }
        @endif

        // Procesar mensaje de error
        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: @json(session('error')),
                confirmButtonColor: '#d33',
                showCancelButton: true,
                cancelButtonText: "Reportar Error"
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    notificarProgramador(@json(session('error')));
                }
            });
        @endif

        // Procesar error 500 del servidor
        @if (session('server_error'))
            const serverError = @json(session('server_error'));
            const urlOrigen = serverError.url_origen || window.location.href;
            let errorDetails = `URL con error: ${urlOrigen}\n\n${serverError.message}`;
            @if(config('app.debug'))
                if (serverError.exception) {
                    errorDetails += '\n\nDetalles: ' + serverError.exception;
                }
            @endif

            Swal.fire({
                icon: 'error',
                title: serverError.title || 'Error 500: Error del servidor',
                html: '<p>' + serverError.message + '</p><p class="text-xs text-gray-400 mt-2">URL: ' + urlOrigen + '</p>',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Aceptar',
                showCancelButton: true,
                cancelButtonText: 'Reportar Error',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    notificarProgramador(errorDetails, 'Error 500 del servidor', urlOrigen);
                }
            });
        @endif

        // Procesar mensaje de info
        @if (session('info'))
            Swal.fire({
                icon: 'info',
                title: 'Información',
                text: @json(session('info')),
                confirmButtonColor: '#3B82F6'
            });
        @endif

        // Procesar mensaje de warning
        @if (session('warning'))
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: @json(session('warning')),
                confirmButtonColor: '#FBBF24'
            });
        @endif

        // Procesar múltiples warnings
        @if (session('warnings'))
            @foreach (session('warnings') as $warning)
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: "{{ $warning }}",
                    timer: 5000,
                    showConfirmButton: false
                });
            @endforeach
        @endif

        // Marcar como inicializado
        document.body.dataset.alertsPageInit = 'true';
    }

    // Registrar en el sistema global
    window.pageInitializers = window.pageInitializers || [];
    window.pageInitializers.push(initAlertsPage);

    // Configurar listeners
    document.addEventListener('livewire:navigated', initAlertsPage);
    document.addEventListener('DOMContentLoaded', initAlertsPage);

    // Limpiar flag antes de navegar
    document.addEventListener('livewire:navigating', () => {
        document.body.dataset.alertsPageInit = 'false';
    });
</script>
