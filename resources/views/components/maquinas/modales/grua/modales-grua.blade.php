  {{-- 🔄 MODAL MOVIMIENTO GENERAL --}}
  <div id="modalMovimientoGeneral"
      class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
      <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-lg">
          <h2 class="text-lg sm:text-xl font-bold mb-4 text-center text-gray-800">
              ➕ Nuevo Movimiento
          </h2>

          <form method="POST" action="{{ route('movimientos.store') }}" id="form-movimiento-general">
              @csrf
              <input type="hidden" name="tipo" value="movimiento libre">

              <!-- Producto -->
              <x-tabla.input-movil name="codigo_general" id="codigo_general_general" label="Escanear Producto o Paquete"
                  placeholder="Escanea QR" autocomplete="off" inputmode="none" required />

              <!-- Ubicación destino (campo libre) -->
              <div class="mt-4">
                  <x-tabla.input-movil name="ubicacion_destino" id="ubicacion_destino_general"
                      label="Escanear Ubicación destino" placeholder="Escanea ubicación o escribe código"
                      autocomplete="off" inputmode="none" />
              </div>

              <!-- Máquina destino (select filtrado por obra_id de la grúa) -->
              <div class="mt-4">
                  <label for="maquina_destino" class="block text-sm font-medium text-gray-700">Máquina destino</label>
                  <select name="maquina_destino" id="maquina_destino"
                      class="w-full border border-gray-300 rounded text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      style="height:2cm; padding:0.75rem 1rem; font-size:1.5rem;">
                      <option value="">-- Selecciona máquina --</option>
                      @foreach ($maquinasDisponibles as $maq)
                          <option value="{{ $maq->id }}">
                              {{ $maq->nombre }}
                          </option>
                      @endforeach
                  </select>

              </div>

              <!-- Botones -->
              <div class="flex justify-end gap-3 mt-6">
                  <button type="button" onclick="cerrarModalMovimientoGeneral()"
                      class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg">Cancelar</button>
                  <button type="submit"
                      class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Registrar</button>
              </div>
          </form>
      </div>
  </div>
  <script>
      document.addEventListener('DOMContentLoaded', function() {
          // Selecciona solo los inputs del modal movimiento general
          const inputs = document.querySelectorAll('#modalMovimientoGeneral input');

          inputs.forEach(input => {
              input.addEventListener('keydown', function(e) {
                  if (e.key === 'Enter') {
                      e.preventDefault(); // ⛔ Bloquea el enter automático del escáner
                      // No hace ni submit ni salta a otro campo
                  }
              });
          });
      });
  </script>

  {{-- 🔄 MODAL BAJADA PAQUETE --}}
  <div id="modal-bajada-paquete"
      class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
          <h2 class="text-xl font-bold mb-4">Reubicar paquete</h2>

          <p class="mb-2 text-sm text-gray-700"><strong>Descripción:</strong> <span id="descripcion_paquete"></span></p>

          <form method="POST" action="{{ route('movimientos.store') }}">
              @csrf

              {{-- <input type="hidden" name="tipo" value="bajada de paquete"> --}}
              <input type="hidden" name="movimiento_id" id="movimiento_id">
              <input type="hidden" name="paquete_id" id="paquete_id">
              <input type="hidden" name="ubicacion_origen" id="ubicacion_origen">
              <!-- Escanear paquete -->
              <x-tabla.input-movil id="codigo_general" name="codigo_general" placeholder="ESCANEA PAQUETE"
                  inputmode="none" autocomplete="off" />
              <p id="estado_verificacion" class="text-sm mt-1"></p>

              <!-- Ubicación destino -->
              <x-tabla.input-movil id="ubicacion_destino" name="ubicacion_destino" placeholder="ESCANEA UBICACIÓN"
                  required />

              <!-- Botones -->
              <div class="flex justify-end gap-3 mt-6">
                  <button type="button" onclick="cerrarModalBajadaPaquete()"
                      class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg">Cancelar</button>
                  <button type="submit"
                      class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Registrar</button>
              </div>
          </form>
      </div>
  </div>
  {{-- 🔄 MODAL RECARGA MP --}}
  <div id="modalMovimiento" class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden items-center justify-center">
      <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-md mx-0 sm:mx-0">

          <h2 class="text-lg sm:text-xl font-bold mb-4 text-center text-gray-800">
              RECARGAR MÁQUINA
          </h2>

          <!-- Información tipo tabla -->
          <div class="grid grid-cols-2 gap-3 mb-4 text-sm sm:text-base text-gray-700">
              <div class="bg-gray-100 rounded-lg p-3 shadow-sm">
                  <p class="font-semibold text-gray-600 text-xs sm:text-sm"><i class="fas fa-industry"></i>
                      {{-- fa-industry --}}
                  </p>
                  <p id="maquina-nombre-destino" class="text-green-700 font-bold text-lg sm:text-xl mt-1"></p>
              </div>
              <div class="bg-gray-100 rounded-lg p-3 shadow-sm">
                  <p class="font-semibold text-gray-600 text-xs sm:text-sm">🧱 Tipo</p>
                  <p id="producto-tipo" class="text-gray-800 font-bold text-xl mt-1"></p>
              </div>
              <div class="bg-gray-100 rounded-lg p-3 shadow-sm">
                  <p class="font-semibold text-gray-600 text-xs sm:text-sm">⌀ Diámetro</p>
                  <p id="producto-diametro" class="text-gray-800 font-bold text-lg sm:text-xl mt-1">
                  </p>
              </div>
              <div class="bg-gray-100 rounded-lg p-3 shadow-sm">
                  <p class="font-semibold text-gray-600 text-xs sm:text-sm">📏 Longitud</p>
                  <p id="producto-longitud" class="text-gray-800 font-bold text-lg sm:text-xl mt-1">
                  </p>
              </div>
          </div>

          <!-- Ubicaciones sugeridas -->
          <div id="ubicaciones-actuales" class="mb-4 hidden">
              <div class="border-t pt-3">
                  <label class="font-semibold block mb-2 text-gray-700 text-sm sm:text-base">
                      📍 Ubicaciones con producto disponible
                  </label>
                  <ul id="ubicaciones-lista" class="list-disc list-inside text-gray-700 text-sm pl-4 space-y-1"></ul>
              </div>
          </div>

          <!-- Formulario -->
          <form method="POST" action="{{ route('movimientos.store') }}" id="form-ejecutar-movimiento">
              @csrf
              <input type="hidden" name="tipo" id="modal_tipo">
              <input type="hidden" name="producto_base_id" id="modal_producto_base_id">
              <input type="hidden" name="maquina_destino" id="modal_maquina_id">

              <x-tabla.input-movil type="text" name="codigo_general" id="modal_producto_id"
                  placeholder="ESCANEA QR MATERIA PRIMA" inputmode="none" autocomplete="off" required />

              <!-- Botones -->
              <div class="flex justify-end gap-3 mt-6">
                  <button type="button" onclick="cerrarModalRecargaMateriaPrima()"
                      class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg">Cancelar</button>
                  <button type="submit"
                      class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Registrar</button>
              </div>

          </form>
      </div>
  </div>
  {{-- 🔄 MODAL DESCARGA MATERIA PRIMA --}}
  <div id="modal-ver-pedido"
      class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
      <div class="bg-white w-full max-w-2xl rounded shadow-lg p-6 relative">
          <button onclick="cerrarModalPedido()"
              class="absolute top-2 right-2 text-gray-500 hover:text-black text-xl">&times;</button>

          <h2 class="text-xl font-bold mb-4">Pedido vinculado al movimiento</h2>

          <div id="contenidoPedido" class="space-y-3 text-sm">
              {{-- El contenido se rellena por JavaScript --}}
          </div>
      </div>
  </div>
  <script>
      function abrirModalRecargaMateriaPrima(id, tipo, productoCodigo, maquinaId, productoBaseId, ubicacionesSugeridas,
          maquinaNombre, tipoBase, diametroBase, longitudBase) {

          document.getElementById('modal_tipo').value = tipo;
          document.getElementById('modal_maquina_id').value = maquinaId;
          document.getElementById('modal_producto_id').value = productoCodigo; // ← aquí va el código
          document.getElementById('modal_producto_base_id').value = productoBaseId;

          document.getElementById('maquina-nombre-destino').textContent = maquinaNombre;
          document.getElementById('producto-tipo').textContent = tipoBase;
          document.getElementById('producto-diametro').textContent = `${diametroBase} mm`;
          document.getElementById('producto-longitud').textContent = `${longitudBase} mm`;

          const lista = document.getElementById('ubicaciones-lista');
          lista.innerHTML = '';

          if (ubicacionesSugeridas && ubicacionesSugeridas.length > 0) {
              document.getElementById('ubicaciones-actuales').classList.remove('hidden');
              ubicacionesSugeridas.forEach(u => {
                  const li = document.createElement('li');
                  li.textContent = `${u.nombre} (Código: ${u.codigo})`;

                  lista.appendChild(li);
              });
          } else {
              document.getElementById('ubicaciones-actuales').classList.add('hidden');
          }

          document.getElementById('modalMovimiento').classList.remove('hidden');
          document.getElementById('modalMovimiento').classList.add('flex');

          // Focus en el campo QR
          setTimeout(() => {
              document.getElementById("modal_producto_id")?.focus();
          }, 100);
      }


      function cerrarModalRecargaMateriaPrima() {
          document.getElementById('modalMovimiento').classList.add('hidden');
          document.getElementById('modalMovimiento').classList.remove('flex');
      }

      function abrirModalMovimientoLibre() {
          const modal = document.getElementById('modalMovimientoGeneral');
          modal.classList.remove('hidden');
          modal.classList.add('flex');
          setTimeout(() => {
              document.getElementById("codigo_general_general")?.focus();
          }, 100);
      }

      function cerrarModalMovimientoLibre() {
          const modal = document.getElementById('modalMovimientoGeneral');
          modal.classList.add('hidden');
          modal.classList.remove('flex');
      }


      // Mostrar/ocultar campos según tipo
      document.addEventListener('DOMContentLoaded', function() {
          const tipoSelect = document.getElementById('tipo');
          const productoSection = document.getElementById('producto-section');
          const paqueteSection = document.getElementById('paquete-section');

          tipoSelect.addEventListener('change', function() {
              if (this.value === 'producto') {
                  productoSection.classList.remove('hidden');
                  paqueteSection.classList.add('hidden');
              } else if (this.value === 'paquete') {
                  productoSection.classList.add('hidden');
                  paqueteSection.classList.remove('hidden');
              }
          });
      });

      let paqueteEsperadoId = null;

      function abrirModalBajadaPaquete(data) {
          document.getElementById('movimiento_id').value = data.id;
          document.getElementById('paquete_id').value = data.paquete_id;
          document.getElementById('ubicacion_origen').value = data.ubicacion_origen;
          document.getElementById('descripcion_paquete').innerText = data.descripcion;

          paqueteEsperadoId = data.paquete_id;
          document.getElementById('codigo_general').value = '';
          document.getElementById('estado_verificacion').innerText = '';
          document.getElementById('codigo_general').classList.remove('border-green-500', 'border-red-500');

          document.getElementById('modal-bajada-paquete').classList.remove('hidden');

          // Esperar un poco para que se renderice el DOM
          setTimeout(() => {
              const input = document.getElementById('codigo_general');
              if (input) input.focus();
          }, 100);
      }

      function cerrarModalBajadaPaquete() {
          document.getElementById('modal-bajada-paquete').classList.add('hidden');
      }

      function abrirModalPedidoDesdeMovimiento(movimiento) {
          if (!movimiento || !movimiento.pedido || !movimiento.pedido_producto) return;

          const pedido = movimiento.pedido;
          const linea = movimiento.pedido_producto; // 👈 línea de pedido
          const producto = movimiento.producto_base;

          const tipo = producto?.tipo ?? '—';
          const diametro = producto?.diametro ?? '—';
          const longitud = producto?.longitud ?? '—'; // suele venir en metros si es barra

          const proveedor = (pedido.fabricante_id && pedido.fabricante?.nombre) ?
              pedido.fabricante.nombre :
              (pedido.distribuidor?.nombre ?? '—');

          // Datos de línea
          const cantidadKg = Number(linea.cantidad ?? 0); // total pedido para esa línea
          const recepcionadoKg = Number(linea.cantidad_recepcionada ?? 0);
          const restanteKg = Math.max(0, cantidadKg - recepcionadoKg);
          const estadoLinea = linea.estado ?? '—';
          const fechaLinea = linea.fecha_estimada_entrega ?
              new Date(linea.fecha_estimada_entrega).toLocaleDateString('es-ES') :
              '—';

          // Info general del pedido (opcionales)
          const pesoPedidoRed = Math.round(pedido.peso_total || 0) + ' kg';

          // Formateo longitud según tipo
          const longitudFmt = (tipo === 'barra' && !isNaN(Number(longitud))) ?
              `${longitud} m` :
              (longitud ? `${longitud} mm` : '—');

          const contenedor = document.getElementById('contenidoPedido');
          const modal = document.getElementById('modal-ver-pedido');

          // ⚠️ Importante: pasamos pedido_producto_id por query para que el back lo coja.
          const hrefRecepcion =
              `/pedidos/${pedido.id}/recepcion/${producto?.id ?? movimiento.producto_base_id}?movimiento_id=${movimiento.id}`;


          contenedor.innerHTML = `
    <p><strong>Proveedor:</strong> ${proveedor}</p>
    <p><strong>Código Pedido:</strong> ${pedido.codigo ?? pedido.id}</p>

    <hr class="my-3" />

    <p><strong>Línea de pedido:</strong> #${linea.id}</p>
    <p><strong>Fecha estimada:</strong> ${fechaLinea}</p>
    <p><strong>Cantidad pedida:</strong> ${cantidadKg.toLocaleString('es-ES')} kg</p>

    <hr class="my-3" />

    <p><strong>Tipo:</strong> ${tipo}</p>
    <p><strong>Diámetro:</strong> ${diametro} mm</p>
    <p><strong>Longitud:</strong> ${longitudFmt}</p>

    <a href="${hrefRecepcion}"
       class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded shadow inline-block mt-4">
      RECEPCIONAR
    </a>
  `;

          modal.classList.remove('hidden');
      }



      function cerrarModalPedido() {
          document.getElementById('modal-ver-pedido').classList.add('hidden');
      }
  </script>
