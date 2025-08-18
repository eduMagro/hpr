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
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: {!! json_encode(session('error')) !!}, // Esto evita errores de comillas
                confirmButtonColor: '#d33',
                showCancelButton: true,
                cancelButtonText: "Reportar Error"
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    notificarProgramador({!! json_encode(session('error')) !!});
                }
            });
        });
    </script>
@endif

@if (session('success'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: 'success',
                text: @json(session('success')),
                confirmButtonColor: '#28a745'
            }).then(() => {
                console.console.log('Operación exitosa:', @json(session('success')));
                // Recarga la página tras el mensaje
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


<!-- Función para reportar errores a programadores -->
<script>
    function notificarProgramador(mensaje) {
        const urlActual = window.location.href;
        const mensajeCompleto = `🔗 URL: ${urlActual}\n📜 Mensaje: ${mensaje}`;

        fetch("{{ route('alertas.store') }}", { // usa el helper de ruta
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json', // <-- importante
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
                if (!resp.ok) { // capturamos 405, 500, etc.
                    const texto = await resp.text();
                    throw new Error(`HTTP ${resp.status}: ${texto}`);
                }
                return resp.json(); // ya estamos seguros de que ES JSON
            })
            .then(data => {
                Swal.fire('Notificación enviada',
                    'Los técnicos han sido notificados.',
                    'success');
            })
            .catch(err => console.error('⚠️ Error:', err));
    }
</script>
