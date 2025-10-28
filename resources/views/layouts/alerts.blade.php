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

@if ($errors->any())
    <script>
        document.addEventListener("DOMContentLoaded", function() {
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
        });
    </script>
@endif


@if (session('error'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const nombreArchivo = @json(session('nombre_archivo', null));
            let errorMensaje = {!! json_encode(session('error')) !!};

            // ✅ Si hay nombre de archivo y no está en el mensaje, añadirlo
            if (nombreArchivo && !errorMensaje.includes(nombreArchivo)) {
                errorMensaje = `📄 Archivo: ${nombreArchivo}\n\n${errorMensaje}`;
            }

            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: '<div style="text-align: left; white-space: pre-wrap;">' + errorMensaje.replace(/\n/g,
                    '<br>') + '</div>',
                confirmButtonColor: '#d33',
                showCancelButton: true,
                cancelButtonText: "Reportar Error"
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    notificarProgramador(errorMensaje, 'Error en procesamiento de archivo');
                }
            });
        });
    </script>
@endif

@if (session('success'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const mensaje = @json(session('success'));
            const esImportacion = @json(session('import_report', false));
            const tieneAdvertencias = @json(session('tiene_advertencias', false));
            const nombreArchivo = @json(session('nombre_archivo', null));

            // ✅ SI ES IMPORTACIÓN → Formato especial con HTML
            if (esImportacion) {
                // Convertir saltos de línea a <br> para mostrar en HTML
                const mensajeHtml = mensaje.replace(/\n/g, '<br>');

                // Configuración especial para importaciones
                const config = {
                    icon: 'success',
                    html: '<div style="text-align: left; font-family: monospace; white-space: pre-wrap;">' +
                        mensajeHtml + '</div>',
                    confirmButtonColor: '#28a745',
                    width: '650px',
                };

                // Si tiene advertencias, añadir botón de reportar
                if (tieneAdvertencias) {
                    config.showCancelButton = true;
                    config.cancelButtonText = '⚠️ Reportar Advertencias';
                    config.confirmButtonText = 'Aceptar';
                    config.cancelButtonColor = '#f59e0b';
                }

                Swal.fire(config).then((result) => {
                    // Si clickeó en "Reportar Advertencias"
                    if (result.dismiss === Swal.DismissReason.cancel && tieneAdvertencias) {
                        // Incluir nombre de archivo en el asunto
                        const asunto = nombreArchivo ?
                            `Advertencias en importación: ${nombreArchivo}` :
                            'Advertencias en importación de planillas';

                        notificarProgramador(mensaje, asunto);
                    }
                });
            }
            // ✅ SI NO ES IMPORTACIÓN → Formato simple (como antes)
            else {
                Swal.fire({
                    icon: 'success',
                    text: mensaje, // ← Texto simple sin formateo
                    confirmButtonColor: '#28a745'
                }).then(() => {
                    console.log('Operación exitosa:', mensaje);
                });
            }
        });
    </script>
@endif

@if (session('info'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: 'info',
                title: 'Información',
                text: @json(session('info')),
                confirmButtonColor: '#3B82F6' // azul Tailwind
            });
        });
    </script>
@endif

@if (session('warning'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: @json(session('warning')),
                confirmButtonColor: '#FBBF24' // amarillo Tailwind
            });
        });
    </script>
@endif

@if (session('warnings'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @foreach (session('warnings') as $warning)
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: "{{ $warning }}",
                    timer: 5000,
                    showConfirmButton: false
                });
            @endforeach
        });
    </script>
@endif


<!-- Función para notificar a programadores -->
<script>
    function notificarProgramador(mensaje, asunto = 'Error reportado por usuario') {
        const urlActual = window.location.href;
        const usuario = '{{ auth()->user()->name ?? 'Usuario desconocido' }}';
        const email = '{{ auth()->user()->email ?? 'Email no disponible' }}';

        // ✅ Mensaje completo con contexto mejorado
        const mensajeCompleto = `🔗 URL: ${urlActual}

👤 Usuario: ${usuario} (${email})
📅 Fecha/Hora: ${new Date().toLocaleString('es-ES')}

📋 ${asunto}

📜 Mensaje:
${mensaje}

---
Navegador: ${navigator.userAgent}`;

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
