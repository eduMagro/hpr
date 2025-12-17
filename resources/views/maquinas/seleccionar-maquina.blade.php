<x-app-layout>
    <x-slot name="title">Seleccionar Máquina - {{ config('app.name') }}</x-slot>

    <div class="min-h-screen bg-gray-100 flex items-center justify-center">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Cargando...</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function initSeleccionarMaquinaPage() {
            // Prevenir doble inicialización
            if (document.body.dataset.seleccionarMaquinaPageInit === 'true') return;

            console.log('🔍 Inicializando selección de máquina...');

            const maquinas = @json($maquinas);

            // Construir opciones del select
            let optionsHtml = '<option value="">-- Selecciona una máquina --</option>';
            maquinas.forEach(m => {
                optionsHtml += `<option value="${m.id}">${m.codigo} - ${m.nombre}</option>`;
            });

            Swal.fire({
                title: 'Selecciona tu máquina',
                html: `
                    <p style="color: #6b7280; margin-bottom: 1rem;">No tienes máquina asignada para hoy.<br>Por favor, selecciona en cuál vas a trabajar.</p>
                    <select id="maquinaSelect" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem;">
                        ${optionsHtml}
                    </select>
                `,
                icon: 'info',
                showCancelButton: false,
                confirmButtonText: 'Entrar',
                confirmButtonColor: '#3085d6',
                allowOutsideClick: false,
                allowEscapeKey: false,
                width: '400px',
                preConfirm: () => {
                    const maquinaId = document.getElementById('maquinaSelect').value;
                    if (!maquinaId) {
                        Swal.showValidationMessage('Debes seleccionar una máquina');
                        return false;
                    }
                    return maquinaId;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `/maquinas/${result.value}`;
                }
            });

            // Marcar como inicializado
            document.body.dataset.seleccionarMaquinaPageInit = 'true';
        }

        // Registrar en el sistema global
        window.pageInitializers = window.pageInitializers || [];
        window.pageInitializers.push(initSeleccionarMaquinaPage);

        // Configurar listeners
        document.addEventListener('livewire:navigated', initSeleccionarMaquinaPage);
        document.addEventListener('DOMContentLoaded', initSeleccionarMaquinaPage);

        // Limpiar flag antes de navegar
        document.addEventListener('livewire:navigating', () => {
            document.body.dataset.seleccionarMaquinaPageInit = 'false';
        });
    </script>
</x-app-layout>
