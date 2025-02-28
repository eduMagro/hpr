@props(['text' => 'Enviar', 'href' => null, 'type' => 'button'])

@if ($href)
    <a href="{{ $href }}" class="btn btn-primary flex items-center space-x-2" onclick="mostrarCargando(this)">
        <span class="spinner hidden"></span>
        <span class="btn-text">{{ $text }}</span>
    </a>
@else
    <button type="{{ $type }}" class="btn btn-primary flex items-center space-x-2" onclick="mostrarCargando(this)">
        <span class="spinner hidden"></span>
        <span class="btn-text">{{ $text }}</span>
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

    .hidden {
        display: none;
    }
</style>

<!-- Script para mostrar el Spinner sin bloquear el envío del formulario -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".form-con-spinner").forEach(form => {
            form.addEventListener("submit", function(event) {
                let boton = event.submitter; // Solo el botón que activó el envío
                if (boton) {
                    let spinner = boton.querySelector('.spinner');
                    let texto = boton.querySelector('.btn-text');

                    if (spinner && texto) {
                        spinner.classList.remove('hidden'); // Muestra el spinner
                        texto.classList.add('hidden'); // Oculta el texto del botón
                    }

                    boton.disabled = true; // Deshabilita SOLO este botón
                }
            });
        });
    });
</script>
