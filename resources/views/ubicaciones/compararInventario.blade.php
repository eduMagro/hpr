<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Comparar Inventario
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 py-6" x-data="() => compararInventario(@js($esperados))" x-init="analizar()">

        <template x-if="Object.keys(resultados).length">
            <template x-for="(analisis, ubicacion) in resultados" :key="ubicacion">
                <div class="mt-6 border rounded-lg shadow overflow-hidden">
                    <h3 class="bg-gray-100 px-4 py-2 font-semibold text-lg text-gray-700"
                        x-text="`Ubicación: ${ubicacion}`">
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 text-sm">

                        <div class="bg-green-50 border-l-4 border-green-400 px-3 py-2 rounded">
                            <h4 class="font-semibold text-green-700 mb-1">✅ Correctos (<span
                                    x-text="analisis.ok.length"></span>)</h4>
                            <p class="text-green-800 text-xs break-words" x-text="analisis.ok.join(', ') || '—'"></p>
                        </div>

                        <div class="bg-blue-50 border-l-4 border-blue-400 px-3 py-2 rounded">
                            <h4 class="font-semibold text-blue-700 mb-1">🔁 En otra ubicación (<span
                                    x-text="analisis.enOtra.length"></span>)</h4>
                            <p class="text-blue-800 text-xs break-words" x-text="analisis.enOtra.join(', ') || '—'"></p>
                        </div>

                        <div class="bg-red-50 border-l-4 border-red-400 px-3 py-2 rounded">
                            <h4 class="font-semibold text-red-700 mb-1">❌ Faltantes (<span
                                    x-text="analisis.faltantes.length"></span>)</h4>
                            <p class="text-red-800 text-xs break-words" x-text="analisis.faltantes.join(', ') || '—'">
                            </p>
                        </div>

                        <div class="bg-yellow-50 border-l-4 border-yellow-400 px-3 py-2 rounded">
                            <h4 class="font-semibold text-yellow-700 mb-1">⚠️ Mal ubicados (<span
                                    x-text="analisis.malUbicados.length"></span>)</h4>
                            <p class="text-yellow-800 text-xs break-words"
                                x-text="analisis.malUbicados.join(', ') || '—'"></p>
                        </div>

                        <div class="bg-orange-50 border-l-4 border-orange-400 px-3 py-2 rounded md:col-span-2">
                            <h4 class="font-semibold text-orange-700 mb-1">⚠️ Sobrantes en esta ubicación (<span
                                    x-text="analisis.sobrantes.length"></span>)</h4>
                            <p class="text-orange-800 text-xs break-words"
                                x-text="analisis.sobrantes.join(', ') || '—'"></p>
                        </div>
                    </div>
                </div>
            </template>
        </template>

        <div x-show="sobrantesGlobales.length > 0"
            class="mt-10 border-t pt-6 text-sm text-red-800 bg-red-50 border-l-4 border-red-400 px-4 py-4 rounded shadow">
            <h3 class="font-bold text-red-700 text-lg mb-2">🚨 Productos sobrantes no asociados a ninguna ubicación</h3>
            <ul class="list-disc list-inside space-y-1 text-xs">
                <template x-for="codigo in sobrantesGlobales" :key="codigo">
                    <li x-text="codigo"></li>
                </template>
            </ul>
        </div>


    </div>

    <script>
        function compararInventario(esperadosPorUbicacion) {
            return {
                resultados: {},
                sobrantesGlobales: [],

                analizar() {
                    const detectados = {};
                    const inesperados = {};
                    const globalDetectados = [];

                    for (const [ubicacion, esperados] of Object.entries(esperadosPorUbicacion)) {
                        const claveEscaneados = `inv-${ubicacion}`;
                        const claveSospechosos = `sospechosos-${ubicacion}`;

                        const escaneados = JSON.parse(localStorage.getItem(claveEscaneados) || '[]');
                        const sospechosos = JSON.parse(localStorage.getItem(claveSospechosos) || '[]');

                        detectados[ubicacion] = escaneados;
                        inesperados[ubicacion] = sospechosos;

                        globalDetectados.push(...escaneados);
                    }

                    this.sobrantesGlobales = [];
                    const todosEsperados = Object.values(esperadosPorUbicacion).flat();
                    const codigosVistos = new Set([...todosEsperados, ...globalDetectados]);

                    for (const sospechos of Object.values(inesperados)) {
                        for (const codigo of sospechos) {
                            if (!codigosVistos.has(codigo)) {
                                this.sobrantesGlobales.push(codigo);
                            }
                        }
                    }

                    for (const [ubicacion, esperados] of Object.entries(esperadosPorUbicacion)) {
                        const escaneadosAqui = detectados[ubicacion] || [];

                        const ok = esperados.filter(c => escaneadosAqui.includes(c));
                        const faltantes = esperados.filter(c => !globalDetectados.includes(c));
                        const enOtra = esperados.filter(c => !escaneadosAqui.includes(c) && globalDetectados.includes(c));

                        const malUbicados = faltantes.filter(codigo =>
                            Object.entries(inesperados).some(([ubi, lista]) =>
                                ubi !== ubicacion && lista.includes(codigo)
                            )
                        );

                        const sobrantes = escaneadosAqui.filter(c => !esperados.includes(c));

                        this.resultados[ubicacion] = {
                            ok,
                            enOtra,
                            faltantes,
                            malUbicados,
                            sobrantes
                        };
                    }
                }
            }
        }
    </script>
</x-app-layout>
