<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Confirmación de pedido</title>
</head>

<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: 'Segoe UI', sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f5; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0"
                    style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 15px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr style="background-color: #1f2937;">
                        <td style="padding: 20px 30px;">
                            <img src="{{ asset('imagenes/ico/android-chrome-192x192.png') }}" alt="Logo"
                                width="48" style="vertical-align: middle; border-radius: 6px; margin-right: 12px;">
                            <h1 style="color: #CE1F23; font-size: 24px; margin: 0;">Hierros Paco Reyes</h1>
                            <p style="color: #d1d5db; font-size: 14px; margin: 4px 0 0;">Especialistas en ferrallado
                                industrial</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 30px;">
                            <h2 style="color: #1f2937; font-size: 20px; margin-bottom: 10px;">📦 Confirmación de pedido
                            </h2>

                            <p style="font-size: 16px; color: #374151; margin-bottom: 20px;">
                                Estimado proveedor, {{ $pedido->proveedor->nombre }}<br>
                                Le informamos que se ha generado un nuevo pedido a fecha
                                {{ $pedido->created_at->format('d/m/Y') }}
                                con
                                los siguientes datos:
                            </p>

                            <table style="width: 100%; font-size: 15px; color: #111827; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 8px 0;"><strong>Código:</strong></td>
                                    <td style="padding: 8px 0;">{{ $pedido->codigo }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0;"><strong>Fecha de entrega:</strong></td>
                                    <td style="padding: 8px 0;">
                                        {{ \Carbon\Carbon::parse($pedido->fecha_entrega)->format('d/m/Y') }}</td>

                                </tr>
                            </table>

                            <h3 style="color: #1f2937; margin-bottom: 8px;">📋 Productos solicitados:</h3>

                            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                                <thead>
                                    <tr style="background-color: #374151; color: #ffffff;">
                                        <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: left;">Producto
                                        </th>
                                        <th style="padding: 10px; border: 1px solid #e5e7eb; text-align: right;">
                                            Cantidad (kg)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pedido->productos as $producto)
                                        <tr style="background-color: #f9fafb;">
                                            <td style="padding: 10px; border: 1px solid #e5e7eb;">
                                                {{ ucfirst($producto->tipo) }} - {{ $producto->diametro }} mm
                                                @if (!empty($producto->longitud))
                                                    / {{ $producto->longitud }} m
                                                @endif
                                            </td>
                                            <td style="padding: 10px; border: 1px solid #e5e7eb; text-align: right;">
                                                {{ number_format($producto->pivot->cantidad, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <p style="margin-top: 30px; font-size: 14px; color: #6b7280;">
                                Por favor, confirme la recepción de este pedido respondiendo a este correo.
                                Si tiene cualquier duda, no dude en contactar con nuestro departamento de compras
                                (<a href="mailto:compras@pacoreyes.com"
                                    style="color: #6b7280;">compras@pacoreyes.com</a>).
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr style="background-color: #111827;">
                        <td style="padding: 20px 30px; text-align: center;">
                            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                                © {{ date('Y') }} Hierros Paco Reyes · Todos los derechos reservados
                            </p>
                            <p style="color: #9ca3af; font-size: 12px; margin: 5px 0 0;">
                                Este mensaje ha sido generado automáticamente.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    @isset($esVistaPrevia)
        <div style="position: absolute; top: 20px; right: 20px; z-index: 999; display: flex; gap: 10px;">
            <form action="{{ route('pedidos.enviarCorreo', $pedido->id) }}" method="POST">
                @csrf
                <!-- CC input aquí -->
                <div style="margin-bottom: 12px;">
                    <label for="cc" style="font-weight: 600; color: #374151;">Enviar también a (CC):</label><br>
                    <input type="text" name="cc" id="cc"
                        placeholder="correo1@ejemplo.com, correo2@ejemplo.com"
                        style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #d1d5db; margin-top: 6px;">
                </div>
                <button type="submit"
                    style="
                    background-color: #2563eb;
                    color: #fff;
                    padding: 10px 18px;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 600;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
                    transition: background-color 0.3s, box-shadow 0.3s;
                "
                    onmouseover="this.style.backgroundColor='#1d4ed8'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)'"
                    onmouseout="this.style.backgroundColor='#2563eb'; this.style.boxShadow='0 2px 5px rgba(0,0,0,0.15)'">
                    ✉️ Enviar correo al proveedor
                </button>
            </form>

            <form id="formCancelarPedido" action="{{ route('pedidos.destroy', $pedido->id) }}" method="POST"
                style="margin: 0;">
                @csrf
                @method('DELETE')
                <button type="button" id="btnCancelar"
                    style="
                        background-color: #f3f4f6;
                        color: #374151;
                        padding: 10px 18px;
                        border: none;
                        border-radius: 6px;
                        font-size: 14px;
                        font-weight: 600;
                        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                        transition: all 0.3s ease;"
                    onmouseover="this.style.backgroundColor='#e5e7eb'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)'"
                    onmouseout="this.style.backgroundColor='#f3f4f6'; this.style.boxShadow='0 2px 5px rgba(0,0,0,0.1)'">
                    ❌ Cancelar y eliminar pedido
                </button>
            </form>

        </div>
    @endisset

</body>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.getElementById('btnCancelar').addEventListener('click', function() {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "El pedido será eliminado permanentemente.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formCancelarPedido').submit();
            }
        });
    });
</script>

</html>
