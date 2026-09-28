@component('mail::message')
# {{ $evento->nombre }}

Se compartió contigo la URL de **{{ $tipo === 'entrada' ? 'Entrada' : 'Salida' }}** para este evento en CampusPass.

@component('mail::button', ['url' => $url])
Abrir URL de {{ $tipo === 'entrada' ? 'Entrada' : 'Salida' }}
@endcomponent

Esta URL caduca {{ \App\Models\EventoTutorias::MINUTOS_DURACION_URL }} minutos después de haberse generado.

Saludos,<br>
{{ config('app.name') }}
@endcomponent
