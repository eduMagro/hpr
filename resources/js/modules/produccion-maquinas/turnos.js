export function aplicarLineasTurnos(turnosActivos) {
    // Limpiar líneas anteriores
    document.querySelectorAll('.fc-timegrid-slot.turno-inicio').forEach(el => {
        el.classList.remove('turno-inicio');
    });

    // Aplicar líneas para cada turno activo
    turnosActivos.forEach(turno => {
        if (!turno.activo || !turno.hora_inicio) return;

        const horaInicio = turno.hora_inicio.substring(0, 5);
        const selector = `.fc-timegrid-slot[data-time="${horaInicio}:00"]`;
        const slots = document.querySelectorAll(selector);

        slots.forEach(slot => {
            slot.classList.add('turno-inicio');
        });
    });
}

export async function toggleTurno(turnoId, turnoNombre) {
    try {
        const response = await fetch('/turnos/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ turno_id: turnoId })
        });

        const data = await response.json();

        if (data.success) {
            const btn = document.querySelector(`button[data-turno-id="${turnoId}"]`);
            const icon = btn.querySelector('.turno-icon');

            if (data.activo) {
                btn.classList.remove('bg-gray-200', 'text-gray-600', 'border-gray-300');
                btn.classList.add('bg-green-500', 'text-white', 'border-green-600');
                btn.title = `Desactivar turno ${turnoNombre}`;
                icon.textContent = '✓';
            } else {
                btn.classList.remove('bg-green-500', 'text-white', 'border-green-600');
                btn.classList.add('bg-gray-200', 'text-gray-600', 'border-gray-300');
                btn.title = `Activar turno ${turnoNombre}`;
                icon.textContent = '✕';
            }

            // Reaplicar líneas de turnos
            if (window.aplicarLineasTurnos) {
                setTimeout(() => window.aplicarLineasTurnos(), 100);
            }
        }
    } catch (error) {
        console.error('Error al cambiar turno:', error);
    }
}
