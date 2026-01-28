import { httpJSON } from "../http.js";
import { R } from "../config.js";

export async function editarFichaje(event) {
    const props = event.extendedProps || {};
    const entradaActual = props.entrada || "";
    const salidaActual = props.salida || "";

    const res = await Swal.fire({
        title: "Editar fichaje",
        html: `
      <div class="flex flex-col gap-3">
        <label class="text-left text-sm">Entrada</label>
        <input id="entradaHora" type="time" class="swal2-input" value="${entradaActual}">
        <label class="text-left text-sm">Salida</label>
        <input id="salidaHora" type="time" class="swal2-input" value="${salidaActual}">
        <button type="button" id="btnEliminarHoras" class="mt-2 px-3 py-1 text-sm text-red-600 hover:text-red-800 hover:bg-red-50 rounded border border-red-300">
          Eliminar horas
        </button>
      </div>`,
        showCancelButton: true,
        confirmButtonText: "Guardar",
        cancelButtonText: "Cancelar",
        didOpen: () => {
            document.getElementById("btnEliminarHoras").addEventListener("click", () => {
                document.getElementById("entradaHora").value = "";
                document.getElementById("salidaHora").value = "";
            });
        },
        preConfirm: () => {
            const entrada = document.getElementById("entradaHora").value;
            const salida = document.getElementById("salidaHora").value;
            return { entrada: entrada || null, salida: salida || null };
        },
    });
    if (!res.isConfirmed) return;

    const id = event.id.toString().replace(/^turno-/, "");
    await httpJSON(R().asignacion.updateHoras.replace("__ID__", id), {
        method: "POST",
        body: res.value,
    });

    event.setExtendedProp("entrada", res.value.entrada);
    event.setExtendedProp("salida", res.value.salida);
    Swal.fire({
        icon: "success",
        title: "Horas actualizadas",
        timer: 1500,
        showConfirmButton: false,
    });
}
