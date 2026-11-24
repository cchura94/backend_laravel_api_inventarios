<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>

    <style>
        @page {
            margin: 30px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0 0 5px;
            text-transform: uppercase;
        }

        .header p {
            margin: 2px 0;
            color: #666;
        }

        .filters {
            margin-bottom: 12px;
            padding: 7px 10px;
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            font-size: 9px;
            color: #555;
        }

        .filters span {
            display: inline-block;
            margin-right: 15px;
        }

        .summary {
            width: 100%;
            margin-bottom: 15px;
        }

        .summary table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary td {
            width: 33.33%;
            padding: 8px;
            border: 1px solid #ddd;
            background: #fafafa;
        }

        .summary .label {
            display: block;
            font-size: 8px;
            text-transform: uppercase;
            color: #777;
        }

        .summary .value {
            display: block;
            font-size: 14px;
            font-weight: bold;
            color: #222;
            margin-top: 3px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        table.data th {
            background: #333;
            color: #fff;
            font-weight: bold;
            padding: 7px 5px;
            text-align: left;
            font-size: 9px;
        }

        table.data td {
            padding: 6px 5px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: top;
        }

        table.data tbody tr:nth-child(even) td {
            background: #fafafa;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .estado {
            font-weight: bold;
            font-size: 9px;
        }

        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 9px;
            color: #777;
        }
    </style>
</head>
<body>

    @php
        $totalGeneral = 0;
        $totalItems = 0;

        foreach ($ventas as $venta) {
            $subtotal = 0;

            foreach ($venta->almacen_producto_venta as $producto) {
                $subtotal += (float) $producto->pivot->cantidad * (float) $producto->pivot->precio_unitario_venta;
                $totalItems += (float) $producto->pivot->cantidad;
            }

            $totalGeneral += $subtotal - (float) ($venta->descuento_total ?? 0);
        }
    @endphp

    <div class="header">
        <h1>Reporte de Ventas</h1>
        <p>Listado general de notas de venta registradas</p>
    </div>

    <div class="filters">
        @if (!empty($desde) || !empty($hasta))
            <span>
                <strong>Periodo:</strong>
                {{ !empty($desde) ? \Carbon\Carbon::parse($desde)->format('d/m/Y') : 'Inicio' }}
                -
                {{ !empty($hasta) ? \Carbon\Carbon::parse($hasta)->format('d/m/Y') : 'Hoy' }}
            </span>
        @endif

        @if (!empty($buscar))
            <span><strong>Búsqueda:</strong> "{{ $buscar }}"</span>
        @endif

        <span><strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}</span>
    </div>

    <div class="summary">
        <table>
            <tr>
                <td>
                    <span class="label">Ventas</span>
                    <span class="value">{{ $ventas->count() }}</span>
                </td>

                <td>
                    <span class="label">Ítems vendidos</span>
                    <span class="value">{{ $totalItems }}</span>
                </td>

                <td>
                    <span class="label">Monto total</span>
                    <span class="value">{{ number_format($totalGeneral, 2, ',', '.') }} Bs.</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 5%;">N.º</th>
                <th style="width: 15%;">Código</th>
                <th style="width: 15%;">Fecha</th>
                <th style="width: 22%;">Cliente</th>
                <th style="width: 15%;">Usuario</th>
                <th style="width: 8%;" class="text-center">Ítems</th>
                <th style="width: 10%;">Estado</th>
                <th style="width: 10%;" class="text-right">Total</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($ventas as $index => $venta)
                @php
                    $subtotal = 0;
                    $items = 0;

                    foreach ($venta->almacen_producto_venta as $producto) {
                        $subtotal += (float) $producto->pivot->cantidad * (float) $producto->pivot->precio_unitario_venta;
                        $items += (float) $producto->pivot->cantidad;
                    }

                    $total = $subtotal - (float) ($venta->descuento_total ?? 0);
                @endphp

                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $venta->codigo }}</td>
                    <td>{{ \Carbon\Carbon::parse($venta->fecha)->format('d/m/Y H:i') }}</td>
                    <td>{{ $venta->cliente->nombre_completo ?? 'No registrado' }}</td>
                    <td>{{ $venta->user->name ?? 'No registrado' }}</td>
                    <td class="text-center">{{ $items }}</td>
                    <td class="estado">{{ $venta->estado }}</td>
                    <td class="text-right">{{ number_format($total, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">
                        No hay notas de venta registradas para los filtros seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Reporte generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>

</body>
</html>
