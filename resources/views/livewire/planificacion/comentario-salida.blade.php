<div>
    @if($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay -->
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" wire:click="cerrar"></div>

                <!-- Modal -->
                <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-blue-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </div>
                            <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                    Agregar comentario
                                </h3>
                                <div class="mt-4">
                                    <div class="relative">
                                        <textarea
                                            wire:model.live="comentario"
                                            id="comentario-textarea"
                                            rows="5"
                                            maxlength="{{ $maxLength }}"
                                            class="w-full px-3 py-2 text-gray-700 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                            placeholder="Escribe tu comentario aquí..."></textarea>

                                        <!-- Contador de caracteres -->
                                        <div class="flex justify-between mt-2 text-sm">
                                            <span class="text-gray-500">
                                                @if(strlen($comentario) > $maxLength * 0.8)
                                                    <span class="text-orange-600 font-medium">
                                                        {{ strlen($comentario) }} / {{ $maxLength }} caracteres
                                                    </span>
                                                @else
                                                    {{ strlen($comentario) }} / {{ $maxLength }} caracteres
                                                @endif
                                            </span>
                                        </div>

                                        @error('comentario')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            wire:click="guardar"
                            type="button"
                            class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                Guardar
                            </span>
                            <span wire:loading>
                                <svg class="inline w-4 h-4 mr-2 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Guardando...
                            </span>
                        </button>
                        <button
                            wire:click="cerrar"
                            type="button"
                            class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            wire:loading.attr="disabled">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
