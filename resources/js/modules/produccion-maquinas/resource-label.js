export function createResourceLabel(arg) {
    return {
        html: `
        <div class="flex flex-col gap-2 w-full py-1">
            <a href="/maquinas/${arg.resource.id}"
               wire:navigate
               class="text-blue-600 hover:text-blue-800 hover:underline font-semibold maquina-nombre"
               data-maquina-id="${arg.resource.id}"
               title="Ver detalles de ${arg.resource.title}">
                ${arg.resource.title}
            </a>
            <div class="flex gap-1 flex-wrap">
                <button onclick="abrirModalEstado(${arg.resource.id}, '${arg.resource.title}')"
                        class="text-[10px] px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors"
                        title="Cambiar estado de máquina">
                    Estado
                </button>
                <button onclick="abrirModalRedistribuir(${arg.resource.id}, '${arg.resource.title}')"
                        class="text-[10px] px-2 py-1 bg-orange-500 hover:bg-orange-600 text-white rounded transition-colors"
                        title="Redistribuir cola de trabajo">
                    Redistribuir
                </button>
            </div>
        </div>`
    };
}
