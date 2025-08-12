import { openMenuAt, closeMenu } from "./baseMenu.js";
import { editarFichaje } from "../dialogs/fichaje.js";
import { httpJSON } from "../http.js";
import { R } from "../config.js";

export function openWorkerMenu(x, y, event) {
    const nombre = event.title || "Operario";
    const cargo = event.extendedProps?.categoria_nombre ?? "";
    const esp = event.extendedProps?.especialidad_nombre ?? "Sin especialidad";

    const el = openMenuAt(
        x,
        y,
        `
    <div style="padding:10px 12px; font-size:13px; color:#6b7280; border-bottom:1px solid #f3f4f6;">
      ${nombre} <div style="font-size:12px">${cargo} · ${esp}</div>
    </div>
    <button id="ctx-editar-fichajes" style="display:block;width:100%;text-align:left;padding:10px 12px;font-size:14px;background:#fff;border:none;cursor:pointer;">
      ✏️ Editar fichajes
    </button>
    <button id="ctx-eliminar-registro" style="display:block;width:100%;text-align:left;padding:10px 12px;font-size:14px;background:#fff;border:none;cursor:pointer;color:#b91c1c;">
      🗑️ Eliminar registro
    </button>
  `
    );

    el.querySelector("#ctx-editar-fichajes").addEventListener(
        "click",
        async () => {
            closeMenu();
            await editarFichaje(event);
        }
    );

    el.querySelector("#ctx-eliminar-registro").addEventListener(
        "click",
        async () => {
            closeMenu();

            const ok = await Swal.fire({
                icon: "warning",
                title: "Eliminar registro",
                html: `<div>¿Seguro que quieres eliminar este evento/asignación?</div>
             <div class="text-xs text-gray-500 mt-1">Esta acción no se puede deshacer.</div>`,
                confirmButtonText: "Eliminar",
                cancelButtonText: "Cancelar",
                showCancelButton: true,
                confirmButtonColor: "#b91c1c",
            }).then((r) => r.isConfirmed);
            if (!ok) return;

            const id = event.id.toString().replace(/^turno-/, "");
            const url = R().asignacion.delete.replace("__ID__", id);
            await httpJSON(url, { method: "DELETE" });
            event.remove();
            Swal.fire({
                icon: "success",
                title: "Registro eliminado",
                timer: 1300,
                showConfirmButton: false,
            });
        }
    );
}
