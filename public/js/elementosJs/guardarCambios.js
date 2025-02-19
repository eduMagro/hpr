function guardarCambios(elemento) {
    // Construir el objeto con los campos que se desean actualizar
    const datosActualizar = {
        // Aunque 'id' no suele actualizarse, lo incluimos para referencia.
        id: elemento.id,

        // Relaciones: se envían los id (si no están disponibles, se usa null)
        planilla_id: elemento.planilla?.id ?? null,
        etiqueta_id: elemento.etiquetaRelacion?.id ?? null,
        paquete_id: elemento.paquete_id ?? null,
        maquina_id: elemento.maquina_id ?? null,
        maquina_id_2: elemento.maquina_2?.id ?? null,
        maquina_id_3: elemento.maquina_3?.id ?? null,
        producto_id: elemento.producto?.id ?? null,
        producto_id_2: elemento.producto2?.id ?? null,
        ubicacion_id: elemento.ubicacion?.id ?? null,

        // Otros campos
        figura: elemento.figura || null,
        peso: elemento.peso || null,
        diametro: elemento.diametro || null,
        longitud: elemento.longitud || null,
        fecha_inicio: elemento.fecha_inicio || null,
        fecha_finalizacion: elemento.fecha_finalizacion || null,
        estado: elemento.estado || null,
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
                Swal.fire({
                    icon: "success",
                    title: "Elemento actualizado",
                    text: "El elemento se ha actualizado con éxito.",
                    timer: 2000,
                    showConfirmButton: false,
                }).then(() => {
                    window.location.reload(); // Recarga la página tras el mensaje
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error al actualizar",
                    text: data.message || "Ha ocurrido un error inesperado.",
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
