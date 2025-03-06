@if (session('abort'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Acceso denegado',
            text: "{{ session('abort') }}",
        });
    </script>
@endif

@if ($errors->any())
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: 'error',
                title: 'Errores encontrados',
                html: '<ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
                confirmButtonColor: '#d33',
                showCancelButton: true,
                cancelButtonText: "Reportar a Programador"
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    notificarProgramador("Se han detectado errores en la validación de datos.");
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
                text: '{{ session('error') }}',
                confirmButtonColor: '#d33',
                showCancelButton: true,
                cancelButtonText: "Reportar a Programador"
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    notificarProgramador("{{ session('error') }}");
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
                text: '{{ session('success') }}',
                confirmButtonColor: '#28a745'
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
        const mensajeCompleto = `
         🔗 **URL:** ${urlActual}
        📜 **Mensaje:** ${mensaje}
    `;

        fetch('/alertas/store', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    mensaje: mensajeCompleto,
                    categoria: "programador" // 🔹 Se asigna el destinatario como "programador"
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: "Notificación enviada",
                        text: "El programador ha sido notificado.",
                        icon: "success"
                    });
                } else {
                    console.error("⚠️ Error al enviar la alerta:", data.error);
                }
            })
            .catch(error => console.error("⚠️ Error inesperado:", error));
    }
</script>
