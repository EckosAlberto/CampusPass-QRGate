@component('mail::message')
# {{ $reporte->nombre }}

Se generó el reporte **{{ $reporte->nombre }}** en CampusPass.

- **Periodo:** {{ $reporte->fecha_inicial->format('d/m/Y') }} – {{ $reporte->fecha_final->format('d/m/Y') }}
- **Registros:** {{ $reporte->registros }}

Encontrarás el PDF adjunto a este correo.

Saludos,<br>
{{ config('app.name') }}
@endcomponent
