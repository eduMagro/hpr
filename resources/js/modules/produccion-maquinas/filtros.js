export function initFiltros(calendar) {
    const filtros = {
        cliente: document.getElementById('filtroCliente'),
        codCliente: document.getElementById('filtroCodCliente'),
        obra: document.getElementById('filtroObra'),
        codObra: document.getElementById('filtroCodObra'),
        codigoPlanilla: document.getElementById('filtroCodigoPlanilla'),
        fechaEntrega: document.getElementById('filtroFechaEntrega'),
        estado: document.getElementById('filtroEstado')
    };

    const btnLimpiar = document.getElementById('limpiarResaltado');

    // Debounce helper
    function debounce(fn, ms = 200) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn.apply(this, args), ms);
        };
    }

    // Función de filtrado
    const aplicarFiltros = debounce(() => {
        let filtrosActivos = 0;
        const eventos = document.querySelectorAll('.fc-event');

        eventos.forEach(evento => {
            const planillaData = {
                cliente: evento.dataset.cliente || '',
                codCliente: evento.dataset.codCliente || '',
                obra: evento.dataset.obra || '',
                codObra: evento.dataset.codObra || '',
                codigoPlanilla: evento.dataset.codigoPlanilla || '',
                fechaEntrega: evento.dataset.fechaEntrega || '',
                estado: evento.dataset.estado || ''
            };

            let cumpleFiltros = true;

            // Aplicar cada filtro
            if (filtros.cliente?.value && !planillaData.cliente.toLowerCase().includes(filtros.cliente.value.toLowerCase())) {
                cumpleFiltros = false;
            }
            if (filtros.codCliente?.value && !planillaData.codCliente.toLowerCase().includes(filtros.codCliente.value.toLowerCase())) {
                cumpleFiltros = false;
            }
            if (filtros.obra?.value && !planillaData.obra.toLowerCase().includes(filtros.obra.value.toLowerCase())) {
                cumpleFiltros = false;
            }
            if (filtros.codObra?.value && !planillaData.codObra.toLowerCase().includes(filtros.codObra.value.toLowerCase())) {
                cumpleFiltros = false;
            }
            if (filtros.codigoPlanilla?.value && !planillaData.codigoPlanilla.toLowerCase().includes(filtros.codigoPlanilla.value.toLowerCase())) {
                cumpleFiltros = false;
            }
            if (filtros.fechaEntrega?.value && planillaData.fechaEntrega !== filtros.fechaEntrega.value) {
                cumpleFiltros = false;
            }
            if (filtros.estado?.value && planillaData.estado !== filtros.estado.value) {
                cumpleFiltros = false;
            }

            // Aplicar estilos
            if (cumpleFiltros) {
                evento.classList.remove('opacity-30', 'grayscale');
            } else {
                evento.classList.add('opacity-30', 'grayscale');
            }
        });

        // Contar filtros activos
        Object.values(filtros).forEach(filtro => {
            if (filtro?.value) filtrosActivos++;
        });

        // Actualizar badge
        const badge = document.getElementById('filtrosActivosBadge');
        if (filtrosActivos > 0) {
            badge.textContent = filtrosActivos;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }, 200);

    // Añadir listeners
    Object.values(filtros).forEach(filtro => {
        filtro?.addEventListener('input', aplicarFiltros);
    });

    btnLimpiar?.addEventListener('click', () => {
        Object.values(filtros).forEach(filtro => {
            if (filtro) filtro.value = '';
        });
        aplicarFiltros();
    });
}

export function toggleFiltros() {
    const panel = document.getElementById('panelFiltros');
    const chevron = document.getElementById('filtrosChevron');

    if (panel.style.maxHeight === '0px' || panel.style.maxHeight === '') {
        panel.style.maxHeight = panel.scrollHeight + 'px';
        chevron.style.transform = 'rotate(180deg)';
    } else {
        panel.style.maxHeight = '0';
        chevron.style.transform = 'rotate(0deg)';
    }
}
