document.addEventListener("alpine:init", () => {
    Alpine.data("salidaVerificada", () => ({
        todosVerificados: false,
        verificados: [],
        paqueteVerificado: false,
        idIngresado: "",

        verificarPaquete(event) {
            const { verificado, paqueteId } = event.detail;
            if (verificado) {
                if (!this.verificados.includes(paqueteId)) {
                    this.verificados.push(paqueteId);
                }
            } else {
                this.verificados = this.verificados.filter(
                    (id) => id !== paqueteId
                );
            }
            // Comprobamos si todos los paquetes han sido verificados
            this.todosVerificados =
                this.verificados.length ===
                document.querySelectorAll("[x-data]").length;
        },

        async actualizarEstado(salidaId) {
            const response = await fetch(`/salidas/${salidaId}/actualizar`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({ estado: "completada" }),
            });

            if (response.ok) {
                Swal.fire({
                    title: "Éxito",
                    text: 'El estado de la salida ha sido actualizado a "completada".',
                    icon: "success",
                    confirmButtonText: "OK",
                }).then(() => {
                    location.reload();
                });
            } else {
                const data = await response.json();
                Swal.fire({
                    title: "Error",
                    text:
                        data.message ||
                        "Hubo un error al actualizar el estado.",
                    icon: "error",
                    confirmButtonText: "Reintentar",
                });
            }
        },
    }));
});
