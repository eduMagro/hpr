<a href="{{ $action }}" onclick="event.preventDefault(); confirmarEliminacion('{{ $action }}')"
    class="w-10 h-10 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center" title="Eliminar">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
        stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3m-4 0h14" />
    </svg>
</a>


<form id="formulario-eliminar" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<script>
    function confirmarEliminacion(actionUrl) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡No podrás revertir esta acción!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formulario = document.getElementById('formulario-eliminar');
                formulario.action = actionUrl;
                formulario.submit();
            }
        });
    }
</script>
