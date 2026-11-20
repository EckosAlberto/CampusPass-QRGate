<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin-bottom: 0; }
        p.subtitulo { color: #555; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .indicadores td { border: none; padding: 4px 8px 4px 0; }
        .indicadores .valor { font-size: 16px; font-weight: bold; }
        h2 { font-size: 14px; margin-top: 24px; margin-bottom: 6px; }
    </style>
</head>
<body>
    <h1>{{ $reporte['nombre'] }}</h1>
    <p class="subtitulo">Periodo: {{ $reporte['periodo'] }} &middot; Generado por: {{ $reporte['generadoPor'] }} &middot; {{ $reporte['fechaGeneracion'] }}</p>

    <table class="indicadores">
        <tr>
            <td>
                <div class="valor">{{ $datos['indicadores']['egresadosRegistrados'] }}</div>
                Egresados registrados
            </td>
            <td>
                <div class="valor">{{ $datos['indicadores']['invitadosRegistrados'] }}</div>
                Invitados registrados
            </td>
            <td>
                <div class="valor">{{ $datos['indicadores']['totalAsistentes'] }}</div>
                Total de asistentes
            </td>
        </tr>
    </table>

    <h2>Estadísticas por carrera</h2>
    <table>
        <thead>
            <tr>
                <th>Carrera</th>
                <th>Egresados</th>
                <th>Invitados</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($datos['porCarrera'] as $fila)
                <tr>
                    <td>{{ $fila['carrera'] }}</td>
                    <td>{{ $fila['egresados'] }}</td>
                    <td>{{ $fila['invitados'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Sin datos en el periodo seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Estadísticas por ceremonia</h2>
    <table>
        <thead>
            <tr>
                <th>Ceremonia</th>
                <th>Total de accesos</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($datos['porCeremonia'] as $fila)
                <tr>
                    <td>{{ $fila['ceremonia'] }}</td>
                    <td>{{ $fila['total'] }}</td>
                </tr>
            @empty
                <tr><td colspan="2">Sin datos en el periodo seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if (! empty($datos['detalle']))
        <h2>Detalle de accesos</h2>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Nombre</th>
                    <th>No. Control</th>
                    <th>Carrera</th>
                    <th>Ceremonia</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($datos['detalle'] as $fila)
                    <tr>
                        <td>{{ $fila['fecha'] }}</td>
                        <td>{{ $fila['hora'] }}</td>
                        <td>{{ $fila['nombre'] }}</td>
                        <td>{{ $fila['noDeControl'] }}</td>
                        <td>{{ $fila['carrera'] }}</td>
                        <td>{{ $fila['ceremonia'] }}</td>
                        <td>{{ $fila['tipo'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
