<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Trazabilidad de Salida</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #aaa;
            padding: 6px;
            text-align: center;
        }

        th {
            background-color: #ddd;
        }

        .left {
            text-align: left;
        }

        img.logo {
            height: 60px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <img src="{{ public_path('imagenes/ico/android-chrome-192x192.png') }}" alt="Logo" class="logo">
    <h1>Informe de Trazabilidad</h1>
    <p><strong>Salida:</strong> {{ $salida->codigo_salida }}</p>
    <p><strong>Fecha:</strong> {{ optional($salida->updated_at)->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>CÓDIGO SAGE</th>
                <th>PLANILLA</th>
                <th class="left">DESCRIPCIÓN</th>
                <th>Ø mm</th>
                <th>PESO TOTAL</th>
                <th class="left">COLADAS</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($filasTrazabilidad as $fila)
                <tr>
                    <td>{{ $fila['codigo_sage'] }}</td>
                    <td>{{ $fila['codigo_planilla'] }}</td>
                    <td class="left">{{ $fila['descripcion'] }}</td>
                    <td>{{ $fila['diametro'] }}</td>
                    <td>{{ number_format($fila['peso_total'], 2, ',', '.') }} kg</td>
                    <td class="left">{{ implode(', ', $fila['coladas']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
