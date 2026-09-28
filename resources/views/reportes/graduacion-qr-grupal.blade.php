<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin-bottom: 0; }
        p.subtitulo { color: #555; margin-top: 4px; }
        .boleto { display: block; page-break-inside: avoid; border: 1px solid #ccc; border-radius: 6px; padding: 10px; margin-top: 12px; text-align: center; }
        .boleto img { width: 160px; height: 160px; }
        .boleto .nombre { font-weight: bold; margin-top: 6px; }
        .boleto .no-control { color: #555; }
    </style>
</head>
<body>
    <h1>QR de invitados &mdash; {{ $ceremonia->nombre }}</h1>
    <p class="subtitulo">Carrera: {{ request('carrera') }} &middot; Total de invitados: {{ $boletos->count() }}</p>

    @foreach ($boletos as $boleto)
        <div class="boleto">
            <img src="{{ $boleto['qr'] }}" alt="QR">
            <div class="nombre">{{ $boleto['alumno'] }}</div>
            <div class="no-control">No. Control: {{ $boleto['noDeControl'] }}</div>
        </div>
    @endforeach

    @if ($boletos->isEmpty())
        <p>No hay boletos de invitados generados para esta carrera todavía.</p>
    @endif
</body>
</html>
