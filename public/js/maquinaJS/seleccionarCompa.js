function seleccionarCompañero(maquinaId) {
    if (!usuarios || !Array.isArray(usuarios) || usuarios.length === 0) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "No hay usuarios disponibles para seleccionar.",
        });
        return;
    }

    let opciones = `<option value="" selected>Sin compañero</option>`;
    opciones += usuarios
        .map(
            (usuario) =>
                `<option value="${usuario.id}">${usuario.name}</option>`
        )
        .join("");

    Swal.fire({
        title: "Seleccionar Compañero",
        html: `
        <select id="users_id_2" style="width: 100%; padding: 10px; font-size: 16px;">
            ${opciones}
        </select>
    `,
        showCancelButton: true,
        confirmButtonText: "Iniciar Sesión",
        cancelButtonText: "Cancelar",
        preConfirm: () => {
            const selectElement = document.getElementById("users_id_2");
            const users_id_2 = selectElement ? selectElement.value : null;

            return fetch(guardarSesionUrl, {
                // Usamos la variable global en lugar de Blade
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken, // Usamos la variable global en lugar de Blade
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    maquina_id: maquinaId,
                    users_id_2: users_id_2 || null,
                }),
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(response.statusText);
                    }
                    return response.json();
                })
                .catch((error) => {
                    Swal.showValidationMessage(`Error: ${error}`);
                });
        },
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `/manager/public/maquinas/${maquinaId}`;
        }
    });
}
