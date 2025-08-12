// === Context menu (estilo "fc-contextmenu") ===============================

let current = null;

export function closeMenu() {
    if (!current) return;
    current.remove();
    current = null;
    document.removeEventListener("click", closeMenu);
    document.removeEventListener("contextmenu", closeMenu, true);
    document.removeEventListener("scroll", closeMenu, true);
    window.removeEventListener("resize", closeMenu);
    window.removeEventListener("keydown", onEsc);
}

function onEsc(e) {
    if (e.key === "Escape") closeMenu();
}

export function openMenuAt(x, y, html) {
    closeMenu();

    const el = document.createElement("div");
    el.className = "fc-contextmenu";
    Object.assign(el.style, {
        position: "fixed",
        top: y + "px",
        left: x + "px",
        zIndex: 99999,
        minWidth: "240px",
        background: "#fff",
        border: "1px solid #e5e7eb",
        boxShadow:
            "0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05)",
        borderRadius: "8px",
        overflow: "hidden",
        fontFamily: "system-ui, -apple-system, Segoe UI, Roboto, sans-serif",
    });

    el.innerHTML = html;
    document.body.appendChild(el);
    current = el;

    // Reposicionar si se sale del viewport
    const rect = el.getBoundingClientRect();
    const dx = Math.max(0, rect.right - window.innerWidth + 8);
    const dy = Math.max(0, rect.bottom - window.innerHeight + 8);
    if (dx || dy) {
        el.style.left = Math.max(8, x - dx) + "px";
        el.style.top = Math.max(8, y - dy) + "px";
    }

    // Cierre seguro
    setTimeout(() => {
        document.addEventListener("click", closeMenu);
        document.addEventListener("contextmenu", closeMenu, true);
        document.addEventListener("scroll", closeMenu, true);
        window.addEventListener("resize", closeMenu);
        window.addEventListener("keydown", onEsc);
    }, 0);

    return el;
}

/**
 * Builder de acciones: items = [{label, icon, danger, onClick}], headerHtml opcional
 */
export function openActionsMenu(x, y, { headerHtml = "", items = [] } = {}) {
    const html = `
    <div class="ctx-menu-container">
      ${headerHtml ? `<div class="ctx-menu-header">${headerHtml}</div>` : ""}
      ${items
          .map(
              (it, i) => `
        <button type="button"
          class="ctx-menu-item${it.danger ? " ctx-menu-danger" : ""}"
          data-idx="${i}">
          ${it.icon ? `<span class="ctx-menu-icon">${it.icon}</span>` : ""}
          <span class="ctx-menu-label">${it.label}</span>
        </button>`
          )
          .join("")}
    </div>
  `;

    const el = openMenuAt(x, y, html);

    // Delegación de eventos: SIN usar "it" suelto
    el.querySelectorAll(".ctx-menu-item").forEach((btn) => {
        btn.addEventListener("click", async (e) => {
            e.preventDefault();
            e.stopPropagation();
            const idx = Number(btn.dataset.idx);
            const action = items[idx]?.onClick;
            closeMenu(); // cerrar primero siempre
            try {
                await action?.();
            } catch (err) {
                console.error(err);
            }
        });
    });

    return el;
}
