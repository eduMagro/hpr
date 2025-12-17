<x-app-layout>
    <x-slot name="title">Movimientos - {{ config('app.name') }}</x-slot>

    <div class="w-full px-6 py-4">

        @livewire('movimientos-table')
        <!-- SCRIPT PARA IMPRIMIR QR -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <div id="qrCanvas" class="hidden"></div>
        <script>
            // Función global para impresión de QR (por si se necesita en el futuro)
            // Requiere <div id="qrCanvas"></div>
            window.generateAndPrintQR = function(data) {
                const safeData = data.replace(/_/g, '%5F');
                const qrContainer = document.getElementById('qrCanvas');

                if (!qrContainer) {
                    console.error("Contenedor qrCanvas no encontrado");
                    return;
                }

                qrContainer.innerHTML = "";

                try {
                    new QRCode(qrContainer, {
                        text: safeData,
                        width: 200,
                        height: 200,
                    });

                    setTimeout(() => {
                        const qrImg = qrContainer.querySelector('img');
                        if (!qrImg) {
                            alert("Error al generar el QR.");
                            return;
                        }

                        const printWindow = window.open('', '_blank');
                        if (printWindow) {
                            printWindow.document.write(`
                                <html>
                                    <head><title>Imprimir QR</title></head>
                                    <body>
                                        <img src="${qrImg.src}" alt="Código QR" style="width:100px">
                                        <script>
                                            window.onload = function() {
                                                window.print();
                                                setTimeout(function() { window.close(); }, 500);
                                            }
                                        <\/script>
                                    </body>
                                </html>
                            `);
                            printWindow.document.close();
                        }
                    }, 500);
                } catch (e) {
                    console.error("Error QRCode:", e);
                }
            }
        </script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</x-app-layout>
