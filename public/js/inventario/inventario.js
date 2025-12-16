function initInventarioUI() {
    const CONTENIDO = document.getElementById("contenido");
    if (!CONTENIDO) return;

    const SECTORES = Array.from(document.getElementsByClassName("escondible"));
    const SUBCONTENIDOS = Array.from(document.getElementsByClassName("subcontenido"));
    const DESPLEGAR_SUBCONTENIDOS = Array.from(document.getElementsByClassName("desplegar-subcontenido"));

    aparecer(CONTENIDO);
    mostrarOcultarSectores(SECTORES, CONTENIDO, SUBCONTENIDOS);
    bindPergaminoTriggers(DESPLEGAR_SUBCONTENIDOS);
}

function mostrarOcultarSectores(sectores, contenido, subcontenidos) {
    sectores.forEach((e) => {
        if (e.dataset.invBound === "1") return;
        e.dataset.invBound = "1";

        e.addEventListener("click", (ev) => {
            const self = ev.currentTarget;                 // el sector clickado
            const estaAbierto = self.classList.contains("mostrandoDetalles");

            if (estaAbierto) {
                // Cerrar
                reaparecer(contenido);
                self.classList.remove("mostrandoDetalles", "z-50", "relative", "h-[5vh]");
                cerrarTodosSubcontenidos(subcontenidos);
                self.classList.add("h-full");

                sectores.forEach((f) => {
                    // mostrar todo y devolver eventos al padre
                    f.classList.remove("hidden");
                    f.parentElement.classList.remove("absolute", "-z-30");
                    f.parentElement.style.pointerEvents = "";   // quitar bloqueo por si acaso
                    f.style.pointerEvents = "auto";
                    // si tocaste alturas antes, restáuralas en f (no en e)
                    f.classList.remove("h-[5vh]");
                    f.classList.add("h-full");
                });

            } else {
                // Abrir
                self.classList.add("mostrandoDetalles", "relative", "z-50");
                self.classList.add("h-[5vh]");
                self.classList.remove("h-full");

                sectores.forEach((f) => {
                    if (f !== self) {
                        // Oculta completamente (no ocupa ni recibe clics)
                        f.classList.add("hidden");
                        // Bloquea también el contenedor por si queda encima
                        f.parentElement.style.pointerEvents = "none";
                        // Si quieres además sacarlo del flujo/z:
                        f.parentElement.classList.add("absolute", "-z-30");
                    }
                });
            }
        });
    });
}

function cerrarTodosSubcontenidos(subcontenidos) {
    subcontenidos.forEach(panel => {
        // quitar transición para que sea instantáneo
        const prev = panel.style.transition;
        panel.style.transition = "none";

        panel.style.height = "0px";
        panel.style.opacity = "0";
        panel.dataset.open = "0";
        panel.dataset.animating = "0";

        // restaurar transición para futuras animaciones
        // (coincidir con tu toggle: height/opacity 200ms)
        requestAnimationFrame(() => {
            panel.style.transition = prev || "height 200ms ease, opacity 200ms ease";
        });

        // accesibilidad
        const trigger = panel.previousElementSibling;
        if (trigger) trigger.setAttribute("aria-expanded", "false");
    });

    const QR_INPUTS = Array.from(document.getElementsByClassName("qr-input"));
    const QR_DESPLEGABLE_INFO = Array.from(document.getElementsByClassName("qr-desplegable-info"));

    QR_INPUTS.forEach(element => {
        element.classList.add("hidden");
    });

    QR_DESPLEGABLE_INFO.forEach(element => {
        element.classList.remove("hidden");
    });
}


function aparecer(contenido) {
    contenido.classList.remove("opacity-0");
}
function reaparecer(contenido) {
    contenido.classList.remove("transform", "transition-all", "duration-200");
    contenido.classList.add("opacity-0");
    aparecer(contenido);
}


// DESPLEGAR SUBCONTENIDO COMO PERGAMINO
document.addEventListener("DOMContentLoaded", () => {
    initInventarioUI();
});

document.addEventListener("livewire:navigated", () => {
    initInventarioUI();
});

function bindPergaminoTriggers(triggers) {
    triggers.forEach((trigger) => {
        if (trigger.dataset.invBound === "1") return;
        trigger.dataset.invBound = "1";

        const panel = trigger.nextElementSibling;
        if (!panel || !panel.classList.contains("subcontenido")) return;

        // Estado inicial
        panel.dataset.open = panel.dataset.open ?? "0";
        panel.dataset.animating = "0";
        panel.style.overflow = "hidden";
        panel.style.height = "0px";
        panel.style.opacity = "0";
        panel.style.transition = "height 200ms ease, opacity 200ms ease";
        trigger.setAttribute("aria-expanded", "false");

        trigger.addEventListener("click", (ev) => {
            // Si no quieres que clic en el input cierre/abra, deja esta línea:
            if (ev.target.closest('input, textarea, select, button, a, [data-no-toggle]')) return;

            togglePergamino(panel, trigger, 200);
        });
    });
}

function togglePergamino(panel, trigger, durMs = 200) {
    if (panel.dataset.animating === "1") return; // debounce

    // elementos del header (trigger)
    const input = trigger.querySelector('.qr-input');
    const info = trigger.querySelector('.qr-desplegable-info');

    const isOpen = panel.dataset.open === "1";

    if (isOpen) {
        // CERRAR
        panel.dataset.animating = "1";

        const full = panel.scrollHeight;
        panel.style.height = full + "px";
        panel.getBoundingClientRect(); // reflow

        panel.style.height = "0px";
        panel.style.opacity = "0";

        const onCloseEnd = (e) => {
            if (e.propertyName !== "height") return;
            panel.removeEventListener("transitionend", onCloseEnd);
            panel.dataset.open = "0";
            panel.dataset.animating = "0";
            trigger?.setAttribute("aria-expanded", "false");

            // --- swap header ---
            if (input) input.classList.add('hidden');
            if (info) info.classList.remove('hidden');
        };
        panel.addEventListener("transitionend", onCloseEnd);

    } else {
        // ABRIR
        panel.dataset.animating = "1";

        panel.style.height = "0px";
        panel.getBoundingClientRect(); // reflow

        const full = panel.scrollHeight;
        panel.style.opacity = "1";
        panel.style.height = full + "px";

        const onOpenEnd = (e) => {
            if (e.propertyName !== "height") return;
            panel.removeEventListener("transitionend", onOpenEnd);
            panel.dataset.open = "1";
            panel.dataset.animating = "0";
            trigger?.setAttribute("aria-expanded", "true");
            panel.style.height = "auto";

            // --- swap header ---
            if (info) info.classList.add('hidden');
            if (input) {
                input.classList.remove('hidden');
                input.focus?.({ preventScroll: true });
            }
        };
        panel.addEventListener("transitionend", onOpenEnd);
    }
}
