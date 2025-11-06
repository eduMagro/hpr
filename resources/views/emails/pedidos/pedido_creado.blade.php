<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Confirmación de pedido</title>
    <style>
        @page {
            margin: 24px;
        }

        html,
        body {
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
        }

        .wrap {
            width: 100%;
            max-width: 720px;
            margin: 0 auto;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }

        .hdr {
            background: #1f2937;
            color: #fff;
            padding: 18px 24px;
        }

        .hdr h1 {
            margin: 0;
            font-size: 22px;
            color: #CE1F23;
        }

        .hdr p {
            margin: 6px 0 0;
            color: #d1d5db;
            font-size: 12px;
        }

        .body {
            padding: 24px;
        }

        h2,
        h3 {
            margin: 0 0 10px;
            color: #1f2937;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }

        th,
        td {
            border: 1px solid #e5e7eb;
            padding: 10px;
        }

        thead th {
            background: #374151;
            color: #fff;
            text-align: left;
        }

        .muted {
            color: #6b7280;
            font-size: 12px;
            margin-top: 16px;
        }

        .ftr {
            background: #111827;
            color: #9ca3af;
            text-align: center;
            padding: 16px 24px;
            font-size: 12px;
        }

        .codigo-linea {
            font-weight: 600;
            color: #1f2937;
            font-size: 13px;
        }

        .info-box {

            padding: 12px;
            margin: 10px 0;
        }

        .info-box-title {
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .info-box-content {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.5;
        }

        .lugar-entrega {

            padding: 12px;
            margin: 10px 0;
        }

        .lugar-entrega-title {
            font-weight: 600;

            margin-bottom: 6px;
            font-size: 14px;
        }

        .lugar-entrega-nombre {
            font-weight: 600;
            color: #1f2937;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .lugar-entrega-direccion {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .maps-link {
            display: inline-block;
            color: #2563eb;
            text-decoration: underline;
            font-size: 13px;
            margin-top: 4px;
        }

        /* Estilos para el formulario de previsualización */
        .preview-form-box {

            padding: 16px;
            margin: 15px 0;
        }

        .preview-form-title {
            font-weight: 600;
            color: #92400e;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .preview-textarea {
            width: 100%;
            min-height: 80px;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 13px;
            font-family: Arial, Helvetica, sans-serif;
            resize: vertical;
        }

        .preview-button {
            background-color: #059669;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            margin-top: 8px;
        }

        .preview-button:hover {
            background-color: #047857;
        }

        .preview-note {
            font-size: 12px;
            color: #78716c;
            font-style: italic;
            margin-top: 6px;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <div class="hdr">
                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="vertical-align:middle; padding-right:12px; width:48px;">
                            <img src="{{ public_path('imagenes/ico/android-chrome-192x192.png') }}" alt="Logo"
                                width="40" height="40" style="border-radius:6px;">
                        </td>
                        <td style="vertical-align:middle;">
                            <h1>Hierros Paco Reyes</h1>
                            <p>Especialistas en ferrallado industrial</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="body">
                @php
                    $proveedorNombre = $pedido->fabricante->nombre ?? ($pedido->distribuidor->nombre ?? 'Proveedor');

                    // Obtener lugar de entrega de la primera línea (todas tienen el mismo)
                    $primeraLinea = $pedido->productos->first();
                    $obraId = $primeraLinea ? $primeraLinea->pivot->obra_id : null;
                    $obraManual = $primeraLinea ? $primeraLinea->pivot->obra_manual : null;
                    $obra = $obraId ? \App\Models\Obra::find($obraId) : null;
                @endphp

                <h2>Confirmación de pedido</h2>
                <p style="font-size:14px; color:#374151; margin-bottom:16px;">
                    Estimado proveedor, {{ $proveedorNombre }}.<br>
                    Le informamos que se ha generado un nuevo pedido a fecha {{ $pedido->created_at->format('d/m/Y') }}
                    con los siguientes datos:
                </p>

                <table style="border:0; margin-bottom:10px;">
                    <tr>
                        <td style="border:0; padding:4px 0;"><strong>Código:</strong> {{ $pedido->codigo }}</td>
                    </tr>
                </table>

                {{-- LUGAR DE ENTREGA GENERAL --}}
                <div class="lugar-entrega">
                    <div class="lugar-entrega-title">Lugar de entrega</div>
                    @if ($obra)
                        <div class="lugar-entrega-nombre">{{ $obra->obra }}</div>
                        <div class="lugar-entrega-direccion">
                            {{ $obra->ciudad ?? '' }} - {{ $obra->direccion ?? 'Sin dirección especificada' }}
                        </div>
                        @if ($obra->latitud && $obra->longitud)
                            @php
                                $lat = number_format((float) $obra->latitud, 6, '.', '');
                                $lng = number_format((float) $obra->longitud, 6, '.', '');
                                $mapsUrl = "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}";
                            @endphp
                            <a href="{{ $mapsUrl }}" class="maps-link">
                                Ver ubicación en Google Maps
                            </a>
                        @endif
                    @elseif ($obraManual)
                        <div class="lugar-entrega-nombre">{{ $obraManual }}</div>
                    @else
                        <div class="lugar-entrega-direccion" style="font-style: italic;">
                            No especificado
                        </div>
                    @endif
                </div>

                {{-- FORMULARIO DE OBSERVACIONES (solo en previsualización) --}}
                @if (isset($esVistaPrevia) && $esVistaPrevia === true)
                    <div class="preview-form-box">
                        <form action="{{ route('pedidos.actualizarObservaciones', $pedido->id) }}" method="POST"
                            id="formObservaciones">
                            @csrf
                            @method('PATCH')
                            <div class="preview-form-title">✏️ Observaciones del pedido</div>
                            <textarea name="observaciones" id="observaciones" class="preview-textarea"
                                placeholder="Escribe aquí cualquier observación o instrucción especial para el proveedor...">{{ old('observaciones', $pedido->observaciones) }}</textarea>
                            <button type="submit" class="preview-button">💾 Guardar observaciones</button>
                            <div class="preview-note">
                                Las observaciones se incluirán en el PDF que se enviará al proveedor
                            </div>
                        </form>
                    </div>
                @else
                    {{-- OBSERVACIONES (solo mostrar si existen y NO es previsualización) --}}
                    @if (!empty($pedido->observaciones))
                        <div class="info-box">
                            <div class="info-box-title">📝 Observaciones</div>
                            <div class="info-box-content">
                                {{ $pedido->observaciones }}
                            </div>
                        </div>
                    @endif
                @endif

                <h3>Productos solicitados</h3>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 20%;">Código Línea</th>
                            <th style="width: 40%;">Producto</th>
                            <th style="text-align:right; width: 20%;">Cantidad (kg)</th>
                            <th style="text-align:right; width: 20%;">Fecha entrega</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pedido->productos as $producto)
                            @php
                                $codigoLinea = $producto->pivot->codigo ?? '—';
                            @endphp
                            <tr>
                                <td>
                                    <span class="codigo-linea">{{ $codigoLinea }}</span>
                                </td>
                                <td>
                                    {{ ucfirst($producto->tipo) }} - Ø{{ $producto->diametro }} mm
                                    @if (!empty($producto->longitud))
                                        / {{ $producto->longitud }} m
                                    @endif
                                </td>
                                <td style="text-align:right;">
                                    {{ number_format($producto->pivot->cantidad, 2, ',', '.') }}
                                </td>
                                <td style="text-align:right;">
                                    {{ \Carbon\Carbon::parse($producto->pivot->fecha_estimada_entrega)->format('d/m/Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p class="muted">
                    Por favor, confirme la recepción de este pedido respondiendo a este correo.
                    Si tiene cualquier duda, contacte con Compras (info@pacoreyes.eu).
                </p>
            </div>

            <div class="ftr">
                © {{ date('Y') }} Hierros Paco Reyes · Todos los derechos reservados<br>
                Este documento ha sido generado automáticamente.
            </div>
        </div>
    </div>

    {{-- Botones solo en PREVISUALIZACIÓN --}}
    @if (isset($esVistaPrevia) && $esVistaPrevia === true)
        <div style="position: fixed; top: 20px; right: 20px; z-index: 999; display: flex; gap: 10px;">
            <form action="{{ route('pedidos.crearEnviarCorreo', $pedido->id) }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit"
                    style="background-color:#2563eb;color:#fff;padding:10px 18px;border:none;border-radius:6px;cursor:pointer;font-size:14px;font-weight:600;box-shadow:0 2px 5px rgba(0,0,0,0.15);">
                    ✉️ Enviar correo al proveedor
                </button>
            </form>

            <form id="formCancelarPedido" action="{{ route('pedidos.destroy', $pedido->id) }}" method="POST"
                style="margin:0;">
                @csrf
                @method('DELETE')
                <button type="button" id="btnCancelar"
                    style="background-color:#f3f4f6;color:#374151;padding:10px 18px;border:none;border-radius:6px;font-size:14px;font-weight:600;box-shadow:0 2px 5px rgba(0,0,0,0.1);">
                    ❌ Cancelar y eliminar pedido
                </button>
            </form>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.getElementById('btnCancelar')?.addEventListener('click', function() {
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: 'El pedido será eliminado permanentemente.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((r) => r.isConfirmed && document.getElementById('formCancelarPedido').submit());
            });

            // Mensaje de éxito al guardar observaciones
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: '¡Guardado!',
                    text: '{{ session('success') }}',
                    timer: 2000,
                    showConfirmButton: false
                });
            @endif
        </script>
    @endif

</body>

</html>
