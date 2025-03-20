  <!-- Reporte de Pesos por Planilla (Pendientes) -->
  <div class="bg-white shadow-lg rounded-lg mb-6">
      <div class="bg-blue-600 text-white text-center p-4 rounded-t-lg">
          <h3 class="text-lg font-semibold">Reporte de Pesos por Planilla (Pendientes)</h3>
      </div>

      <div class="p-4">
          <h4 class="text-center bg-blue-100 text-blue-900 font-semibold py-2 rounded-md">
              Peso Total por Diámetro
          </h4>
          <div class="overflow-x-auto">
              <table class="w-full border border-gray-300 rounded-lg">
                  <thead class="bg-blue-500 text-white">
                      <tr>
                          <th class="px-4 py-3 border text-center">Diámetro (mm)</th>
                          <th class="px-4 py-3 border text-center">Peso Total (kg)</th>
                      </tr>
                  </thead>
                  <tbody class="text-gray-700">
                      @forelse ($pesoTotalPorDiametro as $fila)
                          <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                              <td class="px-4 py-3 text-center border">{{ number_format($fila->diametro, 2) }}
                              </td>
                              <td class="px-4 py-3 text-center border">{{ number_format($fila->peso_total, 2) }}
                              </td>
                          </tr>
                      @empty
                          <tr>
                              <td colspan="2" class="text-red-600 px-4 py-3 text-center">
                                  No hay datos disponibles
                              </td>
                          </tr>
                      @endforelse
                  </tbody>
              </table>
          </div>

          <h4 class="text-center bg-blue-100 text-blue-900 font-semibold py-2 rounded-md mt-6">
              Peso por Planilla y Diámetro
          </h4>
          <div class="overflow-x-auto">
              <table class="w-full border border-gray-300 rounded-lg">
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
                              <td class="px-4 py-3 text-center border">{{ number_format($fila->diametro, 2) }}
                              </td>
                              <td class="px-4 py-3 text-center border">{{ $fila->planilla_id }}</td>
                              <td class="px-4 py-3 text-center border">
                                  {{ number_format($fila->peso_por_planilla, 2) }}</td>
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
              <table class="w-full border border-gray-300 rounded-lg">
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

      <div class="p-4">
          <h4 class="text-center bg-blue-100 text-blue-900 font-semibold py-2 rounded-md">
              Stock Deseado
          </h4>
          {{-- Mostrar mensaje de éxito --}}
          @if (session('exito'))
              <div class="alert alert-success">
                  {{ session('exito') }}
              </div>
          @endif

          @if (session('alertas_stock'))
              <div class="p-4 mb-4">
                  @foreach (session('alertas_stock') as $mensaje)
                      <div
                          class="px-4 py-3 rounded-lg text-white font-semibold 
                      {{ Str::contains($mensaje, '🔴')
                          ? 'bg-red-600'
                          : (Str::contains($mensaje, '🟠')
                              ? 'bg-orange-500'
                              : (Str::contains($mensaje, '⚠️')
                                  ? 'bg-yellow-500'
                                  : 'bg-green-500')) }}">
                          {{ $mensaje }}
                      </div>
                  @endforeach
              </div>
          @endif

          <div class="overflow-x-auto">
              <table class="w-full border border-gray-300 rounded-lg">
                  <thead class="bg-blue-500 text-white">
                      <tr>
                          <th class="px-4 py-3 border text-center">Diámetro (mm)</th>
                          <th class="px-4 py-3 border text-center">Consumo Promedio (kg/día)</th>
                          <th class="px-4 py-3 border text-center">Stock Real (kg)</th>
                          <th class="px-4 py-3 border text-center">Stock Deseado (kg) - 2 Semanas</th>
                          <th class="px-4 py-3 border text-center">Stock Óptimo (kg)</th>
                      </tr>
                  </thead>
                  <tbody class="text-gray-700">
                      @forelse($stockOptimo as $stock)
                          <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                              <td class="px-4 py-3 text-center border">{{ number_format($stock->diametro, 2) }}</td>
                              <td class="px-4 py-3 text-center border">{{ number_format($stock->consumo_promedio, 2) }}
                              </td>
                              <td class="px-4 py-3 text-center border">{{ number_format($stock->stock_real, 2) }}</td>
                              <td class="px-4 py-3 text-center border">{{ number_format($stock->stock_deseado, 2) }}
                              </td>
                              <td class="px-4 py-3 text-center border">{{ number_format($stock->stock_optimo, 2) }}
                              </td>
                          </tr>
                      @empty
                          <tr>
                              <td colspan="5" class="text-red-600 px-4 py-3 text-center">
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
