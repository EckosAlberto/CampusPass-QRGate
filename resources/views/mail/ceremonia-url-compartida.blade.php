@component('mail::message')
# {{ $ceremonia->nombre }}

Se compartió contigo la URL de **{{ $tipo === 'registro' ? 'Registrar Acceso' : 'Enlace remoto' }}** para esta ceremonia en CampusPass.

@component('mail::button', ['url' => $url])
Abrir URL
@endcomponent

@if ($tipo === 'registro')
Esta URL deja de estar disponible {{ $ceremonia->duracion_horas_acceso }} horas después del inicio de la ceremonia.
@else
Esta URL deja de estar disponible al finalizar la ceremonia.
@endif

Saludos,<br>
{{ config('app.name') }}
@endcomponent
