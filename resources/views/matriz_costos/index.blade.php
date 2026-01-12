<x-app-layout>
    <div class="container-fluid px-4 py-4">
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
            }

            .matriz-table td {
                padding: 16px;
                border-bottom: 1px solid var(--gray-200);
                font-size: 14px;
                vertical-align: middle;
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
            <h1 class="matriz-header">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="var(--google-blue)">
                    <path
                        d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z" />
                </svg>
                Matriz de Costos por Obra
            </h1>

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

            <div class="search-wrapper">
                <input type="text" id="matrizSearch" class="search-input" placeholder="Buscar por obra o cliente...">
            </div>

            <div class="table-responsive">
                <table class="table matriz-table" id="matrizTable">
                    <thead>
                        <tr>
                            <th>Obra</th>
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
                        @foreach ($summaries as $summary)
                            <tr>
                                <td>
                                    <strong>{{ $summary['obra']->obra }}</strong><br>
                                    <small class="text-muted">{{ $summary['obra']->cod_obra }}</small>
                                </td>
                                <td>{{ $summary['obra']->cliente->nombre ?? 'N/A' }}</td>
                                <td>{{ number_format($summary['budget'], 2, ',', '.') }} €</td>
                                <td>{{ number_format($summary['labor_cost'], 2, ',', '.') }} €</td>
                                <td>{{ number_format($summary['material_cost'], 2, ',', '.') }} €</td>
                                <td>{{ number_format($summary['logistics_cost'], 2, ',', '.') }} €</td>
                                <td class="{{ $summary['real_cost'] > $summary['budget'] ? 'google-negative' : '' }}">
                                    <strong>{{ number_format($summary['real_cost'], 2, ',', '.') }} €</strong>
                                </td>
                                <td class="{{ $summary['deviation'] < 0 ? 'google-negative' : 'google-positive' }}">
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
                    </tbody>
                </table>
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
