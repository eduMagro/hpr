<div id="notificacion-alertas" class="hidden fixed top-5 right-5 bg-red-600 text-white px-4 py-2 rounded-lg shadow-lg animate-fade-in">
    <p id="notificacion-alertas-texto" class="font-semibold">🔔 Tienes alertas sin leer</p>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        fetch("{{ route('alertas.sinleer') }}")
            .then(response => response.json())
            .then(data => {
                if (data.cantidad > 0) {
                    let notificacion = document.getElementById("notificacion-alerta");
                    notificacion.classList.remove("hidden");

                    // Ocultar después de 5 segundos
                    setTimeout(() => {
                        notificacion.classList.add("hidden");
                    }, 5000);
                }
            })
            .catch(error => console.error("Error al obtener alertas:", error));
    });
</script>

<style>
    @keyframes fade-in {
        0% {
            opacity: 0;
            transform: translateY(-10px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fade-in 0.5s ease-in-out;
    }
</style>
