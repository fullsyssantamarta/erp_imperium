<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cupón de Compra</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            width: 72mm;
        }
        .contenedor {
            padding: 10px;
        }
        .titulo {
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .campo {
            margin: 5px 0;
        }
        .campo span {
            font-weight: bold;
        }
        .icono {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="contenedor">
        <div class="titulo">
            {{$title}}
        </div>

        <p>{{$description}}</p>

        <div class="campo"><span class="icono"></span> Tienda: {{$establishment}}</div>
        <div class="campo"><span class="icono"></span> Fecha: {{$coupon_date}}</div>
        <div class="campo"><span class="icono"></span> Factura No.: {{$document_number}}</div>
        <hr>
        <div class="campo"><span class="icono"></span> Nombre: _ _ _ _ _ _ _ _ _ _ _ _ _ _ _</div>
        <div class="campo"><span class="icono"></span> Cédula/NIT: _ _ _ _ _ _ _ _ _ _ _ _ _</div>
        <div class="campo"><span class="icono"></span> Teléfono: _ _ _ _ _ _ _ _ _ _ _ _ _ _</div>
        <div class="campo"><span class="icono"></span> Correo: _ _ _ _ _ _ _ _ _ _ _ _ _ _ _</div>
    </div>
</body>
</html>
