function initSalidasActualizarEstado() {
    window.actualizarEstado = function (salidaId) {
        // Confirmar la acción antes de continuar
        Swal.fire({
            title: "¿Estás seguro?",
            text: "¿Quieres marcar esta salida como completada?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Sí, marcar como completada",
            cancelButtonText: "Cancelar",
        }).then((result) => {
            if (result.isConfirmed) {
                // Hacer la solicitud AJAX para actualizar el estado
                fetch(`/salidas/${salidaId}/actualizar-estado`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({
                        estado: "completada",
                    }),
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            Swal.fire(
                                "Completada",
                                "La salida ha sido marcada como completada.",
                                "success"
                            );
                            location.reload(); // Recargar la página para reflejar el cambio
                        } else {
                            Swal.fire(
                                "Completada",
                                data.message, // Mostrar el mensaje de error del backend
                                "success"
                            );
                        }
                    })
                    .catch((error) => {
                        Swal.fire(
                            "Error",
                            "Hubo un error al realizar la solicitud.",
                            "error"
                        );
                    });
            }
        });
    };
}

// Inicialización compatible con Livewire Navigate
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initSalidasActualizarEstado);
} else {
    initSalidasActualizarEstado();
}
document.addEventListener("livewire:navigated", initSalidasActualizarEstado);
