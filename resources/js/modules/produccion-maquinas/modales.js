// Gestión de modales para cambiar estado y redistribuir
import Swal from 'sweetalert2';

let maquinaSeleccionada = null;

// Modal cambiar estado
window.abrirModalEstado = function(maquinaId, nombreMaquina) {
    maquinaSeleccionada = maquinaId;
    document.getElementById('nombreMaquinaEstado').textContent = nombreMaquina;
    document.getElementById('modalEstado').classList.remove('hidden');
    document.getElementById('modalEstado').classList.add('flex');
};

window.cerrarModalEstado = function() {
    document.getElementById('modalEstado').classList.add('hidden');
    document.getElementById('modalEstado').classList.remove('flex');
    maquinaSeleccionada = null;
};

window.cambiarEstado = async function(nuevoEstado) {
    if (!maquinaSeleccionada) return;

    try {
        const response = await fetch(`/maquinas/${maquinaSeleccionada}/cambiar-estado`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ estado: nuevoEstado })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Estado actualizado',
                text: `La máquina ahora está en estado: ${nuevoEstado}`,
                timer: 2000,
                showConfirmButton: false
            });

            // Recargar calendario para reflejar cambios
            if (window.calendar) {
                window.calendar.refetchResources();
            }

            cerrarModalEstado();
        }
    } catch (error) {
        console.error('Error al cambiar estado:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo cambiar el estado de la máquina'
        });
    }
};

// Modal redistribuir
window.abrirModalRedistribuir = function(maquinaId, nombreMaquina) {
    maquinaSeleccionada = maquinaId;
    document.getElementById('nombreMaquinaRedistribuir').textContent = nombreMaquina;
    document.getElementById('modalRedistribuir').classList.remove('hidden');
    document.getElementById('modalRedistribuir').classList.add('flex');
};

window.cerrarModalRedistribuir = function() {
    document.getElementById('modalRedistribuir').classList.add('hidden');
    document.getElementById('modalRedistribuir').classList.remove('flex');
    maquinaSeleccionada = null;
};

window.redistribuir = async function(tipo) {
    if (!maquinaSeleccionada) return;

    try {
        const response = await fetch(`/maquinas/${maquinaSeleccionada}/redistribuir`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ tipo: tipo })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Redistribución completada',
                text: data.message,
                timer: 3000
            });

            // Recargar calendario
            if (window.calendar) {
                window.calendar.refetchEvents();
            }

            cerrarModalRedistribuir();
        }
    } catch (error) {
        console.error('Error al redistribuir:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo redistribuir la cola de trabajo'
        });
    }
};

// Modal optimizar
window.abrirModalOptimizar = function() {
    Swal.fire({
        title: 'Optimizar planillas',
        text: '¿Deseas optimizar las planillas con retraso?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, optimizar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            // Aquí iría la lógica de optimización
            Swal.fire({
                icon: 'info',
                title: 'Función en desarrollo',
                text: 'La optimización de planillas estará disponible próximamente'
            });
        }
    });
};

// Modal balanceo - La implementación real está en maquinas.blade.php
// NO definir aquí para evitar sobrescribir la versión completa del blade
