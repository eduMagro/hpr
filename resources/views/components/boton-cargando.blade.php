@props(['text' => 'Enviar', 'href' => null, 'type' => 'button'])

@if ($href)
    <a href="{{ $href }}" class="btn btn-primary flex items-center space-x-2" onclick="mostrarCargando(this)">
        <span class="hidden spinner"></span>
        <span>{{ $text }}</span>
    </a>
@else
    <button type="{{ $type }}" class="btn btn-primary flex items-center space-x-2" onclick="mostrarCargando(this)">
        <span class="hidden spinner"></span>
        <span>{{ $text }}</span>
    </button>
@endif

<!-- Estilos del Spinner -->
<style>
    .spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid white;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-right: 8px;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }
</style>

<!-- Script para mostrar el spinner -->
<script>
    function mostrarCargando(boton) {
        boton.disabled = true;
        let spinner = boton.querySelector('.spinner');
        if (spinner) {
            spinner.classList.remove('hidden'); // Muestra el spinner
        }
    }
</script>
