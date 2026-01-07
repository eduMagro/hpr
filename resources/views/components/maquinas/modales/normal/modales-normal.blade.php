 <!-- Modal para Cambiar Estado de la Máquina -->
 <div id="modalIncidencia"
     class="hidden fixed inset-0 z-[9999] bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-4">
     <div class="bg-white w-full max-w-4xl mx-auto rounded-2xl shadow-2xl overflow-hidden transform transition-all"
         onclick="event.stopPropagation()">
         <form method="POST" action="{{ route('incidencias.store') }}" enctype="multipart/form-data"
             class="flex flex-col max-h-[90vh]">
             @csrf
             <input type="hidden" name="maquina_id" value="{{ $maquina->id }}">

             <!-- Header -->
             <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4 flex justify-between items-center shrink-0">
                 <h2 class="text-xl font-bold text-white flex items-center gap-2">
                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                             d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                     </svg>
                     Reportar Incidencia
                 </h2>
                 <button type="button" onclick="document.getElementById('modalIncidencia').classList.add('hidden')"
                     class="text-white/80 hover:text-white transition">
                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                             d="M6 18L18 6M6 6l12 12" />
                     </svg>
                 </button>
             </div>

             <!-- Body: Grid Layout for Horizontal Tablets/PC -->
             <div class="p-6 overflow-y-auto">
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-6 h-full">

                     {{-- Columna Izquierda: Foto (Full Height priority) --}}
                     <div class="flex flex-col h-full">
                         <label class="block text-sm font-bold text-gray-700 mb-2">Evidencia Fotográfica</label>
                         <div class="relative group flex-grow min-h-[250px] md:min-h-[300px]">
                             <label for="imagenIncidencia"
                                 class="flex flex-col items-center justify-center w-full h-full border-2 border-dashed border-gray-300 rounded-xl cursor-pointer bg-gray-50 hover:bg-red-50 hover:border-red-300 transition-all">
                                 <div id="uploadPlaceholder"
                                     class="flex flex-col items-center justify-center p-6 text-gray-400 group-hover:text-red-500">
                                     <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                     </svg>
                                     <p class="text-base font-bold">Tocar para tomar foto</p>
                                     <p class="text-sm text-center mt-2 opacity-70">Cámara o Galería</p>
                                 </div>
                                 <img id="previewIncidencia"
                                     class="hidden absolute inset-0 w-full h-full object-cover rounded-xl opacity-90" />
                             </label>
                             <input id="imagenIncidencia" name="imagen" type="file" accept="image/*"
                                 capture="environment" class="hidden" required onchange="previewImage(event)">
                         </div>
                     </div>

                     {{-- Columna Derecha: Campos --}}
                     <div class="space-y-5 flex flex-col justify-center">
                         <div>
                             <label for="titulo" class="block text-sm font-bold text-gray-700 mb-1">Título /
                                 Resumen</label>
                             <input type="text" name="titulo" id="titulo"
                                 placeholder="Ej: Ruido en motor, Fuga de aceite..." required
                                 class="w-full border-gray-300 rounded-lg shadow-sm focus:border-red-500 focus:ring-red-500 py-3">
                         </div>

                         <div>
                             <label for="descripcion" class="block text-sm font-bold text-gray-700 mb-1">Descripción
                                 Detallada <span class="text-gray-400 font-normal">(Opcional)</span></label>
                             <textarea id="descripcion" name="descripcion" rows="4"
                                 class="w-full border-gray-300 rounded-lg shadow-sm focus:border-red-500 focus:ring-red-500"
                                 placeholder="Describe el problema..."></textarea>
                         </div>

                         {{-- Estado de la Máquina --}}
                         <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                             <label for="estado_maquina" class="block text-sm font-bold text-gray-700 mb-2">Estado de la
                                 máquina tras la incidencia</label>
                             <select id="estado_maquina" name="estado_maquina"
                                 class="w-full border-gray-300 rounded-lg p-3 shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
                                 <option value="averiada" class="text-red-600 font-bold">⛔ INOPERATIVA (Averiada)
                                 </option>
                                 <option value="activa" {{ $maquina->estado === 'activa' ? 'selected' : '' }}
                                     class="text-green-600 font-bold">✅ OPERATIVA (Incidente menor/seguridad)</option>
                                 <option value="pausa">⏸️ EN PAUSA (Temporal)</option>
                                 <option value="mantenimiento">🔧 MANTENIMIENTO (Programado)</option>
                             </select>
                             <p class="text-xs text-gray-500 mt-2 flex items-start gap-1">
                                 <svg class="w-4 h-4 shrink-0 text-blue-500" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                         d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                 </svg>
                                 <span><strong>Inoperativa:</strong> Parada total. <strong>Operativa:</strong> Incidente
                                     leve.</span>
                             </p>
                         </div>
                     </div>
                 </div>
             </div>

             <!-- Footer Buttons -->
             <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200 shrink-0">
                 <button type="button" onclick="document.getElementById('modalIncidencia').classList.add('hidden')"
                     class="px-6 py-3 bg-white border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition shadow-sm text-base">
                     Cancelar
                 </button>
                 <button type="submit"
                     class="px-6 py-3 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 transition shadow-md shadow-red-200 flex items-center gap-2 text-base">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                             d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                     </svg>
                     Publicar Incidencia
                 </button>
             </div>
         </form>
     </div>
     <script>
         function previewImage(event) {
             const reader = new FileReader();
             reader.onload = function() {
                 const output = document.getElementById('previewIncidencia');
                 output.src = reader.result;
                 output.classList.remove('hidden');
                 document.getElementById('uploadPlaceholder').classList.add('opacity-0');
             };
             if (event.target.files[0]) {
                 reader.readAsDataURL(event.target.files[0]);
             }
         }
     </script>
 </div>
 </form>
 </div>
 <script>
     function previewImage(event) {
         const reader = new FileReader();
         reader.onload = function() {
             const output = document.getElementById('previewIncidencia');
             output.src = reader.result;
             output.classList.remove('hidden');
             document.getElementById('uploadPlaceholder').classList.add('opacity-0');
         };
         if (event.target.files[0]) {
             reader.readAsDataURL(event.target.files[0]);
         }
     }
 </script>
 </div>

 <!-- Modal Chequeo de Máquina (Oculto por defecto) -->
 <div id="modalCheckeo" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center">
     <div class="bg-white p-6 rounded-lg shadow-lg w-96">
         <h2 class="text-lg font-semibold text-gray-800 mb-4">🛠️ Chequeo de Máquina</h2>

         <form id="formCheckeo">
             <div class="space-y-2">
                 <label class="flex items-center">
                     <input type="checkbox" class="mr-2" name="limpieza">
                     🔹 Máquina limpia y sin residuos
                 </label>
                 <label class="flex items-center">
                     <input type="checkbox" class="mr-2" name="herramientas">
                     🔹 Herramientas en su ubicación correcta
                 </label>
                 <label class="flex items-center">
                     <input type="checkbox" class="mr-2" name="lubricacion">
                     🔹 Lubricación y mantenimiento básico realizado
                 </label>
                 <label class="flex items-center">
                     <input type="checkbox" class="mr-2" name="seguridad">
                     🔹 Elementos de seguridad en buen estado
                 </label>
                 <label class="flex items-center">
                     <input type="checkbox" class="mr-2" name="registro">
                     🔹 Registro de incidencias actualizado
                 </label>
             </div>

             <!-- Botones de acción -->
             <div class="flex justify-end mt-4">
                 <button type="button" onclick="document.getElementById('modalCheckeo').classList.add('hidden')"
                     class="mr-2 px-4 py-2 bg-gray-500 text-white rounded">
                     Cancelar
                 </button>
                 <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
                     Guardar Chequeo
                 </button>
             </div>
         </form>
     </div>
 </div>
 <script>
     //--------------------------------------------------------------------------------------------------------

     function abrirModalCambioElemento(elementoId) {
         document.getElementById('modalCambioMaquina').classList.remove('hidden');
         document.getElementById('cambio-elemento-id').value = elementoId;
     }

     function cerrarModalCambio() {
         document.getElementById('modalCambioMaquina').classList.add('hidden');

         // Limpiar correctamente los campos
         document.getElementById('motivoSelect').value = '';
         document.getElementById('motivoTexto').value = '';
         document.getElementById('maquinaDestino').value = '';
         document.getElementById('campoOtroMotivo').classList.add('hidden');
     }

     function mostrarCampoOtro() {
         const select = document.getElementById('motivoSelect');
         const campoOtro = document.getElementById('campoOtroMotivo');

         if (select.value === 'Otros') {
             campoOtro.classList.remove('hidden');
         } else {
             campoOtro.classList.add('hidden');
         }
     }

     function enviarCambioMaquina(event) {
         event.preventDefault();

         const elementoId = document.getElementById('cambio-elemento-id').value;
         let motivo = document.getElementById('motivoSelect').value;
         if (motivo === 'Otros') {
             motivo = document.getElementById('motivoTexto').value.trim();
         }
         const maquinaId = document.getElementById('maquinaDestino').value;
         // Puedes ajustar la URL y los headers si usas Axios
         fetch(`/elementos/${elementoId}/solicitar-cambio-maquina`, {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                 },
                 body: JSON.stringify({
                     motivo,
                     maquina_id: maquinaId
                 }),
             })
             .then(response => response.json())
             .then(data => {
                 alert(data.message || 'Solicitud enviada');
                 cerrarModalCambio();
             })
             .catch(error => {
                 console.error('Error:', error);
                 alert('Hubo un problema al enviar la solicitud.');
             });
     }
     //--------------------------------------------------------------------------------------------------------
     function confirmarEliminacion(actionUrl) {
         Swal.fire({
             title: '¿Quieres deshecharlo?',
             text: "¡No podrás revertir esta acción!",
             icon: 'warning',
             showCancelButton: true,
             confirmButtonColor: '#d33', // Color del botón de confirmar
             cancelButtonColor: '#3085d6', // Color del botón de cancelar
             confirmButtonText: 'Sí, deshechar',
             cancelButtonText: 'Cancelar'
         }).then((result) => {
             if (result.isConfirmed) {
                 const formulario = document.getElementById('formulario-eliminar');
                 formulario.action = actionUrl; // Asigna la ruta de consumir
                 formulario.submit(); // Envía el formulario
             }
         });
     }

     const maquinaId = @json($maquina->id);
     const ubicacionId = @json(optional($ubicacion)->id); // Esto puede ser null si no se encontró

     window.etiquetasData =
         @json($etiquetasData); // Ej.: [{ codigo: "3718", elementos: [27906,27907,...], pesoTotal: 155.55 }, ...]
     window.pesosElementos = @json($pesosElementos); // Ej.: { "27906": "77.81", "27907": "3.87", ... }
     //--------------------------------------------------------------------------------------------------------
     // console.log("Datos precargados de etiquetas:", window.etiquetasData);
     // console.log("Pesos precargados de elementos:", window.pesosElementos);
 </script>
