<x-app-layout>
    <x-slot name="title">Movimientos - {{ config('app.name') }}</x-slot>

    <x-page-header
        title="Movimientos de Inventario"
        subtitle="Historial de entradas, salidas y transferencias"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>'
    />

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
