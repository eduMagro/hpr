window.guardarCambios = function(elemento) {
    // Construir el objeto con los campos que se desean actualizar
    const datosActualizar = {
        // Aunque 'id' no suele actualizarse, lo incluimos para referencia.
        id: elemento.id,

        // Relaciones: se envían los id (si no están disponibles, se usa null)
        planilla_id: elemento.planilla?.id ?? null,
        users_id: elemento.users_id ?? null,
        users_id_2: Number(elemento.users_id_2) || null,
        etiqueta_id: elemento.etiquetaRelacion?.id ?? null,
        paquete_id: elemento.paquete_id ?? null,
        maquina_id: Number(elemento.maquina_id) || null,
        maquina_id_2: Number(elemento.maquina_id_2) || null,
        maquina_id_3: Number(elemento.maquina_id_3) || null,
        producto_id: Number(elemento.producto_id) || null,
        producto_id_2: Number(elemento.producto_id_2) || null,
        producto_id_3: Number(elemento.producto_id_3) || null,

        // Otros campos
        figura: elemento.figura || null,
        dimensiones: elemento.dimensiones || null,
        peso: elemento.peso || null,
        diametro: elemento.diametro || null,
        longitud: elemento.longitud || null,
        barras: elemento.barras || null,

        estado: elemento.estado ?? null,
    };

    fetch(`/elementos/${elemento.id}`, {
        method: "PUT",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify(datosActualizar),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Actualizar objeto local con los datos del servidor (incluyendo accessors como diametro_mm)
                if (data.data) {
                    Object.assign(elemento, data.data);
                }

                // Notificación Toast en lugar de reload
                if (typeof Swal !== 'undefined') {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    });

                    Toast.fire({
                        icon: 'success',
                        title: 'Elemento actualizado'
                    });
                }
            } else {
                let errorMsg =
                    data.message || "Ha ocurrido un error inesperado.";
                // Si existen errores de validación, concatenarlos
                if (data.errors) {
                    errorMsg = Object.values(data.errors).flat().join(" ");
                }
                Swal.fire({
                    icon: "error",
                    title: "Error al actualizar",
                    text: errorMsg,
                    confirmButtonText: "OK",
                });
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            Swal.fire({
                icon: "error",
                title: "Error de conexión",
                text: "No se pudo actualizar el elemento. Inténtalo nuevamente.",
                confirmButtonText: "OK",
            });
        });
}
