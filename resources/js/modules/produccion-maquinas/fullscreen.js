let isFullScreen = false;

function handleEscKey(e) {
    if (e.key === 'Escape' && isFullScreen) {
        toggleFullScreen();
    }
}

export function toggleFullScreen() {
    const container = document.getElementById('produccion-maquinas-container');
    const sidebar = document.querySelector('[class*="sidebar"]') || document.querySelector('aside');
    const header = document.querySelector('nav');
    const breadcrumbs = document.querySelector('[class*="breadcrumb"]');
    const expandIcon = document.getElementById('fullscreen-icon-expand');
    const collapseIcon = document.getElementById('fullscreen-icon-collapse');
    const fullscreenBtn = document.getElementById('fullscreen-btn');
    const fullscreenText = document.getElementById('fullscreen-text');

    if (!container || !expandIcon || !collapseIcon || !fullscreenBtn) {
        console.warn('Elementos de fullscreen no encontrados');
        return;
    }

    if (!isFullScreen) {
        // Entrar en pantalla completa
        if (sidebar) sidebar.style.display = 'none';
        if (header) header.style.display = 'none';
        if (breadcrumbs) breadcrumbs.style.display = 'none';

        container.classList.add('fixed', 'inset-0', 'z-50', 'bg-gray-50', 'overflow-auto');
        container.style.padding = '1rem';

        expandIcon.classList.add('hidden');
        collapseIcon.classList.remove('hidden');
        fullscreenBtn.title = 'Salir de pantalla completa (ESC)';
        if (fullscreenText) fullscreenText.textContent = 'Contraer';

        isFullScreen = true;
        document.addEventListener('keydown', handleEscKey);
    } else {
        // Salir de pantalla completa
        if (sidebar) sidebar.style.display = '';
        if (header) header.style.display = '';
        if (breadcrumbs) breadcrumbs.style.display = '';

        container.classList.remove('fixed', 'inset-0', 'z-50', 'bg-gray-50', 'overflow-auto');
        container.style.padding = '';

        expandIcon.classList.remove('hidden');
        collapseIcon.classList.add('hidden');
        fullscreenBtn.title = 'Pantalla completa (F11)';
        if (fullscreenText) fullscreenText.textContent = 'Expandir';

        isFullScreen = false;
        document.removeEventListener('keydown', handleEscKey);
    }
}
