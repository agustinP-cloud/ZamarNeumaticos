<?php

session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require "conexion.php";

if (!isset($_GET["id"])) {
    die("Falta indicar qué presupuesto mostrar.");
}

$stmt = $pdo->prepare(
    "SELECT p.*, c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
            c.dni_cuit, c.telefono, c.email, c.direccion
     FROM presupuestos p
     JOIN clientes c ON c.id = p.cliente_id
     WHERE p.id = :id"
);
$stmt->execute(["id" => $_GET["id"]]);
$presupuesto = $stmt->fetch();

if (!$presupuesto) {
    die("El presupuesto solicitado no existe.");
}

$stmtDetalle = $pdo->prepare(
    "SELECT d.*, n.marca, n.modelo, n.medida
     FROM detalle_presupuesto d
     JOIN neumaticos n ON n.id = d.neumatico_id
     WHERE d.presupuesto_id = :id"
);
$stmtDetalle->execute(["id" => $_GET["id"]]);
$detalle = $stmtDetalle->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Presupuesto #<?php echo $presupuesto["id"]; ?> - Zamar Neumáticos</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
            background-color: #fff;
            padding: 40px;
        }

        .hoja {
            max-width: 800px;
            margin: 0 auto;
        }

        .encabezado {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #111;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .encabezado h1 {
            font-size: 22px;
            letter-spacing: 2px;
        }

        .encabezado span {
            display: block;
            color: #666;
            font-size: 11px;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .numero-presupuesto {
            text-align: right;
        }

        .numero-presupuesto strong {
            font-size: 20px;
        }

        .numero-presupuesto span {
            display: block;
            color: #666;
            font-size: 11px;
            margin-top: 4px;
        }

        .datos-cliente {
            margin-bottom: 25px;
        }

        .datos-cliente h2 {
            font-size: 12px;
            letter-spacing: 1px;
            color: #666;
            margin-bottom: 8px;
        }

        .datos-cliente p {
            font-size: 13px;
            line-height: 1.6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
            text-align: left;
        }

        th {
            background-color: #111;
            color: #fff;
            font-size: 11px;
            letter-spacing: 1px;
        }

        .total-fila td {
            border-top: 2px solid #111;
            border-bottom: none;
            font-weight: bold;
            font-size: 15px;
        }

        .estado {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .estado.pendiente { background-color: #fff3cd; color: #7a5c00; }
        .estado.aprobado { background-color: #d4edda; color: #1e5c2c; }
        .estado.rechazado { background-color: #f8d7da; color: #7a1e26; }

        .pie {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #777;
            text-align: center;
        }

        .acciones {
            text-align: right;
            margin-bottom: 25px;
        }

        .acciones button,
        .acciones a {
            display: inline-block;
            padding: 10px 18px;
            font-size: 12px;
            letter-spacing: 1px;
            text-decoration: none;
            border: 1px solid #111;
            color: #111;
            background: #fff;
            cursor: pointer;
            margin-left: 8px;
        }

        .acciones button {
            background-color: #ffd900;
            border-color: #ffd900;
            font-weight: bold;
        }

        /* Al imprimir, ocultamos los botones */
        @media print {
            .acciones {
                display: none;
            }
            body {
                padding: 0;
            }
        }

    </style>

</head>

<body>

    <div class="hoja">

        <div class="acciones">
            <a href="presupuestos.php">Volver</a>
            <button onclick="window.print()">Imprimir</button>
        </div>

        <div class="encabezado">

            <div>
                <h1>ZAMAR NEUMÁTICOS</h1>
                <span>Sistema de gestión · Presupuesto</span>
            </div>

            <div class="numero-presupuesto">
                <strong>Presupuesto #<?php echo $presupuesto["id"]; ?></strong>
                <span>Fecha: <?php echo htmlspecialchars($presupuesto["fecha"]); ?></span>
                <span>
                    Válido hasta:
                    <?php echo $presupuesto["fecha_vencimiento"] ? htmlspecialchars($presupuesto["fecha_vencimiento"]) : "-"; ?>
                </span>
                <span class="estado <?php echo htmlspecialchars($presupuesto["estado"]); ?>">
                    <?php echo htmlspecialchars($presupuesto["estado"]); ?>
                </span>
            </div>

        </div>


        <div class="datos-cliente">
            <h2>CLIENTE</h2>
            <p>
                <strong><?php echo htmlspecialchars($presupuesto["cliente_nombre"] . " " . $presupuesto["cliente_apellido"]); ?></strong><br>
                <?php if ($presupuesto["dni_cuit"]) { ?>DNI/CUIT: <?php echo htmlspecialchars($presupuesto["dni_cuit"]); ?><br><?php } ?>
                <?php if ($presupuesto["telefono"]) { ?>Tel: <?php echo htmlspecialchars($presupuesto["telefono"]); ?><br><?php } ?>
                <?php if ($presupuesto["email"]) { ?>Email: <?php echo htmlspecialchars($presupuesto["email"]); ?><br><?php } ?>
                <?php if ($presupuesto["direccion"]) { ?>Dirección: <?php echo htmlspecialchars($presupuesto["direccion"]); ?><?php } ?>
            </p>
        </div>


        <table>

            <thead>
                <tr>
                    <th>Neumático</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($detalle as $d) { ?>

                    <tr>
                        <td><?php echo htmlspecialchars($d["marca"] . " " . $d["modelo"] . " (" . $d["medida"] . ")"); ?></td>
                        <td><?php echo $d["cantidad"]; ?></td>
                        <td>$ <?php echo number_format($d["precio_unitario"], 2, ",", "."); ?></td>
                        <td>$ <?php echo number_format($d["subtotal"], 2, ",", "."); ?></td>
                    </tr>

                <?php } ?>

                <tr class="total-fila">
                    <td colspan="3" style="text-align:right;">TOTAL</td>
                    <td>$ <?php echo number_format($presupuesto["total"], 2, ",", "."); ?></td>
                </tr>

            </tbody>

        </table>


        <div class="pie">
            Este presupuesto tiene una validez limitada. Zamar Neumáticos · Sistema de gestión
        </div>

    </div>

</body>

</html>
