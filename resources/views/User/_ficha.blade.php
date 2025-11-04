@php
    $esOficina = Auth::check() && Auth::user()->rol === 'oficina';
@endphp

{{-- BOTONES DE FICHAJE (solo operarios) --}}
@if (!$esOficina)
    <div class="flex justify-between items-center w-full gap-4 p-4">
        <button onclick="registrarFichaje('entrada')"
            class="w-full py-2 px-4 bg-green-600 text-white rounded-md btn-cargando">
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            <span class="texto">Entrada</span>
        </button>

        <button onclick="registrarFichaje('salida')"
            class="w-full py-2 px-4 bg-red-600 text-white rounded-md btn-cargando">
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            <span class="texto">Salida</span>
        </button>
    </div>
@endif

{{-- FICHA --}}
<x-ficha-trabajador :user="$user" :resumen="$resumen" />

{{-- JS de fichaje (solo operarios) --}}
@if (!$esOficina)
    <script>
        function registrarFichaje(tipo) {
            const boton = event.currentTarget;
            const textoOriginal = boton.querySelector('.texto').textContent;

            boton.disabled = true;
            boton.querySelector('.texto').textContent = 'Obteniendo ubicación…';
            boton.classList.add('opacity-50', 'cursor-not-allowed');

            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    const latitud = pos.coords.latitude;
                    const longitud = pos.coords.longitude;

                    Swal.fire({
                        title: 'Confirmar Fichaje',
                        text: `¿Quieres registrar una ${tipo}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, fichar',
                        cancelButtonText: 'Cancelar'
                    }).then((res) => {
                        if (!res.isConfirmed) {
                            reset();
                            return;
                        }

                        fetch("{{ url('/fichar') }}", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                },
                                body: JSON.stringify({
                                    user_id: "{{ auth()->id() }}",
                                    tipo,
                                    latitud,
                                    longitud
                                })
                            })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: data.success,
                                        text: `📍 Lugar: ${data.obra_nombre}`,
                                        timer: 3000,
                                        showConfirmButton: false
                                    });
                                    if (window.calendar) window.calendar.refetchEvents();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.error || 'Error desconocido'
                                    });
                                }
                            })
                            .catch(() => Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo comunicar con el servidor'
                            }))
                            .finally(reset);
                    });

                    function reset() {
                        boton.disabled = false;
                        boton.querySelector('.texto').textContent = textoOriginal;
                        boton.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                },
                function(err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de ubicación',
                        text: err.message
                    });
                    boton.disabled = false;
                    boton.querySelector('.texto').textContent = textoOriginal;
                    boton.classList.remove('opacity-50', 'cursor-not-allowed');
                }, {
                    enableHighAccuracy: false,
                    timeout: 8000,
                    maximumAge: 60000
                }
            );
        }
    </script>
@endif
