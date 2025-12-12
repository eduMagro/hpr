import "./bootstrap";

// Livewire 3 incluye Alpine; solo cargamos y arrancamos si no está presente
if (!window.Alpine) {
    import('alpinejs').then(({ default: Alpine }) => {
        window.Alpine = Alpine;
        Alpine.start();
    }).catch((err) => {
        console.error('No se pudo cargar Alpine dinámicamente', err);
    });
}
