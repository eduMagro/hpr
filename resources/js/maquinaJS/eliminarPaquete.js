function eliminarPaquete(paqueteId) {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Sí, eliminar",
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/paquetes/${paqueteId}`, {
                method: "DELETE",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Eliminado",
                            text: data.message,
                            confirmButtonColor: "#28a745",
                        }).then(() => {
                            window.location.reload(); // Recargar la página
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: data.message,
                            confirmButtonColor: "#d33",
                        });
                    }
                })
                .catch((error) => {
                    console.error("Error en fetch:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error en conexión",
                        text: "No se pudo conectar con el servidor.",
                        confirmButtonColor: "#d33",
                    });
                });
        }
    });
}
