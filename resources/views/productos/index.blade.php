<x-app-layout>
    <x-slot name="title">Materiales - {{ config('app.name') }}</x-slot>

    @include('components.maquinas.modales.grua.modales-grua', [
        'maquinasDisponibles' => $maquinasDisponibles,
        'ubicacionesPorSector' => $ubicacionesPorSector ?? collect(),
        'sectores' => $sectores ?? [],
        'sectorPorDefecto' => $sectorPorDefecto ?? null,
    ])

    <div class="w-full px-6 py-4">
        <!-- Botones superiores -->
        <div class="mb-6 flex flex-wrap justify-center gap-3">

            <a href="{{ route('entradas.create') }}"
                class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-2.5 px-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 hidden md:inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                        clip-rule="evenodd" />
                </svg>
                Nuevo Producto
            </a>

            <a href="{{ route('coladas.index') }}"
                class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold py-2.5 px-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 hidden md:inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                    <path fill-rule="evenodd"
                        d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"
                        clip-rule="evenodd" />
                </svg>
                Gestión de Coladas
            </a>

            <button onclick="abrirModalImprimir()"
                class="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-semibold py-2.5 px-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 hidden md:inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z"
                        clip-rule="evenodd" />
                    <path
                        d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM16 13a1 1 0 100 2h1a1 1 0 100-2h-1z" />
                </svg>
                Generar e Imprimir QR
            </button>
        </div>

        <!-- 🖥️ Tabla solo en pantallas medianas o grandes -->
        <div class="hidden md:block">

            <!-- Modal Generar e Imprimir QR -->
            <div id="modalImprimirQR"
                class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
                    <h2 class="text-xl font-semibold mb-4">Generar e Imprimir QR</h2>

                    <div class="space-y-4">
                        <div>
                            <label for="cantidadImprimir" class="block text-sm font-medium text-gray-700">Cantidad a
                                generar</label>
                            <input type="number" id="cantidadImprimir" value="10" min="1"
                                class="w-full p-2 border border-gray-300 rounded-lg" required>
                        </div>

                        <div id="estadoImpresion" class="hidden">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <p class="text-sm text-blue-800" id="mensajeEstado"></p>
                            </div>
                        </div>

                        <div class="flex justify-end pt-2 space-x-2">
                            <p class="text-xs text-gray-500 mt-2">
                                Esta operacion genera codigos e imprime automaticamente las etiquetas QR.
                            </p>

                            <button type="button" onclick="cerrarModalImprimir()"
                                class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                                Cancelar
                            </button>
                            <button type="button" onclick="generarEImprimir()" id="btnImprimir"
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                Generar e Imprimir
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Modal Instalar Servicio de Impresion -->
            <div id="modalInstalarServicio"
                class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg relative">
                    <div class="flex items-center mb-4">
                        <div class="bg-yellow-100 rounded-full p-2 mr-3">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                </path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800">Servicio de Impresion No Detectado</h2>
                    </div>

                    <div class="space-y-4">
                        <p class="text-gray-600">
                            El servicio de impresion P-Touch no esta corriendo en este equipo.
                            Sigue estos pasos para configurarlo:
                        </p>

                        <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                            <div class="flex items-start">
                                <span
                                    class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 flex-shrink-0">1</span>
                                <div>
                                    <p class="font-medium text-gray-800">Descarga el instalador</p>
                                    <p class="text-sm text-gray-500">Haz clic en el boton de abajo para descargar</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <span
                                    class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 flex-shrink-0">2</span>
                                <div>
                                    <p class="font-medium text-gray-800">Ejecuta el archivo descargado</p>
                                    <p class="text-sm text-gray-500">Doble clic en <code
                                            class="bg-gray-200 px-1 rounded">setup_and_start.bat</code></p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <span
                                    class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 flex-shrink-0">3</span>
                                <div>
                                    <p class="font-medium text-gray-800">Manten la ventana abierta</p>
                                    <p class="text-sm text-gray-500">El servicio debe estar corriendo para imprimir</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <p class="text-sm text-blue-800">
                                <strong>Requisito:</strong> Necesitas tener Python instalado.
                                Si no lo tienes, el instalador te guiara.
                            </p>
                        </div>

                        <div class="flex justify-between pt-2">
                            <button type="button" onclick="cerrarModalInstalar()"
                                class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                                Cancelar
                            </button>
                            <div class="space-x-2">
                                <button type="button" onclick="verificarServicioYContinuar()"
                                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                    Ya lo tengo corriendo
                                </button>
                                <a href="{{ route('print-service.download') }}"
                                    class="inline-block px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                    Descargar Instalador
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Usar window para evitar errores de re-declaración con Livewire prefetch
                if (typeof window.PRINT_SERVICE_URL === 'undefined') window.PRINT_SERVICE_URL = 'http://localhost:8765';

                // Verificar si el servicio de impresion esta corriendo
                async function verificarServicioImpresion() {
                    try {
                        const controller = new AbortController();
                        const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 segundos

                        const response = await fetch(window.PRINT_SERVICE_URL, {
                            method: 'GET',
                            mode: 'cors',
                            signal: controller.signal
                        });

                        clearTimeout(timeoutId);
                        return response.ok;
                    } catch (error) {
                        console.log('Servicio de impresion no disponible:', error.message);
                        return false;
                    }
                }

                // Abrir modal de impresion (verifica servicio primero)
                async function abrirModalImprimir() {
                    const servicioDisponible = await verificarServicioImpresion();

                    if (!servicioDisponible) {
                        abrirModalInstalar();
                        return;
                    }

                    const modal = document.getElementById('modalImprimirQR');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.getElementById('estadoImpresion').classList.add('hidden');
                }

                function cerrarModalImprimir() {
                    const modal = document.getElementById('modalImprimirQR');
                    modal.classList.remove('flex');
                    modal.classList.add('hidden');
                }

                // Funciones para modal de instalacion
                function abrirModalInstalar() {
                    const modal = document.getElementById('modalInstalarServicio');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }

                function cerrarModalInstalar() {
                    const modal = document.getElementById('modalInstalarServicio');
                    modal.classList.remove('flex');
                    modal.classList.add('hidden');
                }

                async function verificarServicioYContinuar() {
                    const servicioDisponible = await verificarServicioImpresion();

                    if (servicioDisponible) {
                        cerrarModalInstalar();
                        // Abrir el modal de impresion
                        const modal = document.getElementById('modalImprimirQR');
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        document.getElementById('estadoImpresion').classList.add('hidden');
                    } else {
                        alert('El servicio aun no esta disponible. Asegurate de que este corriendo y vuelve a intentarlo.');
                    }
                }

                function mostrarEstado(mensaje, tipo = 'info') {
                    const estadoDiv = document.getElementById('estadoImpresion');
                    const mensajeP = document.getElementById('mensajeEstado');

                    estadoDiv.classList.remove('hidden', 'bg-blue-50', 'border-blue-200', 'bg-green-50', 'border-green-200',
                        'bg-red-50', 'border-red-200');
                    mensajeP.classList.remove('text-blue-800', 'text-green-800', 'text-red-800');

                    if (tipo === 'success') {
                        estadoDiv.classList.add('bg-green-50', 'border-green-200');
                        mensajeP.classList.add('text-green-800');
                    } else if (tipo === 'error') {
                        estadoDiv.classList.add('bg-red-50', 'border-red-200');
                        mensajeP.classList.add('text-red-800');
                    } else {
                        estadoDiv.classList.add('bg-blue-50', 'border-blue-200');
                        mensajeP.classList.add('text-blue-800');
                    }

                    mensajeP.textContent = mensaje;
                }

                async function generarEImprimir() {
                    const cantidad = document.getElementById('cantidadImprimir').value;
                    const btnImprimir = document.getElementById('btnImprimir');

                    if (!cantidad || cantidad < 1) {
                        alert('Por favor ingresa una cantidad valida');
                        return;
                    }

                    try {
                        btnImprimir.disabled = true;
                        btnImprimir.textContent = 'Procesando...';
                        mostrarEstado('Generando codigos...', 'info');

                        // 1. Generar codigos en el servidor
                        const response = await fetch('{{ route('productos.generar.datos') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                cantidad: cantidad
                            })
                        });

                        if (!response.ok) {
                            throw new Error(`Error generando codigos en el servidor (status: ${response.status})`);
                        }

                        const data = await response.json();

                        if (!data.success) {
                            throw new Error(data.error || 'Error desconocido generando codigos');
                        }

                        mostrarEstado(`${data.cantidad} codigos generados. Enviando a impresora...`, 'info');
                        console.log('Enviando a impresora:', data.codigos);

                        // 2. Enviar a servicio local de impresion con timeout de 30 segundos
                        let printResponse;
                        try {
                            const printController = new AbortController();
                            const printTimeout = setTimeout(() => printController.abort(), 30000);

                            printResponse = await fetch(window.PRINT_SERVICE_URL + '/print', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    codigos: data.codigos
                                }),
                                mode: 'cors',
                                signal: printController.signal
                            });

                            clearTimeout(printTimeout);
                            console.log('Respuesta recibida:', printResponse.status);
                        } catch (fetchError) {
                            console.error('Error en fetch a /print:', fetchError);
                            // Si falla la conexion, mostrar modal de instalacion
                            if (fetchError.name === 'AbortError') {
                                mostrarEstado('Timeout: El servicio tardó demasiado en responder', 'error');
                                return;
                            }
                            cerrarModalImprimir();
                            abrirModalInstalar();
                            return;
                        }

                        if (!printResponse.ok) {
                            const errorText = await printResponse.text();
                            throw new Error(`Error en servicio de impresion (status: ${printResponse.status}): ${errorText}`);
                        }

                        const printData = await printResponse.json();

                        if (!printData.success) {
                            throw new Error(printData.error || 'Error desconocido en la impresion');
                        }

                        // 3. Exito
                        mostrarEstado(`Completado! ${printData.cantidad} etiquetas enviadas a imprimir.`, 'success');

                        setTimeout(() => {
                            cerrarModalImprimir();
                            location.reload();
                        }, 2000);

                    } catch (error) {
                        console.error('Error en impresion:', error);
                        let mensajeError = error.message;

                        if (error.message.includes('Failed to fetch')) {
                            cerrarModalImprimir();
                            abrirModalInstalar();
                            return;
                        }

                        mostrarEstado(mensajeError, 'error');
                    } finally {
                        btnImprimir.disabled = false;
                        btnImprimir.textContent = 'Generar e Imprimir';
                    }
                }

                // Cerrar modales con ESC
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        cerrarModalImprimir();
                        cerrarModalInstalar();
                    }
                });

                // Cerrar si se hace clic fuera
                window.addEventListener('click', function(event) {
                    const modalImprimir = document.getElementById('modalImprimirQR');
                    const modalInstalar = document.getElementById('modalInstalarServicio');
                    if (event.target === modalImprimir) {
                        cerrarModalImprimir();
                    }
                    if (event.target === modalInstalar) {
                        cerrarModalInstalar();
                    }
                });
            </script>

            <!-- Catálogo de Productos Base -->
            <div x-data="{ open: false }" class="mb-6">
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-lg shadow-md border border-gray-200 dark:border-gray-600 p-4">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="bg-blue-600 p-2 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">Catálogo de Productos Base</h2>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Listado de productos disponibles</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="abrirModalProductoBase()"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2 shadow-md hover:shadow-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Añadir
                            </button>
                            <button @click="open = !open"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2 shadow-md hover:shadow-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                    :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" style="transition: transform 0.2s;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                                <span x-show="!open">Mostrar</span>
                                <span x-show="open">Ocultar</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div x-show="open" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="mt-3 bg-white dark:bg-gray-800 shadow-lg rounded-lg border border-gray-200 dark:border-gray-600 p-4">
                    @if ($productosBase->count() > 0)
                        @php
                            $barras = $productosBase->where('tipo', 'barra')->sortBy('diametro');
                            $barrasAgrupadas = $barras->groupBy('diametro');
                            $encarretados = $productosBase->where('tipo', 'encarretado')->sortBy('diametro');
                        @endphp

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {{-- Barras --}}
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 border border-blue-100 dark:border-blue-800">
                                <h4
                                    class="text-sm font-bold text-blue-800 dark:text-blue-300 mb-3 flex items-center gap-2 border-b border-blue-200 dark:border-blue-800 pb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 12H4" />
                                    </svg>
                                    BARRAS
                                    <span
                                        class="ml-auto text-xs font-normal text-blue-600 dark:text-blue-400">({{ $barras->count() }})</span>
                                </h4>
                                @if ($barras->count() > 0)
                                    <div class="space-y-2">
                                        @foreach ($barrasAgrupadas as $diametro => $barrasPorDiametro)
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="bg-blue-600 dark:bg-blue-700 text-white text-xs font-bold px-2 py-1 rounded min-w-[50px] text-center">
                                                    Ø{{ $diametro }}
                                                </span>
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach ($barrasPorDiametro->sortBy('longitud') as $barra)
                                                        <span
                                                            class="bg-white dark:bg-gray-700 border border-blue-300 dark:border-blue-600 text-blue-700 dark:text-blue-200 text-xs px-2 py-0.5 rounded cursor-pointer hover:bg-red-100 dark:hover:bg-red-900/50 hover:border-red-400 dark:hover:border-red-500 hover:text-red-700 dark:hover:text-red-300 transition-colors"
                                                            title="ID: {{ $barra->id }} - Click para eliminar"
                                                            onclick="eliminarProductoBase({{ $barra->id }}, 'Barra Ø{{ $diametro }} {{ $barra->longitud ? $barra->longitud . 'm' : 'S/L' }}')">
                                                            {{ $barra->longitud ? $barra->longitud . 'm' : 'S/L' }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-blue-400 dark:text-blue-300 text-xs text-center py-2">Sin barras registradas</p>
                                @endif
                            </div>

                            {{-- Encarretados --}}
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 border border-green-100 dark:border-green-800">
                                <h4
                                    class="text-sm font-bold text-green-800 dark:text-green-300 mb-3 flex items-center gap-2 border-b border-green-200 dark:border-green-800 pb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    ENCARRETADOS
                                    <span
                                        class="ml-auto text-xs font-normal text-green-600 dark:text-green-400">({{ $encarretados->count() }})</span>
                                </h4>
                                @if ($encarretados->count() > 0)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($encarretados as $enc)
                                            <span
                                                class="bg-green-600 dark:bg-green-700 text-white text-xs font-bold px-3 py-1.5 rounded cursor-pointer hover:bg-red-600 dark:hover:bg-red-500 transition-colors"
                                                title="ID: {{ $enc->id }} - Click para eliminar"
                                                onclick="eliminarProductoBase({{ $enc->id }}, 'Encarretado Ø{{ $enc->diametro }}')">
                                                Ø{{ $enc->diametro }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-green-400 dark:text-green-300 text-xs text-center py-2">Sin encarretados registrados</p>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">No hay productos base registrados</p>
                    @endif
                </div>
            </div>

            @livewire('productos-table')
        </div>

        <!-- 📱 Tarjetas solo en pantallas pequeñas -->
        <div class="block md:hidden">
            <!-- Buscador por código -->
            {{-- <div class="mb-4">
                <form method="GET" action="{{ route('productos.index') }}"
            class="flex flex-col sm:flex-row gap-2 items-center">
            <input type="text" name="codigo" placeholder="Buscar por código..."
                value="{{ request('codigo') }}"
                class="w-full sm:w-64 px-4 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm font-semibold">
                Buscar
            </button>
            @if (request('codigo'))
            <a href="{{ route('productos.index') }}" wire:navigate
                class="text-sm text-gray-600 underline hover:text-gray-800">Limpiar</a> 
            @endif
            </form>
        </div> --}}
            <!-- Filtros compactos en una fila -->
            <div class="mb-4">
                <form method="GET" action="{{ route('productos.index') }}" class="flex gap-2 items-center">
                    <input type="text" name="codigo" placeholder="Código QR..." value="{{ request('codigo') }}"
                        class="flex-1 px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onchange="this.form.submit()">

                    <select name="producto_base_id"
                        class="flex-1 px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onchange="this.form.submit()">
                        <option value="" {{ !request('producto_base_id') ? 'selected' : '' }}>Producto base
                        </option>
                        @foreach ($productosBase as $producto)
                            <option value="{{ $producto->id }}"
                                {{ request('producto_base_id') == $producto->id ? 'selected' : '' }}>
                                {{ strtoupper($producto->tipo) }}
                                Ø{{ $producto->diametro }}{{ $producto->longitud ? ' ' . $producto->longitud . 'm' : '' }}
                            </option>
                        @endforeach
                    </select>

                    @if (request('codigo') || request('producto_base_id'))
                        <a href="{{ route('productos.index') }}"
                            class="px-2 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded flex items-center justify-center"
                            title="Restablecer filtros">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                            </svg>
                        </a>
                    @endif
                </form>
            </div>

            <!-- Modo Tarjetas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($registrosProductos as $producto)
                    <div class="bg-white shadow-md rounded-lg p-4">
                        <h3 class="font-bold text-lg text-gray-700">ID: {{ $producto->id }}</h3>
                        <h3 class="font-bold text-lg text-gray-700">Código: {{ $producto->codigo }}</h3>
                        <p><strong>Fabricante:</strong> {{ $producto->fabricante->nombre ?? '—' }}</p>
                        <p>
                            <strong>Características:</strong>
                            {{ strtoupper($producto->productoBase->tipo ?? '—') }}
                            |
                            Ø{{ $producto->productoBase->diametro ?? '—' }}
                            {{ $producto->productoBase->longitud ? '| ' . $producto->productoBase->longitud . ' m' : '' }}
                        </p>

                        <p><strong>Nº Colada:</strong> {{ $producto->n_colada }}</p>
                        <p><strong>Nº Paquete:</strong> {{ $producto->n_paquete }}</p>
                        <p><strong>Peso Inicial:</strong> {{ $producto->peso_inicial }} kg</p>
                        <p><strong>Peso Stock:</strong> {{ $producto->peso_stock }} kg</p>
                        <p><strong>Estado:</strong> {{ $producto->estado }}</p>
                        <hr class="m-2 border-gray-300">

                        @if (isset($producto->ubicacion->nombre))
                            <p class="font-bold text-lg text-gray-800 break-words">{{ $producto->ubicacion->nombre }}
                            </p>
                        @elseif (isset($producto->maquina->nombre))
                            <p class="font-bold text-lg text-gray-800 break-words">{{ $producto->maquina->nombre }}
                            </p>
                        @else
                            <p class="font-bold text-lg text-gray-800 break-words">No está ubicada</p>
                        @endif

                        <p class="text-gray-600 mt-2">{{ $producto->created_at->format('d/m/Y H:i') }}</p>

                        <hr class="my-2 border-gray-300">

                        @php
                            $usuario = auth()->user();
                            $esOficina = $usuario->rol === 'oficina';
                            $esGruista = $usuario->rol !== 'oficina' && $usuario->maquina?->tipo === 'grua';
                        @endphp

                        @if ($esOficina || $esGruista)
                            <div class="flex flex-wrap gap-2 mt-4 w-full">
                                <a href="{{ route('productos.show', $producto->id) }}" wire:navigate
                                    class="flex-1 bg-blue-500 hover:bg-blue-600 text-white text-center text-sm font-semibold py-2 px-2 rounded shadow">
                                    Ver
                                </a>
                                <a href="{{ route('productos.edit', $producto->id) }}" wire:navigate
                                    class="flex-1 bg-blue-400 hover:bg-blue-500 text-white text-center text-sm font-semibold py-2 px-2 rounded shadow">
                                    Editar
                                </a>
                                <button onclick="abrirModalMovimientoLibre('{{ $producto->codigo }}')"
                                    class="flex-1 bg-green-500 hover:bg-green-600 text-white text-center text-sm font-semibold py-2 px-2 rounded shadow">
                                    Mover
                                </button>

                                <button type="button"
                                    data-consumir="{{ route('productos.editarConsumir', $producto->id) }}"
                                    class="btn-consumir flex-1 bg-red-500 hover:bg-red-600 text-white text-center text-sm font-semibold py-2 px-2 rounded shadow">
                                    Consumir
                                </button>

                                <form action="{{ route('productos.destroy', $producto->id) }}" method="POST"
                                    class="form-eliminar flex-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="btn-eliminar w-full bg-gray-500 hover:bg-gray-600 text-white text-sm font-semibold py-2 px-2 rounded shadow">
                                        Eliminar
                                    </button>
                                </form>

                            </div>
                        @endif

                    </div>
                @empty
                    <div class="col-span-3 text-center py-4">No hay productos disponibles.</div>
                @endforelse
            </div>
        </div>

        <script>
            function initProductosPage() {
                // Prevenir doble inicialización
                if (document.body.dataset.productosPageInit === 'true') return;

                console.log('🔍 Inicializando página de productos...');

                // Delegación de eventos para botones "Consumir"
                document.body.addEventListener('click', async (e) => {
                    const btn = e.target.closest('.btn-consumir');
                    if (!btn) return;

                    e.preventDefault();

                    const url = btn.dataset.consumir || btn.getAttribute('href');

                    const {
                        value: opcion
                    } = await Swal.fire({
                        title: '¿Cómo deseas consumir el material?',
                        text: 'Selecciona si quieres consumirlo completo o solo unos kilos.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Consumir completo',
                        cancelButtonText: 'Cancelar',
                        showDenyButton: true,
                        denyButtonText: 'Consumir por kilos'
                    });

                    if (opcion) {
                        // ✅ Consumir completo
                        if (opcion === true) {
                            window.location.href = url + '?modo=total';
                        }
                    } else if (opcion === false) {
                        // ✅ Consumir por kilos
                        const {
                            value: kilos
                        } = await Swal.fire({
                            title: 'Introduce los kilos a consumir',
                            input: 'number',
                            inputAttributes: {
                                min: 1,
                                step: 0.01
                            },
                            inputPlaceholder: 'Ejemplo: 250',
                            showCancelButton: true,
                            confirmButtonText: 'Consumir',
                            cancelButtonText: 'Cancelar',
                            preConfirm: (value) => {
                                if (!value || value <= 0) {
                                    Swal.showValidationMessage(
                                        'Debes indicar un número válido mayor que 0');
                                    return false;
                                }
                                return value;
                            }
                        });

                        if (kilos) {
                            // Redirigimos con cantidad en la URL (ejemplo GET)
                            window.location.href = url + '?modo=parcial&kgs=' + kilos;
                        }
                    }
                });

                // Confirmar eliminación
                document.querySelectorAll('.form-eliminar').forEach(form => {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();

                        Swal.fire({
                            title: '¿Estás seguro?',
                            text: "Esta acción eliminará la materia prima de forma permanente.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#6c757d',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    });
                });

                // Marcar como inicializado
                document.body.dataset.productosPageInit = 'true';
            }

            // Registrar en el sistema global
            window.pageInitializers = window.pageInitializers || [];
            window.pageInitializers.push(initProductosPage);

            // Configurar listeners
            document.addEventListener('livewire:navigated', initProductosPage);
            document.addEventListener('DOMContentLoaded', initProductosPage);

            // Limpiar flag antes de navegar
            document.addEventListener('livewire:navigating', () => {
                document.body.dataset.productosPageInit = 'false';
            });
        </script>

        <!-- Formulario oculto para eliminar Producto Base -->
        <form id="formEliminarProductoBase" method="POST" class="hidden">
            @csrf

            <!-- Modal Crear Producto Base -->
            <div id="modalProductoBase"
                class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
                    <h2 class="text-xl font-semibold mb-4">Nuevo Producto Base</h2>

                    <form action="{{ route('productos-base.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="redirect_to" value="productos">

                        <div>
                            <label for="pb_tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                            <select id="pb_tipo" name="tipo" required onchange="toggleLongitud()"
                                class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Seleccione tipo</option>
                                <option value="barra">Barra</option>
                                <option value="encarretado">Encarretado</option>
                            </select>
                        </div>

                        <div>
                            <label for="pb_diametro" class="block text-sm font-medium text-gray-700 mb-1">Diametro
                                (mm) *</label>
                            <input type="number" id="pb_diametro" name="diametro" required min="1"
                                class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Ej: 12">
                        </div>

                        <div id="pb_longitud_container">
                            <label for="pb_longitud" class="block text-sm font-medium text-gray-700 mb-1">Longitud
                                (m)</label>
                            <input type="number" id="pb_longitud" name="longitud" min="1"
                                class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Ej: 12 (solo para barras)">
                        </div>

                        <div>
                            <label for="pb_descripcion"
                                class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label>
                            <textarea id="pb_descripcion" name="descripcion" rows="2"
                                class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Descripcion opcional..."></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" onclick="cerrarModalProductoBase()"
                                class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function abrirModalProductoBase() {
                    document.getElementById('modalProductoBase').classList.remove('hidden');
                    document.getElementById('modalProductoBase').classList.add('flex');
                }

                function cerrarModalProductoBase() {
                    document.getElementById('modalProductoBase').classList.remove('flex');
                    document.getElementById('modalProductoBase').classList.add('hidden');
                    // Limpiar formulario
                    document.getElementById('pb_tipo').value = '';
                    document.getElementById('pb_diametro').value = '';
                    document.getElementById('pb_longitud').value = '';
                    document.getElementById('pb_descripcion').value = '';
                }

                function toggleLongitud() {
                    const tipo = document.getElementById('pb_tipo').value;
                    const container = document.getElementById('pb_longitud_container');
                    const input = document.getElementById('pb_longitud');

                    if (tipo === 'encarretado') {
                        container.style.display = 'none';
                        input.value = '';
                    } else {
                        container.style.display = 'block';
                    }
                }

                function eliminarProductoBase(id, nombre) {
                    Swal.fire({
                        title: '¿Eliminar producto base?',
                        html: `<p class="text-gray-600">Se eliminará: <strong>${nombre}</strong></p><p class="text-xs text-gray-400 mt-1">ID: ${id}</p>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc2626',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.getElementById('formEliminarProductoBase');
                            form.action = `/productos-base/${id}`;
                            form.submit();
                        }
                    });
                }

                // Cerrar modal con ESC
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        cerrarModalProductoBase();
                    }
                });

                // Cerrar al hacer clic fuera
                window.addEventListener('click', function(event) {
                    const modal = document.getElementById('modalProductoBase');
                    if (event.target === modal) {
                        cerrarModalProductoBase();
                    }
                });
            </script>

</x-app-layout>
