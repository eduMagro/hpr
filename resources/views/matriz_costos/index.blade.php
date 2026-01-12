<x-app-layout>
    <div class="container-fluid px-4 py-4" x-data="{ missingUsersModalOpen: false, currentMissingUsers: [] }">
        <style>
            :root {
                --google-blue: #1a73e8;
                --google-red: #ea4335;
                --google-yellow: #fbbc04;
                --google-green: #34a853;
                --gray-100: #f8f9fa;
                --gray-200: #e8eaed;
                --gray-300: #dadce0;
                --text-primary: #3c4043;
                --text-secondary: #5f6368;
            }

            .matriz-container {
                font-family: 'Roboto', sans-serif;
                color: var(--text-primary);
            }

            .matriz-header {
                font-family: 'Google Sans', sans-serif;
                font-size: 24px;
                font-weight: 400;
                margin-bottom: 32px;
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .summary-container {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 16px;
                margin-bottom: 32px;
            }

            .card-summary {
                background: white;
                border: 1px solid var(--gray-300);
                border-radius: 8px;
                padding: 20px;
                transition: box-shadow 0.2s;
            }

            .card-summary:hover {
                box-shadow: 0 1px 3px 0 rgba(60, 64, 67, 0.30), 0 4px 8px 3px rgba(60, 64, 67, 0.15);
            }

            .card-label {
                font-size: 14px;
                color: var(--text-secondary);
                margin-bottom: 8px;
            }

            .card-value {
                font-family: 'Google Sans', sans-serif;
                font-size: 24px;
                font-weight: 500;
            }

            .table-responsive {
                background: white;
                border: 1px solid var(--gray-300);
                border-radius: 8px;
                overflow: hidden;
            }

            .matriz-table {
                width: 100%;
                margin-bottom: 0;
            }

            .matriz-table th {
                background-color: var(--gray-100);
                font-weight: 500;
                font-size: 13px;
                color: var(--text-secondary);
                padding: 12px 16px;
                border-bottom: 1px solid var(--gray-300);
                text-transform: uppercase;
                letter-spacing: 0.5px;
                white-space: nowrap;
            }

            .matriz-table td {
                padding: 16px;
                border-bottom: 1px solid var(--gray-200);
                font-size: 14px;
                vertical-align: middle;
                white-space: nowrap;
            }

            .matriz-table tr:hover {
                background-color: var(--gray-100);
            }

            .badge-google {
                padding: 4px 12px;
                border-radius: 16px;
                font-size: 12px;
                font-weight: 500;
            }

            .badge-google-success {
                background-color: #e6f4ea;
                color: #1e8e3e;
            }

            .badge-google-error {
                background-color: #fce8e6;
                color: #d93025;
            }

            .badge-google-warning {
                background-color: #fef7e0;
                color: #b17d06;
            }

            .google-positive {
                color: var(--google-green);
            }

            .google-negative {
                color: var(--google-red);
            }

            .search-wrapper {
                margin-bottom: 24px;
            }

            .search-input {
                padding: 10px 20px;
                border: 1px solid var(--gray-300);
                border-radius: 24px;
                width: 350px;
                outline: none;
                transition: all 0.2s;
            }

            .search-input:focus {
                border-color: var(--google-blue);
                box-shadow: 0 1px 3px 0 rgba(60, 64, 67, 0.30);
            }
        </style>

        <div class="matriz-container">
            <!-- Header ... -->
            <h1 class="matriz-header">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="var(--google-blue)">
                    <path
                        d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z" />
                </svg>
                Matriz de Costos por Obra
            </h1>

            <!-- Summary Cards ... -->
            <div class="summary-container">
                <div class="card-summary">
                    <div class="card-label">Obras Analizadas</div>
                    <div class="card-value">{{ $totals['count'] }}</div>
                </div>
                <div class="card-summary">
                    <div class="card-label">Ppto. Estimado Total</div>
                    <div class="card-value">{{ number_format($totals['budget'], 2, ',', '.') }} €</div>
                </div>
                <div class="card-summary">
                    <div class="card-label">Coste Real Total</div>
                    <div
                        class="card-value {{ $totals['real_cost'] > $totals['budget'] ? 'google-negative' : 'google-positive' }}">
                        {{ number_format($totals['real_cost'], 2, ',', '.') }} €
                    </div>
                </div>
                <div class="card-summary">
                    <div class="card-label">Desviación Global</div>
                    <div class="card-value {{ $totals['deviation'] < 0 ? 'google-negative' : 'google-positive' }}">
                        {{ $totals['deviation'] > 0 ? '+' : '' }}{{ number_format($totals['deviation'], 2, ',', '.') }}
                        €
                    </div>
                </div>
            </div>

            <!-- Search ... -->
            <div class="search-wrapper d-flex align-items-center justify-content-between">
                <input type="text" id="matrizSearch" class="search-input" placeholder="Buscar por obra o cliente...">

                <div class="form-check form-switch ms-3">
                    <input class="form-check-input" type="checkbox" id="toggleCompleted"
                        {{ $showCompleted ? 'checked' : '' }}
                        onchange="window.location.href = '{{ route('matriz_costos.index') }}?show_completed=' + (this.checked ? 1 : 0)">
                    <label class="form-check-label text-secondary font-weight-normal" style="font-size: 14px;"
                        for="toggleCompleted">
                        Mostrar obras completadas
                    </label>
                </div>
            </div>

            <!-- Table ... -->
            <div class="table-responsive">
                <table class="table matriz-table" id="matrizTable">
                    <thead>
                        <tr>
                            <th>Obra</th>
                            <th>Fecha Alta</th>
                            <th>Cliente</th>
                            <th>Presupuesto</th>
                            <th>M. Obra</th>
                            <th>Material</th>
                            <th>Logística</th>
                            <th>Total Real</th>
                            <th>Desviación</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groupedSummaries as $month => $summaries)
                            <tr class="group-header bg-light">
                                <td colspan="10" class="py-2 px-3">
                                    <h5 class="mb-0 text-primary font-weight-bold" style="font-size: 16px;">
                                        <i class="far fa-calendar-alt me-2"></i> {{ $month }}
                                    </h5>
                                </td>
                            </tr>
                            @foreach ($summaries as $summary)
                                <tr>
                                    <td>
                                        <strong>{{ $summary['obra']->obra }}</strong><br>
                                    </td>
                                    <td>
                                        <span
                                            class="text-secondary">{{ $summary['obra']->created_at->format('d/m/Y') }}</span>
                                    </td>
                                    <td>{{ $summary['obra']->cliente->empresa ?? 'N/A' }}</td>
                                    <td>{{ number_format($summary['budget'], 2, ',', '.') }} €</td>
                                    <td>
                                        <div class="flex items-center justify-between">
                                            {{ number_format($summary['labor_cost'], 2, ',', '.') }} €
                                            @if (!empty($summary['missing_nominas_users']) && count($summary['missing_nominas_users']) > 0)
                                                <button type="button" class="ml-2 text-warning"
                                                    style="border:none; background:none; cursor:pointer;"
                                                    title="Nóminas de algunos trabajadores no calculables"
                                                    @click="missingUsersModalOpen = true; currentMissingUsers = {{ json_encode($summary['missing_nominas_users']) }}">
                                                    <!-- SVG: Circle Alert -->
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20"
                                                        height="20" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        class="lucide lucide-circle-alert-icon lucide-circle-alert text-red-500">
                                                        <circle cx="12" cy="12" r="10" />
                                                        <line x1="12" x2="12" y1="8"
                                                            y2="12" />
                                                        <line x1="12" x2="12.01" y1="16"
                                                            y2="16" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ number_format($summary['material_cost'], 2, ',', '.') }} €</td>
                                    <td>{{ number_format($summary['logistics_cost'], 2, ',', '.') }} €</td>
                                    <td
                                        class="{{ $summary['real_cost'] > $summary['budget'] ? 'google-negative' : '' }}">
                                        <strong>{{ number_format($summary['real_cost'], 2, ',', '.') }} €</strong>
                                    </td>
                                    <td
                                        class="{{ $summary['deviation'] < 0 ? 'google-negative' : 'google-positive' }}">
                                        {{ $summary['deviation'] > 0 ? '+' : '' }}{{ number_format($summary['deviation'], 2, ',', '.') }}
                                        €
                                    </td>
                                    <td>
                                        @if ($summary['budget'] == 0)
                                            <span class="badge-google badge-google-warning">Sin Ppto.</span>
                                        @elseif($summary['real_cost'] > $summary['budget'])
                                            <span class="badge-google badge-google-error">Excedido</span>
                                        @elseif($summary['real_cost'] > $summary['budget'] * 0.9)
                                            <span class="badge-google badge-google-warning">Límite</span>
                                        @else
                                            <span class="badge-google badge-google-success">En Objetivo</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Alpine Modal -->
        <div x-show="missingUsersModalOpen"
            class="fixed inset-0 z-[1050] flex items-center justify-center bg-black bg-opacity-50"
            style="display: none;" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @keydown.escape.window="missingUsersModalOpen = false">

            <div class="bg-white rounded-lg shadow-xl p-6 relative w-11/12 max-w-md"
                @click.away="missingUsersModalOpen = false">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Trabajadores sin Nómina</h3>
                    <button @click="missingUsersModalOpen = false" class="text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <p class="text-sm text-gray-600 mb-3">
                    No se han encontrado nóminas para los siguientes trabajadores (ni individuales ni por categoría) en
                    el mes de la obra ni en los 12 meses anteriores.
                </p>

                <ul class="list-disc pl-5 max-h-60 overflow-y-auto">
                    <template x-for="user in currentMissingUsers" :key="user.id">
                        <li x-text="user.name" class="text-gray-800"></li>
                    </template>
                </ul>

                <div class="mt-6 text-right">
                    <button @click="missingUsersModalOpen = false"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <script>
            document.getElementById('matrizSearch').addEventListener('keyup', function() {
                let filter = this.value.toLowerCase();
                let rows = document.querySelectorAll('#matrizTable tbody tr');

                rows.forEach(row => {
                    let text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        </script>
    </div>
</x-app-layout>
