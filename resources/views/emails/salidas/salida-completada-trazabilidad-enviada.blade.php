<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Salida completada</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
    <div
        style="max-width: 600px; margin: auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <h2 style="color: #333;">✅ Salida completada</h2>


        <p>Se ha completado la salida <strong>{{ $salida->codigo_salida ?? 'N/A' }}</strong>.</p>

        @isset($salida->obra)
            <p><strong>Obra:</strong> {{ $salida->obra->obra }}</p>
        @endisset

        <p><strong>Fecha:</strong> {{ $salida->updated_at->format('d/m/Y H:i') }}</p>
        <p><strong>Responsable:</strong> {{ $salida->usuario->nombre_completo ?? 'N/A' }}</p>

        @if ($salida->comentario)
            <blockquote style="border-left: 4px solid #ccc; padding-left: 10px; color: #555;">
                {{ $salida->comentario }}
            </blockquote>
        @endif

        <p style="margin-top: 30px;">
            <a href="{{ route('salidas-ferralla.show', $salida->id) }}" wire:navigate
                style="display: inline-block; padding: 10px 20px; background-color: #4a90e2; color: white; text-decoration: none; border-radius: 5px;">
                Ver salida
            </a>
        </p>

        <p style="margin-top: 40px;">Gracias,<br>{{ config('app.name') }}</p>
    </div>
</body>

</html>
