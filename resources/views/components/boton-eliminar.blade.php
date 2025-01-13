<button onclick="confirmarEliminacion('{{ $action }}')" class="text-red-500 hover:text-red-700 text-sm">
    Eliminar
</button>

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
