let current = null;

export function closeMenu() {
    if (!current) return;
    current.remove();
    current = null;
    document.removeEventListener("click", closeMenu);
    document.removeEventListener("scroll", closeMenu, true);
    window.removeEventListener("resize", closeMenu);
}

export function openMenuAt(x, y, html) {
    closeMenu();
    const el = document.createElement("div");
    el.className = "fc-contextmenu";
    Object.assign(el.style, {
        position: "fixed",
        top: y + "px",
        left: x + "px",
        zIndex: 9999,
        minWidth: "240px",
        background: "#fff",
        border: "1px solid #e5e7eb",
        boxShadow:
            "0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05)",
        borderRadius: "8px",
        overflow: "hidden",
    });
    el.innerHTML = html;
    document.body.appendChild(el);
    current = el;

    setTimeout(() => {
        document.addEventListener("click", closeMenu);
        document.addEventListener("scroll", closeMenu, true);
        window.addEventListener("resize", closeMenu);
    }, 0);

    return el;
}

/**
 * Builder genérico: pásale items [{label, icon, danger, onClick}] y opcional headerHtml
 */
export function openActionsMenu(x, y, { headerHtml = "", items = [] }) {
    const html = `
    <div class="ctx-menu-container">
      ${headerHtml ? `<div class="ctx-menu-header">${headerHtml}</div>` : ""}
      ${items
          .map(
              (it, i) => `
        <button class="ctx-menu-item${
            it.danger ? " ctx-menu-danger" : ""
        }${
            it.disabled ? " ctx-menu-disabled" : ""
        }" data-idx="${i}" ${it.disabled ? 'disabled' : ''}>
          ${it.icon ? `<span class="ctx-menu-icon">${it.icon}</span>` : ""}
          <span class="ctx-menu-label">${it.label}</span>
        </button>
      `
          )
          .join("")}
    </div>
  `;
    const el = openMenuAt(x, y, html);

    el.querySelectorAll(".ctx-menu-item").forEach((btn) => {
        const idx = parseInt(btn.dataset.idx, 10);
        const item = items[idx];

        btn.addEventListener("click", async (e) => {
            e.preventDefault();
            e.stopPropagation();

            if (item.disabled) return; // No ejecutar si está deshabilitado

            const action = item.onClick; // guarda la acción
            closeMenu(); // 🔒 ciérralo primero, siempre

            try {
                await action?.(); // luego ejecuta lo que toque
            } catch (err) {
                console.error(err);
            }
        });
        btn.addEventListener(
            "mouseup",
            (e) => {
                e.stopPropagation();
                if (!item.disabled) {
                    closeMenu();
                }
            },
            { once: true }
        );
    });
    return el;
}
