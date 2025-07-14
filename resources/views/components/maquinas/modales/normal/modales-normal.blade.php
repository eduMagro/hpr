 <!-- Modal para Cambiar Estado de la Máquina -->
 <div id="modalIncidencia" class="hidden fixed inset-0 z-50 bg-gray-900 bg-opacity-50 flex items-center justify-center">
     <div class="bg-white w-full max-w-md mx-auto rounded-xl shadow-lg overflow-hidden transform transition-all">
         <form method="POST" action="{{ route('maquinas.cambiarEstado', $maquina->id) }}" class="p-6 space-y-4">
             @csrf

             <!-- Título -->
             <h2 class="text-xl font-bold text-gray-800 text-center">Cambiar estado de la máquina</h2>

             <!-- Selección de estado -->
             <div>
                 <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Selecciona el
                     nuevo estado:</label>
                 <select id="estado" name="estado"
                     class="w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-400">
                     <option value="activa" {{ $maquina->estado === 'activa' ? 'selected' : '' }}>Activa
                     </option>
                     <option value="averiada" {{ $maquina->estado === 'averiada' ? 'selected' : '' }}>
                         Averiada</option>
                     <option value="pausa" {{ $maquina->estado === 'pausa' ? 'selected' : '' }}>Pausa
                     </option>
                     <option value="mantenimiento" {{ $maquina->estado === 'mantenimiento' ? 'selected' : '' }}>
                         Mantenimiento</option>
                 </select>
             </div>

             <!-- Botones -->
             <div class="flex justify-end space-x-2 pt-2">
                 <button type="button" onclick="document.getElementById('modalIncidencia').classList.add('hidden')"
                     class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg">
                     Cancelar
                 </button>
                 <button type="submit"
                     class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg">
                     Guardar
                 </button>
             </div>
         </form>
     </div>
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
