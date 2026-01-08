    <x-app-layout>
        <x-slot name="title">Etiquetas - {{ config('app.name') }}</x-slot>
        <div class="w-full p-4 sm:p-2">
            <!-- Tabla con formularios de búsqueda -->
            <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
            <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
                <table class="w-full min-w-[1200px] border border-gray-300 rounded-lg">
                    <thead class="bg-blue-500 text-white text-10">
                        <tr class="text-center text-xs uppercase">

                            <th class="p-2 border">{!! $ordenables['id'] ?? 'ID' !!}</th>
                            <th class="p-2 border">Codigo</th>
                            <th class="p-2 border">Codigo SubEtiqueta</th>
                            <th class="p-2 border">{!! $ordenables['codigo_planilla'] ?? 'Planilla' !!}</th>
                            <th class="p-2 border">{!! $ordenables['paquete'] ?? 'Paquete' !!}</th>
                            <th class="p-2 border">Op 1</th>
                            <th class="p-2 border">Op 2</th>
                            <th class="p-2 border">Ens 1</th>
                            <th class="p-2 border">Ens 2</th>
                            <th class="p-2 border">Sol 1</th>
                            <th class="p-2 border">Sol 2</th>
                            <th class="p-2 border">{!! $ordenables['numero_etiqueta'] ?? 'Número de Etiqueta' !!}</th>
                            <th class="p-2 border">{!! $ordenables['nombre'] ?? 'Nombre' !!}</th>
                            <th class="p-2 border">Marca</th>
                            <th class="p-2 border">{!! $ordenables['peso'] ?? 'Peso (kg)' !!}</th>
                            <th class="p-2 border">Inicio Fabricación</th>
                            <th class="p-2 border">Final Fabricación</th>
                            <th class="p-2 border">Inicio Ensamblado</th>
                            <th class="p-2 border">Final Ensamblado</th>
                            <th class="p-2 border">Inicio Soldadura</th>
                            <th class="p-2 border">Final Soldadura</th>
                            <th class="p-2 border">{!! $ordenables['estado'] ?? 'Estado' !!}</th>
                            <th class="p-2 border">Acciones</th>
                        </tr>

                        <tr class="text-center text-xs uppercase">
                            <form method="GET" action="{{ route('etiquetas.index') }}">

                                <th class="p-1 border">
                                    <x-tabla.input name="id" value="{{ request('id') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="codigo" value="{{ request('codigo') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="etiqueta_sub_id" value="{{ request('etiqueta_sub_id') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="codigo_planilla" value="{{ request('codigo_planilla') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="paquete" value="{{ request('paquete') }}" />
                                </th>
                                <th class="p-1 border"></th> {{-- Op 1 --}}
                                <th class="p-1 border"></th> {{-- Op 2 --}}
                                <th class="p-1 border"></th> {{-- Ens 1 --}}
                                <th class="p-1 border"></th> {{-- Ens 2 --}}
                                <th class="p-1 border"></th> {{-- Sol 1 --}}
                                <th class="p-1 border"></th> {{-- Sol 2 --}}
                                <th class="p-1 border">
                                    <x-tabla.input name="numero_etiqueta" value="{{ request('numero_etiqueta') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input name="nombre" value="{{ request('nombre') }}" />
                                </th>
                                <th class="p-1 border">

                                </th>
                                <th class="p-1 border"></th> {{-- Peso --}}
                                <th class="p-1 border">
                                    <x-tabla.input type="date" name="inicio_fabricacion"
                                        value="{{ request('inicio_fabricacion') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input type="date" name="final_fabricacion"
                                        value="{{ request('final_fabricacion') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input type="date" name="inicio_ensamblado"
                                        value="{{ request('inicio_ensamblado') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input type="date" name="final_ensamblado"
                                        value="{{ request('final_ensamblado') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input type="date" name="inicio_soldadura"
                                        value="{{ request('inicio_soldadura') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.input type="date" name="final_soldadura"
                                        value="{{ request('final_soldadura') }}" />
                                </th>
                                <th class="p-1 border">
                                    <x-tabla.select name="estado" :options="[
                                        'pendiente' => 'Pendiente',
                                        'fabricando' => 'Fabricando',
                                        'ensamblando' => 'Ensamblando',
                                        'soldando' => 'Soldando',
                                        'completada' => 'Completada',
                                    ]" :selected="request('estado')" wire:navigate empty="Todos" />
                                </th>
                                <x-tabla.botones-filtro ruta="etiquetas.index" />
                            </form>
                        </tr>
                    </thead>

                    <tbody class="text-gray-700 text-sm">
                        @forelse ($etiquetas as $etiqueta)
                            <tr tabindex="0" x-data="{
                                editando: false,
                                etiqueta: @js($etiqueta),
                                original: JSON.parse(JSON.stringify(@js($etiqueta)))
                            }"
                                @dblclick="if(!$event.target.closest('input')) {
                              if(!editando) {
                                editando = true;
                              } else {
                                etiqueta = JSON.parse(JSON.stringify(original));
                                editando = false;
                              }
                            }"
                                @keydown.enter.stop="guardarCambios(etiqueta); editando = false"
                                :class="{ 'bg-yellow-100': editando }"
                                class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">
                                <!-- ID (no editable) -->

                                <td class="p-2 text-center border">{{ $etiqueta->id }}</td>
                                <td class="p-2 text-center border">{{ $etiqueta->codigo }}</td>
                                <td class="p-2 text-center border">{{ $etiqueta->etiqueta_sub_id }}</td>

                                <!-- Planilla (no editable) -->
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->planilla_id)
                                        <a href="{{ route('planillas.index', ['planilla_id' => $etiqueta->planilla_id]) }}" wire:navigate
                                            class="text-blue-500 hover:underline">
                                            {{ $etiqueta->planilla->codigo_limpio }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="p-2 text-center border">
                                    @if (isset($etiqueta->paquete->codigo))
                                        <a href="{{ route('paquetes.index', [$etiqueta->paquete_id => $etiqueta->paquete->codigo]) }}" wire:navigate
                                            class="text-blue-500 hover:underline">
                                            {{ $etiqueta->paquete->codigo }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>

                                <!-- Opeario 1 (no editable) -->
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->operario1)
                                        <a href="{{ route('users.index', ['users_id' => $etiqueta->operario1]) }}" wire:navigate
                                            class="text-blue-500 hover:underline">
                                            {{ $etiqueta->operario1->name }}
                                            {{ $etiqueta->operario1->primer_apellido }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>

                                <!-- Operario 2 (no editable) -->
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->opeario2)
                                        <a href="{{ route('users.index', ['users_id' => $etiqueta->opeario2]) }}" wire:navigate
                                            class="text-blue-500 hover:underline">
                                            {{ $etiqueta->opeario2->name }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <!-- Ensamblador 1 (no editable) -->
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->ensamblador1)
                                        <a href="{{ route('users.index', ['users_id' => $etiqueta->ensamblador1]) }}" wire:navigate
                                            class="text-blue-500 hover:underline">
                                            {{ $etiqueta->ensamblador1->name }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>

                                <!-- Ensamblador 2 (no editable) -->
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->ensamblador2)
                                        <a href="{{ route('users.index', ['users_id' => $etiqueta->ensamblador2]) }}" wire:navigate
                                            class="text-blue-500 hover:underline">
                                            {{ $etiqueta->ensamblador2->name }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>

                                <!-- Soldador1 (no editable) -->
                                <td class="p-2 text-center border">{{ $etiqueta->soldador1->name ?? 'N/A' }}</td>

                                <!-- Soldador2 (no editable) -->
                                <td class="p-2 text-center border">{{ $etiqueta->soldador2->name ?? 'N/A' }}</td>

                                <!-- Número de Etiqueta (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.numero_etiqueta"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="etiqueta.numero_etiqueta"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Nombre (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.nombre"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="etiqueta.nombre"
                                        class="form-control form-control-sm" @click.stop>
                                </td>
                                <!-- Marca -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.marca"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="etiqueta.marca"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Peso (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.peso"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="etiqueta.peso"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Fecha Inicio (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_inicio"></span>
                                    </template>
                                    <input x-show="editando" type="date" x-model="etiqueta.fecha_inicio"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Fecha Finalización (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_finalizacion"></span>
                                    </template>
                                    <input x-show="editando" type="date" x-model="etiqueta.fecha_finalizacion"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Fecha Inicio Ensamblado (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_inicio_ensamblado"></span>
                                    </template>
                                    <input x-show="editando" type="date"
                                        x-model="etiqueta.fecha_inicio_ensamblado"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Fecha Finalización Ensamblado (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_finalizacion_ensamblado"></span>
                                    </template>
                                    <input x-show="editando" type="date"
                                        x-model="etiqueta.fecha_finalizacion_ensamblado"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Fecha Inicio Soldadura (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_inicio_soldadura"></span>
                                    </template>
                                    <input x-show="editando" type="date" x-model="etiqueta.fecha_inicio_soldadura"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Fecha Finalización Soldadura (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_finalizacion_soldadura"></span>
                                    </template>
                                    <input x-show="editando" type="date"
                                        x-model="etiqueta.fecha_finalizacion_soldadura"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Estado (editable mediante select) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span
                                            x-text="etiqueta.estado ? etiqueta.estado.charAt(0).toUpperCase() + etiqueta.estado.slice(1) : ''"></span>
                                    </template>
                                    <select x-show="editando" x-model="etiqueta.estado" class="form-select w-full"
                                        @click.stop>
                                        <option value="pendiente">Pendiente</option>
                                        <option value="fabricando">Fabricando</option>
                                        <option value="completada">Completada</option>
                                    </select>
                                </td>

                                <!-- Acciones (no editable) -->
                                <td class="px-2 py-2 border text-xs font-bold">
                                    <div class="flex items-center space-x-2 justify-center">
                                        {{-- Botones visibles solo en edición --}}
                                        <x-tabla.boton-guardar x-show="editando"
                                            @click="guardarCambios(etiqueta); editando = false" />
                                        <x-tabla.boton-cancelar-edicion x-show="editando" @click="editando = false" />

                                        {{-- Botones normales --}}
                                        <template x-if="!editando">
                                            <div class="flex items-center space-x-2">
                                                <x-tabla.boton-editar @click="editando = true" x-show="!editando" />
                                                <button @click="mostrar({{ $etiqueta->id }})" wire:navigate
                                                    class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center"
                                                    title="Ver">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>

                                                {{-- Eliminar --}}
                                                <x-tabla.boton-eliminar :action="route('etiquetas.destroy', $etiqueta->id)" />
                                            </div>
                                        </template>
                                    </div>
                                </td>


                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="text-center py-4 text-gray-500">No hay etiquetas registradas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-tabla.paginacion :paginador="$etiquetas" />

            <!-- Modal estilo etiqueta-máquina -->
            <div id="modalEtiqueta"
                class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">

                <!-- 📐 Marco blanco exterior -->
                <div class="relative bg-white p-1 rounded-lg">
                    <button id="modalPrint" onclick="imprimirEtiquetaModal()"
                        class="absolute top-2 right-10 text-blue-800 hover:text-blue-900 no-print">
                        🖨️
                    </button>
                    <!-- Botón de cierre en el marco -->
                    <button id="modalClose" onclick="cerrarModal()" aria-label="Cerrar"
                        class="absolute -top-3 -right-3 bg-white border border-black
                       rounded-full w-7 h-7 flex items-center justify-center
                       text-xl leading-none hover:bg-red-100">
                        &times;
                    </button>

                    <!-- Caja naranja real del modal -->
                    <div id="modalEtiquetaBox" style="background-color:#fe7f09; border:1px solid black;"
                        class="proceso shadow-xl w-full max-w-3xl rounded-lg overflow-y-auto max-h-[90vh]">

                        <!-- Contenido dinámico -->
                        <div id="modalContent" class="p-2"></div>
                    </div>
                </div>
            </div>
            <script>
                window.etiquetasConElementos = @json($etiquetasJson);
                window.etiquetaModalActual = null; // Guardar la etiqueta actual del modal
            </script>
            <script>
                function mostrar(etiquetaId) {
                    console.log('mostrar() llamada con etiquetaId:', etiquetaId);
                    console.log('etiquetasConElementos:', window.etiquetasConElementos);

                    const datos = window.etiquetasConElementos[etiquetaId];
                    console.log('datos encontrados:', datos);

                    if (!datos) {
                        console.warn('No se encontraron datos para etiquetaId:', etiquetaId);
                        return;
                    }

                    // Guardar referencia para impresión
                    window.etiquetaModalActual = { id: etiquetaId, datos: datos };
                    console.log('etiquetaModalActual guardado:', window.etiquetaModalActual);

                    const subId = datos.etiqueta_sub_id ?? 'N/A';
                    const nombre = datos.nombre ?? 'Sin nombre';
                    const peso = datos.peso_kg ?? 'N/A';
                    const cliente = datos.planilla?.cliente?.empresa ?? 'Sin cliente';
                    const obra = datos.planilla?.obra?.obra ?? 'Sin obra';
                    const planillaCod = datos.planilla?.codigo_limpio ?? 'N/A';
                    const seccion = datos.planilla?.seccion ?? '';
                    const etiquetaIdVisual = datos.id ?? 'N/A';

                    const html = `
        <div class="text-lg font-semibold">${obra} – ${cliente}</div>
        <div class="text-md mb-2">${planillaCod} – S:${seccion}</div>
        <h3 class="text-lg font-semibold text-black">
            ${subId} ${nombre} – Cal:B500SD – ${peso} kg
        </h3>
        <div class="border-t border-black" style="min-height: 150px;">
            <div id="contenedor-svg-${etiquetaId}" class="w-full" style="height: 150px;"></div>
        </div>
    `;

                    const content = document.getElementById('modalContent');
                    content.innerHTML = html;

                    const modal = document.getElementById('modalEtiqueta');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');

                    // Verificar si hay elementos con dimensiones
                    const elementos = datos.elementos || [];
                    console.log('elementos:', elementos);

                    if (elementos.length === 0 || !elementos.some(e => e.dimensiones)) {
                        console.warn('No hay elementos con dimensiones para renderizar');
                        document.getElementById(`contenedor-svg-${etiquetaId}`).innerHTML =
                            '<p class="text-center text-gray-500 py-4">Sin elementos para mostrar</p>';
                        return;
                    }

                    // Esperar a que renderizarGrupoSVG esté disponible y renderizar
                    const grupoData = {
                        id: etiquetaId,
                        etiqueta: {
                            id: etiquetaId,
                            etiqueta_sub_id: subId,
                            nombre: nombre,
                            peso_kg: peso,
                        },
                        elementos: elementos,
                    };

                    function intentarRenderizar(intentos = 0) {
                        console.log('Intentando renderizar, intento:', intentos);
                        if (typeof window.renderizarGrupoSVG === 'function') {
                            console.log('Llamando a renderizarGrupoSVG con:', grupoData);
                            window.renderizarGrupoSVG(grupoData, etiquetaId);
                        } else if (intentos < 20) {
                            setTimeout(() => intentarRenderizar(intentos + 1), 50);
                        } else {
                            console.warn('renderizarGrupoSVG no disponible después de esperar');
                        }
                    }
                    setTimeout(() => intentarRenderizar(), 50);
                }


                /* ----------- Cierre del modal ----------- */
                document.getElementById('modalClose').addEventListener('click', cerrarModal);
                document.getElementById('modalEtiqueta').addEventListener('click', e => {
                    if (e.target === e.currentTarget) cerrarModal(); // clic fuera del cuadro
                });

                function cerrarModal() {
                    const modal = document.getElementById('modalEtiqueta');
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            </script>
            <script src="{{ asset('js/maquinaJS/canvasMaquina.js') }}" defer></script>
            <script>
                // Función para imprimir desde el modal
                function imprimirEtiquetaModal() {
                    if (!window.etiquetaModalActual) return alert('No hay etiqueta seleccionada');
                    imprimirEtiqueta(window.etiquetaModalActual.datos.etiqueta_sub_id);
                }

                async function imprimirEtiqueta(etiquetaSubId) {
                    const svgElement = document.querySelector('#modalContent svg');
                    if (!svgElement) return alert('SVG no encontrado');

                    /* --- convierte SVG en imagen HD --- */
                    let svgImg = null;
                    try {
                        const svgClone = svgElement.cloneNode(true);
                        const bbox = svgElement.getBoundingClientRect();
                        const width = bbox.width || 600;
                        const height = bbox.height || 150;

                        svgClone.setAttribute('width', width);
                        svgClone.setAttribute('height', height);
                        if (!svgClone.getAttribute('viewBox')) {
                            svgClone.setAttribute('viewBox', `0 0 ${width} ${height}`);
                        }

                        // Fondo blanco
                        const bgRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                        bgRect.setAttribute('x', '0');
                        bgRect.setAttribute('y', '0');
                        bgRect.setAttribute('width', width);
                        bgRect.setAttribute('height', height);
                        bgRect.setAttribute('fill', '#ffffff');
                        svgClone.insertBefore(bgRect, svgClone.firstChild);

                        const svgData = new XMLSerializer().serializeToString(svgClone);
                        const svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
                        const svgUrl = URL.createObjectURL(svgBlob);

                        svgImg = await new Promise((resolve) => {
                            const img = new Image();
                            img.onload = () => {
                                const scale = 4;
                                const canvas = document.createElement('canvas');
                                canvas.width = width * scale;
                                canvas.height = height * scale;
                                const ctx = canvas.getContext('2d');
                                ctx.imageSmoothingEnabled = true;
                                ctx.imageSmoothingQuality = 'high';
                                ctx.scale(scale, scale);
                                ctx.fillStyle = '#ffffff';
                                ctx.fillRect(0, 0, width, height);
                                ctx.drawImage(img, 0, 0, width, height);
                                URL.revokeObjectURL(svgUrl);
                                resolve(canvas.toDataURL('image/png', 1.0));
                            };
                            img.onerror = () => {
                                URL.revokeObjectURL(svgUrl);
                                resolve(null);
                            };
                            img.src = svgUrl;
                        });
                    } catch (e) {
                        console.warn('Error al convertir SVG:', e);
                    }

                    if (!svgImg) return alert('Error al procesar la imagen');

                    /* --- clona contenido del modal y sustituye SVG --- */
                    const clone = document.getElementById('modalContent').cloneNode(true);
                    clone.classList.add('etiqueta-print');
                    clone.querySelectorAll('.no-print').forEach(el => el.remove());

                    const svgClone = clone.querySelector('svg');
                    const svgContainer = clone.querySelector('[id^="contenedor-svg-"]') || clone.querySelector('div[style*="min-height"]');
                    const img = new Image();
                    img.src = svgImg;
                    img.style.width = '100%';
                    img.style.height = 'auto';
                    if (svgClone) svgClone.remove();
                    if (svgContainer) {
                        svgContainer.innerHTML = '';
                        svgContainer.appendChild(img);
                    }

                    /* --- genera QR y lo añade --- */
                    const tempQR = document.createElement('div');
                    document.body.appendChild(tempQR);
                    new QRCode(tempQR, {
                        text: etiquetaSubId,
                        width: 60,
                        height: 60
                    });

                    setTimeout(() => {
                        const qrImg = tempQR.querySelector('img');
                        const qrBox = document.createElement('div');
                        qrBox.className = 'qr-print';
                        qrBox.appendChild(qrImg);
                        clone.insertBefore(qrBox, clone.firstChild);

                        /* --- abre ventana A6 --- */
                        const w = window.open('', '_blank');
                        const style = `
<style>
@page { size: A6 landscape; margin: 0; }
body { margin:0; font-family:Arial,sans-serif; }
.etiqueta-print{
    width: 200mm;
    height: 100mm;
    background:#fe7f09; border:2px solid #000;
    padding:4mm; box-sizing:border-box; position:relative;
}
.etiqueta-print img{ max-width:100%; height:auto; display:block; margin-top:6mm; }
.qr-print{
    position:absolute; top:8mm; right:8mm;
    width:60px; height:60px; border:2px solid #000; padding:0; background:#fff;
}
</style>`;

                        w.document.write(`
<html><head><title>Etiqueta ${etiquetaSubId}</title>${style}</head>
<body>${clone.outerHTML}
<script>
  window.onload = () => { window.print(); setTimeout(()=>window.close(),800); };
<\/script>
</body></html>
        `);
                        w.document.close();
                        tempQR.remove();
                    }, 250);
                }
            </script>
            <script>
                function guardarCambios(etiqueta) {

                    fetch(`/etiquetas/${etiqueta.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(etiqueta)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {

                                window.location.reload(); // Recarga la página tras el mensaje
                            } else {
                                let errorMsg =
                                    data.message || "Ha ocurrido un error inesperado.";
                                // Si existen errores de validación, concatenarlos
                                if (data.errors) {
                                    errorMsg = Object.values(data.errors).flat().join(
                                        "<br>"); // O puedes usar '\n' para saltos de línea
                                }
                                Swal.fire({
                                    icon: "error",
                                    title: "Error al actualizar",
                                    html: errorMsg,
                                    confirmButtonText: "OK",
                                    showCancelButton: true,
                                    cancelButtonText: "Reportar Error"
                                }).then((result) => {
                                    if (result.dismiss === Swal.DismissReason.cancel) {
                                        notificarProgramador(errorMsg);
                                    }
                                }).then(() => {
                                    window.location.reload(); // Recarga la página tras el mensaje
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: "error",
                                title: "Error de conexión",
                                text: "No se pudo actualizar la etiqueta. Inténtalo nuevamente.",
                                confirmButtonText: "OK"
                            });
                        });
                }
            </script>
    </x-app-layout>
