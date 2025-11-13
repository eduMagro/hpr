<x-app-layout>
    <x-slot name="title">Movimientos - {{ config('app.name') }}</x-slot>

    <div class="w-full px-6 py-4">

        @livewire('movimientos-table')
        <!-- SCRIPT PARA IMPRIMIR QR -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
            function generateAndPrintQR(data) {
                // Reemplazamos los caracteres problemáticos antes de generar el QR
                const safeData = data.replace(/_/g, '%5F'); // Reemplazamos _ por %5F

                // Elimina cualquier contenido previo del canvas
                const qrContainer = document.getElementById('qrCanvas');
                qrContainer.innerHTML = ""; // Limpia el canvas si ya existe un QR previo

                // Generar el código QR con el texto seguro
                const qrCode = new QRCode(qrContainer, {
                    text: safeData, // Usamos el texto transformado
                    width: 200,
                    height: 200,
                });

                // Esperar a que el QR esté listo para imprimir
                setTimeout(() => {
                    const qrImg = qrContainer.querySelector('img'); // Obtiene la imagen del QR
                    if (!qrImg) {
                        alert("Error al generar el QR. Intenta nuevamente.");
                        return;
                    }

                    // Abrir ventana de impresión
                    const printWindow = window.open('', '_blank');
                    printWindow.document.write(`
                  <html>
                      <head><title>Imprimir QR</title></head>
                      <body>
                          <img src="${qrImg.src}" alt="Código QR" style="width:100px">
                   
                          <script>window.print();<\/script>
                      </body>
                  </html>
              `);
                    printWindow.document.close();
                }, 500); // Tiempo suficiente para generar el QR
            }
        </script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                let form = document.getElementById('miFormulario'); // Asegúrate de poner el ID correcto del formulario

                form.addEventListener("submit", function(event) {
                    event.preventDefault(); // Evita el envío inmediato

                    let formData = new FormData(form);

                    fetch(form.action, {
                            method: form.method,
                            body: formData,
                            headers: {
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.confirm) {
                                Swal.fire({
                                    title: 'Material en fabricación',
                                    text: data.message,
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: 'Sí, continuar',
                                    cancelButtonText: 'Cancelar'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        form
                                            .submit(); // Si el usuario confirma, enviar el formulario
                                    }
                                });
                            } else {
                                form.submit();
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });
            });
        </script>

</x-app-layout>
