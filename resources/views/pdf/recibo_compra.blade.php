<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo {{ $data->codigo }}</title>

    <style>
        @page {
            margin: 30px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 22px;
            margin: 0 0 5px;
            text-transform: uppercase;
        }

        .header p {
            margin: 2px 0;
            color: #666;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            background: #f0f0f0;
            padding: 7px;
            margin-top: 15px;
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 4px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 25%;
        }

        .products th {
            background: #333;
            color: #fff;
            font-weight: bold;
            padding: 8px 5px;
            text-align: left;
        }

        .products td {
            padding: 8px 5px;
            border-bottom: 1px solid #ddd;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .totals {
            width: 48%;
            margin-left: auto;
            margin-top: 15px;
        }

        .totals td {
            padding: 5px;
        }

        .grand-total {
            font-size: 15px;
            font-weight: bold;
            border-top: 2px solid #333;
            background: #f0f0f0;
        }

        .observations {
            padding: 8px;
            border: 1px solid #ddd;
            min-height: 25px;
        }

        .footer {
            margin-top: 35px;
            text-align: center;
            font-size: 10px;
            color: #777;
        }

        .signature {
            margin-top: 55px;
            width: 45%;
            border-top: 1px solid #555;
            text-align: center;
            padding-top: 5px;
        }

        .signature-container {
            width: 100%;
        }
    </style>
</head>
<body>

    @php
        $subtotal = 0;

        foreach ($data->almacen_producto_compra as $producto) {
            $cantidad = (float) $producto->pivot->cantidad;
            $precio = (float) $producto->pivot->precio_unitario_compra;

            $subtotal += $cantidad * $precio;
        }

        $descuento = (float) ($data->descuento_total ?? 0);
        $total = $subtotal - $descuento;
    @endphp

    {{-- ENCABEZADO --}}
    <div class="header">
        <h1>Recibo de Compra</h1>
        <p>Comprobante de compra</p>
        <p><strong>N.º {{ $data->codigo }}</strong></p>
    </div>

    {{-- INFORMACIÓN DE LA COMPRA --}}
    <div class="section-title">Datos de la compra</div>

    <table class="info-table">
        <tr>
            <td class="label">N.º de recibo:</td>
            <td>{{ $data->codigo }}</td>

            <td class="label">Fecha:</td>
            <td>
                {{ \Carbon\Carbon::parse($data->fecha)->format('d/m/Y H:i') }}
            </td>
        </tr>

        <tr>
            <td class="label">Estado:</td>
            <td>{{ $data->estado }}</td>

            <td class="label">Comprador:</td>
            <td>{{ $data->user->name ?? 'No registrado' }}</td>
        </tr>

        <tr>
            <td class="label">Detalle:</td>
            <td colspan="3">{{ $data->detalle ?? 'Sin detalle' }}</td>
        </tr>
    </table>

    {{-- INFORMACIÓN DEL PROVEEDOR --}}
    <div class="section-title">Datos del proveedor</div>

    <table class="info-table">
        <tr>
            <td class="label">Razón social:</td>
            <td>{{ $data->proveedor->razon_social ?? 'Proveedor no registrado' }}</td>
        </tr>

        <tr>
            <td class="label">Identificación:</td>
            <td>{{ $data->proveedor->nro_identificacion ?? 'No registrada' }}</td>
        </tr>

        <tr>
            <td class="label">Contacto:</td>
            <td>{{ $data->proveedor->contacto ?? 'No registrado' }}</td>
        </tr>

        <tr>
            <td class="label">Teléfono:</td>
            <td>{{ $data->proveedor->telefono ?? 'No registrado' }}</td>
        </tr>

        <tr>
            <td class="label">Correo:</td>
            <td>{{ $data->proveedor->correo ?? 'No registrado' }}</td>
        </tr>
    </table>

    {{-- DETALLE DE PRODUCTOS --}}
    <div class="section-title">Detalle de productos</div>

    <table class="products">
        <thead>
            <tr>
                <th style="width: 7%;">N.º</th>
                <th style="width: 35%;">Producto</th>
                <th style="width: 13%;">Cantidad</th>
                <th style="width: 20%;" class="text-right">P. unitario</th>
                <th style="width: 25%;" class="text-right">Importe</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($data->almacen_producto_compra as $index => $producto)
                @php
                    $cantidad = (float) $producto->pivot->cantidad;
                    $precio = (float) $producto->pivot->precio_unitario_compra;
                    $importe = $cantidad * $precio;
                @endphp

                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>

                    <td>
                        <strong>{{ $producto->nombre }}</strong>

                        @if (!empty($producto->pivot->observaciones))
                            <br>
                            <small>
                                {{ $producto->pivot->observaciones }}
                            </small>
                        @endif
                    </td>

                    <td class="text-center">
                        {{ $cantidad }}
                        {{ $producto->unidad_medida ?? 'Unidad' }}
                    </td>

                    <td class="text-right">
                        {{ number_format($precio, 2, ',', '.') }}
                    </td>

                    <td class="text-right">
                        {{ number_format($importe, 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">
                        No hay productos registrados en esta compra.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- TOTALES --}}
    <table class="totals">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">
                {{ number_format($subtotal, 2, ',', '.') }} Bs.
            </td>
        </tr>

        <tr>
            <td>Descuento:</td>
            <td class="text-right">
                {{ number_format($descuento, 2, ',', '.') }} Bs.
            </td>
        </tr>

        <tr class="grand-total">
            <td>TOTAL:</td>
            <td class="text-right">
                {{ number_format($total, 2, ',', '.') }} Bs.
            </td>
        </tr>
    </table>

    {{-- OBSERVACIONES --}}
    <div class="section-title">Observaciones</div>

    <div class="observations">
        {{ $data->observaciones ?? 'Sin observaciones' }}
    </div>

    {{-- FIRMAS --}}
    <table class="signature-container">
        <tr>
            <td style="width: 50%; padding-right: 15px;">
                <div class="signature">
                    Firma del comprador
                </div>
            </td>

            <td style="width: 50%; padding-left: 15px;">
                <div class="signature">
                    Firma del proveedor
                </div>
            </td>
        </tr>
    </table>

    {{-- PIE --}}
    <div class="footer">
        <p>Gracias por su preferencia.</p>
        <p>Recibo generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>

</body>
</html>
