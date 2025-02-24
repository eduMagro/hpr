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
            let latitud = position.coords.latitude;
            let longitud = position.coords.longitude;

            console.log(`📍 Coordenadas obtenidas: Latitud ${latitud}, Longitud ${longitud}`);

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
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content') // ✅ OBTENIENDO EL TOKEN DEL LAYOUT
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
                        Swal.fire({
                            icon: 'success',
                            title: 'Fichaje registrado',
                            text: data.success,
                        });
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
