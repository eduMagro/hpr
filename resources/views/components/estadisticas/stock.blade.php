  <!-- Reporte de Pesos por Planilla (Pendientes) -->
  <div class="bg-white shadow-lg rounded-lg mb-6">
      <div class="bg-blue-600 text-white text-center p-4 rounded-t-lg">
          <h3 class="text-lg font-semibold">Reporte de Pesos por Planilla (Pendientes)</h3>
      </div>

      <tbody class="text-gray-700">
          @forelse ($pesoTotalPorDiametro as $diametro => $peso_total)
              <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                  <td class="px-4 py-3 text-center border">{{ number_format($diametro, 0) }}</td>
                  <td class="px-4 py-3 text-center border">{{ number_format($peso_total, 0) }}</td>
              </tr>
          @empty
              <tr>
                  <td colspan="2" class="text-red-600 px-4 py-3 text-center">
                      No hay datos disponibles
                  </td>
              </tr>
          @endforelse
      </tbody>


      <h4 class="text-center bg-blue-100 text-blue-900 font-semibold py-2 rounded-md mt-6">
          Peso por Planilla y Diámetro
      </h4>
      <div class="overflow-x-auto">
          <table class="w-full border border-gray-300 rounded-lg text-xs">
              <thead class="bg-blue-500 text-white">
                  <tr>
                      <th class="px-4 py-3 border text-center">Diámetro (mm)</th>
                      <th class="px-4 py-3 border text-center">Planilla</th>
                      <th class="px-4 py-3 border text-center">Peso por Planilla (kg)</th>
                  </tr>
              </thead>
              <tbody class="text-gray-700">
                  @forelse ($datosPorPlanilla as $fila)
                      <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                          <td class="px-4 py-3 text-center border">{{ number_format($diametro, 2) }}
                          </td>
                          <td class="px-4 py-3 text-center border">{{ $fila->planilla_id }}</td>
                          <td class="px-4 py-3 text-center border">
                              {{ number_format($fila->peso_por_planilla, 0) }}</td>
                      </tr>
                  @empty
                      <tr>
                          <td colspan="3" class="text-red-600 px-4 py-3 text-center">
                              No hay datos disponibles
                          </td>
                      </tr>
                  @endforelse
              </tbody>
          </table>
      </div>
  </div>

  <div class="text-center text-gray-600 bg-gray-100 py-2 rounded-b-lg">
      Generado el {{ now()->format('d/m/Y H:i') }}
  </div>
  </div>

  <!-- Stock Actual -->
  <div class="bg-white shadow-lg rounded-lg">
      <div class="bg-blue-600 text-white text-center p-4 rounded-t-lg">
          <h3 class="text-lg font-semibold">Stock Actual</h3>
      </div>
      @php
          // 1. Obtener todas las longitudes únicas presentes en $stockBarras
          $longitudesUnicas = $stockBarras->pluck('longitud')->unique()->sort();

          // 2. Reindexar las colecciones para un acceso más rápido
          $stockEncarretadoMap = $stockEncarretado->keyBy('diametro');
          $stockBarrasGroup = $stockBarras->groupBy('diametro');

          // 3. Unir todos los diámetros (encarretado + barras)
          $diametros = $stockEncarretado->pluck('diametro')->merge($stockBarras->pluck('diametro'))->unique()->sort();
      @endphp
      {{-- Mostrar advertencias de stock insuficiente --}}
      @if (session('advertencia'))
          @foreach (session('advertencia') as $mensaje)
              <div class="alert alert-danger d-flex justify-content-between align-items-center">
                  <span>{{ $mensaje }}</span>
                  <form action="{{ route('solicitar.stock') }}" method="POST">
                      @csrf
                      <input type="hidden" name="diametro" value="{{ $mensaje }}">
                      <button type="submit" class="btn btn-warning btn-sm">Solicitar</button>
                  </form>
              </div>
          @endforeach
      @endif



      <div class="p-4">
          <div class="overflow-x-auto">
              <table class="w-full border border-gray-300 rounded-lg text-xs">
                  <thead class="bg-blue-500 text-white">
                      <tr>
                          <th class="px-4 py-3 border text-center">Diámetro (mm)</th>
                          <th class="px-4 py-3 border text-center">Encarretado (kg)</th>
                          @foreach ($longitudesUnicas as $longitud)
                              <th class="px-4 py-3 border text-center">Barras {{ number_format($longitud, 2) }} m
                                  (kg)
                              </th>
                          @endforeach
                          <th class="px-4 py-3 border text-center">Barras Total (kg)</th>
                          <th class="px-4 py-3 border text-center">Total (kg)</th>
                      </tr>
                  </thead>
                  <tbody class="text-gray-700">
                      @forelse($diametros as $diam)
                          @php
                              $encarretado = $stockEncarretadoMap->get($diam);
                              $stockEncarretadoVal = $encarretado ? $encarretado->stock : 0;
                              $barrasCollection = $stockBarrasGroup->get($diam) ?? collect();
                              $barrasTotal = 0;
                          @endphp
                          <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                              <td class="px-4 py-3 text-center border">{{ number_format($diam, 2) }}</td>
                              <td class="px-4 py-3 text-center border">
                                  {{ number_format($stockEncarretadoVal, 2) }}</td>

                              @foreach ($longitudesUnicas as $longitud)
                                  @php
                                      $item = $barrasCollection->firstWhere('longitud', $longitud);
                                      $stockThisLength = $item ? $item->stock : 0;
                                      $barrasTotal += $stockThisLength;
                                  @endphp
                                  <td class="px-4 py-3 text-center border">
                                      {{ number_format($stockThisLength, 2) }}</td>
                              @endforeach

                              <td class="px-4 py-3 text-center border">{{ number_format($barrasTotal, 2) }}</td>
                              <td class="px-4 py-3 text-center border">
                                  {{ number_format($stockEncarretadoVal + $barrasTotal, 2) }}</td>
                          </tr>
                      @empty
                          <tr>
                              <td colspan="{{ 3 + count($longitudesUnicas) }}"
                                  class="text-red-600 px-4 py-3 text-center">
                                  No hay datos disponibles
                              </td>
                          </tr>
                      @endforelse
                  </tbody>
              </table>
          </div>
      </div>

      <div class="text-center text-gray-600 bg-gray-100 py-2 rounded-b-lg">
          Generado el {{ now()->format('d/m/Y H:i') }}
      </div>

      <div class="p-4 text-xs">
          <h4 class="text-center bg-blue-100 text-blue-900 font-semibold py-2 rounded-md">
              Alertas pedidos
          </h4>
          @if (session('alertas_stock'))
              <div class="p-4 mb-4">
                  @foreach (session('alertas_stock') as $mensaje)
                      <div
                          class="flex items-center justify-between px-4 py-3 border-l-4 rounded-lg shadow-md text-xs
                {{ Str::contains($mensaje, '🔴')
                    ? 'bg-red-100 border-red-500 text-red-800'
                    : (Str::contains($mensaje, '🟠')
                        ? 'bg-orange-100 border-orange-500 text-orange-800'
                        : (Str::contains($mensaje, '⚠️')
                            ? 'bg-yellow-100 border-yellow-500 text-yellow-800'
                            : 'bg-green-100 border-green-500 text-green-800')) }}">
                          <div class="flex items-center">
                              {{-- Icono de alerta según la severidad --}}
                              @if (Str::contains($mensaje, '🔴'))
                                  <svg class="w-6 h-6 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                      <path fill-rule="evenodd"
                                          d="M10 2a8 8 0 100 16 8 8 0 000-16zM9 7a1 1 0 012 0v4a1 1 0 01-2 0V7zm0 6a1 1 0 112 0 1 1 0 01-2 0z"
                                          clip-rule="evenodd" />
                                  </svg>
                              @elseif (Str::contains($mensaje, '🟠'))
                                  <svg class="w-6 h-6 text-orange-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                      <path fill-rule="evenodd"
                                          d="M8.257 3.099c.366-.446.985-.446 1.35 0l6.829 8.342c.293.358.076.899-.357.899H2.07c-.432 0-.65-.541-.357-.899l6.829-8.342zM10 14a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"
                                          clip-rule="evenodd" />
                                  </svg>
                              @elseif (Str::contains($mensaje, '⚠️'))
                                  <svg class="w-6 h-6 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                      <path fill-rule="evenodd"
                                          d="M8.257 3.099c.366-.446.985-.446 1.35 0l6.829 8.342c.293.358.076.899-.357.899H2.07c-.432 0-.65-.541-.357-.899l6.829-8.342zM10 14a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"
                                          clip-rule="evenodd" />
                                  </svg>
                              @else
                                  <svg class="w-6 h-6 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                      <path fill-rule="evenodd"
                                          d="M16.707 5.293a1 1 0 00-1.414 0L9 11.586 6.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l7-7a1 1 0 000-1.414z"
                                          clip-rule="evenodd" />
                                  </svg>
                              @endif

                              <span class="text-sm font-medium">
                                  {{ $mensaje }}
                              </span>
                          </div>

                          {{-- Botón de cerrar alerta --}}
                          <button type="button" class="text-gray-500 hover:text-gray-700 focus:outline-none"
                              onclick="this.parentElement.style.display='none'">
                              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                  <path fill-rule="evenodd"
                                      d="M10 8.586l-4.293-4.293a1 1 0 10-1.414 1.414L8.586 10l-4.293 4.293a1 1 0 001.414 1.414L10 11.414l4.293 4.293a1 1 0 001.414-1.414L11.414 10l4.293-4.293a1 1 0 00-1.414-1.414L10 8.586z"
                                      clip-rule="evenodd" />
                              </svg>
                          </button>
                      </div>
                  @endforeach
              </div>
          @endif


          <!-- Stock Materiales -->
          <div class="bg-white shadow-lg rounded-lg text-xs">
              <div class="bg-blue-600 text-white text-center p-2 rounded-t-lg">
                  <h3 class="text-xs font-semibold">Stock Materiales</h3>
              </div>

              <div class="p-2">
                  <div class="overflow-x-auto">
                      <table class="w-full border border-gray-300 rounded-lg text-xs">
                          <thead class="bg-blue-500 text-white">
                              <tr>
                                  <th class="px-2 py-1 border text-center">Diámetro (mm)</th>
                                  <th class="px-2 py-1 border text-center">Stock Óptimo (kg)</th>
                                  <th class="px-2 py-1 border text-center">Stock Real (kg)</th>
                                  <th class="px-2 py-1 border text-center">Stock Deseado (kg) - 2 Semanas</th>
                                  <th class="px-2 py-1 border text-center">Stock Necesario en 7 Días (kg)</th>
                                  <th class="px-2 py-1 border text-center">Consumo Promedio (kg/día)</th>
                              </tr>
                          </thead>
                          <tbody class="text-gray-700">
                              @forelse($stockOptimo as $stock)
                                  <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                                      <td class="px-2 py-1 text-center border">
                                          {{ number_format((int) $stock->diametro, 0) }}</td>
                                      <td class="px-2 py-1 text-center border">
                                          {{ number_format((int) $stock->stock_optimo, 0) }}</td>
                                      <td class="px-2 py-1 text-center border">
                                          {{ number_format($stock->stock_real, 0) }}</td>
                                      <td class="px-2 py-1 text-center border">
                                          {{ number_format((int) $stock->stock_deseado, 0) }}</td>
                                      <td class="px-2 py-1 text-center border bg-yellow-100 text-yellow-900 font-bold">
                                          {{ number_format((int) $stock->stock_semana, 0) }}
                                      </td>
                                      <td class="px-2 py-1 text-center border">
                                          {{ number_format((int) $stock->consumo_promedio, 0) }}</td>
                                  </tr>
                              @empty
                                  <tr>
                                      <td colspan="6" class="text-red-600 px-2 py-1 text-center">
                                          No hay datos disponibles
                                      </td>
                                  </tr>
                              @endforelse
                          </tbody>
                      </table>
                  </div>
              </div>

              <div class="text-center text-gray-600 bg-gray-100 py-1 rounded-b-lg">
                  Generado el {{ now()->format('d/m/Y H:i') }}
              </div>
          </div>
      </div>
  </div>
