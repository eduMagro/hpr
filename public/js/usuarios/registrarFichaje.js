function registrarFichaje(tipo) {
    console.log("🟢 Función `registrarFichaje` ejecutada para tipo:", tipo);

    if (!navigator.geolocation) {
        console.error("❌ Geolocalización no soportada en este navegador.");
        Swal.fire({
            icon: 'error',
            title: 'Geolocalización no disponible',
            text: '⚠️ Tu navegador no soporta geolocalización.',
        });
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            console.log("🟢 Callback ejecutado. Datos de posición:", position);

            let latitud = position.coords.latitude;
            let longitud = position.coords.longitude;

            console.log(`📍 Coordenadas obtenidas: Latitud ${latitud}, Longitud ${longitud}`);

            if (latitud === undefined || longitud === undefined) {
                console.error("❌ Error: No se pudieron obtener las coordenadas.");
                Swal.fire({
                    icon: 'error',
                    title: 'Error de ubicación',
                    text: 'No se pudieron obtener las coordenadas. Intenta nuevamente.',
                });
                return;
            }

            Swal.fire({
                title: 'Confirmar Fichaje',
                text: `¿Quieres registrar una ${tipo}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, fichar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log("🟢 Enviando datos al backend...");

                    fetch(fichajeRoute, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": csrfToken
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            tipo: tipo,
                            latitud: latitud,
                            longitud: longitud
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Error HTTP: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log("📩 Respuesta del servidor:", data);

                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Fichaje registrado',
                                text: data.success,
                            });
                        } else {
                            let errorMessage = data.error || 'Error desconocido';
                            if (data.messages) {
                                errorMessage = data.messages.join("\n");
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMessage,
                            });
                        }
                    })
                    .catch(error => {
                        console.error("❌ Error en la solicitud fetch:", error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo comunicar con el servidor.',
                        });
                    });
                }
            });
        },
        function(error) {
            console.error(`⚠️ Error de geolocalización: ${error.message}`);
            Swal.fire({
                icon: 'error',
                title: 'Error de ubicación',
                text: `⚠️ No se pudo obtener la ubicación: ${error.message}`,
            });
        }, {
            enableHighAccuracy: true
        }
    );
}
