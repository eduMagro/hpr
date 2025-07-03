<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Comparar Inventario
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 py-6" x-data="compararInventario(@json($esperados))">
        <template x-for="(analisis, ubicacion) in resultados" :key="ubicacion">
            <div class="mt-6 border rounded-lg shadow">
                <h3 class="bg-gray-100 px-4 py-2 font-semibold text-lg text-gray-700" x-text="`Ubicación: ${ubicacion}`">
                </h3>

                <div class="p-4 text-sm space-y-2">
                    <div><strong>✅ Correctos:</strong> <span x-text="analisis.ok.join(', ') || '—'"></span></div>
                    <div><strong>🔁 En otra ubicación:</strong> <span x-text="analisis.enOtra.join(', ') || '—'"></span>
                    </div>
                    <div><strong>❌ Faltantes:</strong> <span x-text="analisis.faltantes.join(', ') || '—'"></span></div>
                    <div><strong>⚠️ Sobrantes:</strong> <span x-text="analisis.sobrantes.join(', ') || '—'"></span>
                    </div>
                </div>
            </div>
        </template>

        <div class="mt-6 text-right">
            <button @click="analizar()"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded shadow">
                Analizar Inventario
            </button>
        </div>
    </div>

    <script>
        function compararInventario(esperadosPorUbicacion) {
            return {
                resultados: {},
                analizar() {
                    const detectados = {};
                    const globalDetectados = [];

                    // Recorrer localStorage y llenar detectados
                    for (const [ubicacion, esperados] of Object.entries(esperadosPorUbicacion)) {
                        const key = `inv-${ubicacion}`;
                        const data = JSON.parse(localStorage.getItem(key) || '[]');
                        detectados[ubicacion] = data;
                        globalDetectados.push(...data);
                    }

                    for (const [ubicacion, esperados] of Object.entries(esperadosPorUbicacion)) {
                        const reales = detectados[ubicacion] || [];

                        const ok = esperados.filter(c => reales.includes(c));
                        const enOtra = esperados.filter(c => !reales.includes(c) && globalDetectados.includes(c));
                        const faltantes = esperados.filter(c => !globalDetectados.includes(c));
                        const sobrantes = reales.filter(c => !esperados.includes(c));

                        this.resultados[ubicacion] = {
                            ok,
                            enOtra,
                            faltantes,
                            sobrantes
                        };
                    }
                }
            }
        }
    </script>
</x-app-layout>
