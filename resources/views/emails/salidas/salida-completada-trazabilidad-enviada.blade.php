@component('mail::message')
# Salida completada

Se ha completado la salida **{{ $salida->codigo }}**.

@isset($salida->obra)
- **Obra:** {{ $salida->obra->nombre }}
@endisset

- **Fecha:** {{ $salida->updated_at->format('d/m/Y H:i') }}
- **Responsable:** {{ $salida->usuario->nombre_completo ?? 'N/A' }}

@if($salida->comentarios)
> _{{ $salida->comentarios }}_
@endif

@component('mail::button', ['url' => route('salidas.show', $salida->id)])
Ver salida
@endcomponent

Gracias,<br>
{{ config('app.name') }}
@endcomponent