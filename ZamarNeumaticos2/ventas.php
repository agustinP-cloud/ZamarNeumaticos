<?php

session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require "conexion.php";

$pagina_activa = "ventas";

if (!isset($_SESSION["carrito_venta"])) {
    $_SESSION["carrito_venta"] = [];
}

$mensaje = "";
$error = "";

// Cuánto ya está reservado en el carrito para un neumático (para no pasarse del stock)
function cantidad_en_carrito($carrito, $neumatico_id) {
    $total = 0;
    foreach ($carrito as $linea) {
        if ($linea["neumatico_id"] == $neumatico_id) {
            $total += $linea["cantidad"];
        }
    }
    return $total;
}

// Agregar una línea (neumático + cantidad + precio) al carrito de la venta
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "agregar_linea") {

    $neumatico_id = $_POST["neumatico_id"];
    $cantidad = (int) $_POST["cantidad"];
    $precio_unitario = (float) $_POST["precio_unitario"];

    $stmt = $pdo->prepare("SELECT * FROM neumaticos WHERE id = :id");
    $stmt->execute(["id" => $neumatico_id]);
    $neu = $stmt->fetch();

    $ya_reservado = cantidad_en_carrito($_SESSION["carrito_venta"], $neumatico_id);

    if (!$neu || $cantidad <= 0) {

        $error = "Elegí un neumático y una cantidad válida.";

    } elseif (($ya_reservado + $cantidad) > $neu["stock_actual"]) {

        $error = "No hay stock suficiente. Disponible: " . ($neu["stock_actual"] - $ya_reservado) . " u.";

    } else {

        $_SESSION["carrito_venta"][] = [
            "neumatico_id" => $neumatico_id,
            "nombre" => $neu["marca"] . " " . $neu["modelo"] . " (" . $neu["medida"] . ")",
            "cantidad" => $cantidad,
            "precio_unitario" => $precio_unitario,
            "subtotal" => $cantidad * $precio_unitario,
        ];
    }
}

// Quitar una línea del carrito
if (isset($_GET["quitar"])) {

    $indice = (int) $_GET["quitar"];

    if (isset($_SESSION["carrito_venta"][$indice])) {
        unset($_SESSION["carrito_venta"][$indice]);
        $_SESSION["carrito_venta"] = array_values($_SESSION["carrito_venta"]);
    }

    header("Location: ventas.php");
    exit();
}

// Confirmar la venta: se graba la cabecera, el detalle, y se resta el stock
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "confirmar") {

    if (empty($_SESSION["carrito_venta"])) {

        $error = "Agregá al menos un neumático antes de confirmar la venta.";

    } else {

        $cliente_id = $_POST["cliente_id"];
        $metodo_pago = trim($_POST["metodo_pago"]);
        $total = array_sum(array_column($_SESSION["carrito_venta"], "subtotal"));

        $stmtUsuario = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
        $stmtUsuario->execute(["usuario" => $_SESSION["usuario"]]);
        $usuario_id = $stmtUsuario->fetchColumn();

        $pdo->beginTransaction();

        try {

            $stmtStockCheck = $pdo->prepare("SELECT stock_actual FROM neumaticos WHERE id = :id FOR UPDATE");
            $stmtStock = $pdo->prepare(
                "UPDATE neumaticos SET stock_actual = stock_actual - :cantidad WHERE id = :id"
            );

            // Volvemos a validar el stock justo antes de descontar, por si cambió mientras armabas el carrito
            foreach ($_SESSION["carrito_venta"] as $linea) {
                $stmtStockCheck->execute(["id" => $linea["neumatico_id"]]);
                $stock_actual = $stmtStockCheck->fetchColumn();

                if ($stock_actual < $linea["cantidad"]) {
                    throw new Exception("Sin stock suficiente para " . $linea["nombre"]);
                }
            }

            $stmt = $pdo->prepare(
                "INSERT INTO ventas (cliente_id, usuario_id, total, metodo_pago, estado)
                 VALUES (:cliente_id, :usuario_id, :total, :metodo_pago, 'completada')"
            );
            $stmt->execute([
                "cliente_id" => $cliente_id,
                "usuario_id" => $usuario_id,
                "total" => $total,
                "metodo_pago" => $metodo_pago,
            ]);

            $venta_id = $pdo->lastInsertId();

            $stmtDetalle = $pdo->prepare(
                "INSERT INTO detalle_venta (venta_id, neumatico_id, cantidad, precio_unitario, subtotal)
                 VALUES (:venta_id, :neumatico_id, :cantidad, :precio_unitario, :subtotal)"
            );

            foreach ($_SESSION["carrito_venta"] as $linea) {

                $stmtDetalle->execute([
                    "venta_id" => $venta_id,
                    "neumatico_id" => $linea["neumatico_id"],
                    "cantidad" => $linea["cantidad"],
                    "precio_unitario" => $linea["precio_unitario"],
                    "subtotal" => $linea["subtotal"],
                ]);

                $stmtStock->execute([
                    "cantidad" => $linea["cantidad"],
                    "id" => $linea["neumatico_id"],
                ]);
            }

            $pdo->commit();

            $_SESSION["carrito_venta"] = [];
            $mensaje = "Venta registrada correctamente. El stock ya fue actualizado.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "No se pudo registrar la venta: " . $e->getMessage();
        }
    }
}

// Datos para los selects
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY apellido, nombre")->fetchAll();
$neumaticos = $pdo->query("SELECT * FROM neumaticos WHERE stock_actual > 0 ORDER BY marca, modelo")->fetchAll();

$carrito = $_SESSION["carrito_venta"];
$total_carrito = array_sum(array_column($carrito, "subtotal"));

// Historial de ventas ya registradas
$historial_ventas = $pdo->query(
    "SELECT v.id, v.fecha, v.total, v.metodo_pago, v.estado,
            c.nombre AS cliente_nombre, c.apellido AS cliente_apellido
     FROM ventas v
     JOIN clientes c ON c.id = v.cliente_id
     ORDER BY v.fecha DESC"
)->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ventas - Zamar Neumáticos</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

    <?php include "menu.php"; ?>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main">

        <header class="topbar">

            <div class="titulo-sistema">
                SISTEMA DE GESTIÓN · ZAMAR NEUMÁTICOS
            </div>

        </header>


        <section class="contenido">

            <div class="encabezado">

                <div>

                    <span>GESTIÓN COMERCIAL</span>

                    <h1>VENTAS</h1>

                </div>

            </div>

            <?php if ($mensaje != "") { ?>
                <p class="mensaje-ok"><?php echo htmlspecialchars($mensaje); ?></p>
            <?php } ?>

            <?php if ($error != "") { ?>
                <p class="mensaje-error"><?php echo htmlspecialchars($error); ?></p>
            <?php } ?>


            <?php if (count($neumaticos) === 0) { ?>

                <div class="tarjeta">
                    <div class="panel-vacio">
                        No hay neumáticos con stock disponible para vender. Cargá stock o registrá una compra primero.
                    </div>
                </div>

            <?php } else { ?>

                <!-- AGREGAR NEUMÁTICO AL CARRITO -->
                <div class="tarjeta">

                    <div class="panel-titulo">
                        AGREGAR NEUMÁTICO A LA VENTA
                    </div>

                    <form method="POST" class="formulario-stock">

                        <input type="hidden" name="accion" value="agregar_linea">

                        <div>
                            <label>NEUMÁTICO</label>
                            <select name="neumatico_id" required>
                                <?php foreach ($neumaticos as $n) { ?>
                                    <option value="<?php echo $n["id"]; ?>">
                                        <?php echo htmlspecialchars($n["marca"] . " " . $n["modelo"] . " (" . $n["medida"] . ") — " . $n["stock_actual"] . " u."); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div>
                            <label>CANTIDAD</label>
                            <input type="number" name="cantidad" min="1" required>
                        </div>

                        <div>
                            <label>PRECIO UNITARIO (VENTA)</label>
                            <input type="number" name="precio_unitario" step="0.01" min="0" required>
                        </div>

                        <button type="submit">AGREGAR AL CARRITO</button>

                    </form>

                </div>


                <!-- CARRITO DE LA VENTA -->
                <div class="tarjeta">

                    <div class="panel-titulo">
                        DETALLE DE LA VENTA
                    </div>

                    <?php if (count($carrito) === 0) { ?>

                        <div class="panel-vacio">
                            Todavía no agregaste ningún neumático a esta venta.
                        </div>

                    <?php } else { ?>

                        <table class="tabla-datos">

                            <thead>
                                <tr>
                                    <th>Neumático</th>
                                    <th>Cantidad</th>
                                    <th>Precio unitario</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($carrito as $indice => $linea) { ?>

                                    <tr>
                                        <td><?php echo htmlspecialchars($linea["nombre"]); ?></td>
                                        <td><?php echo $linea["cantidad"]; ?></td>
                                        <td>$ <?php echo number_format($linea["precio_unitario"], 2, ",", "."); ?></td>
                                        <td>$ <?php echo number_format($linea["subtotal"], 2, ",", "."); ?></td>
                                        <td>
                                            <a href="ventas.php?quitar=<?php echo $indice; ?>" class="accion-eliminar">
                                                Quitar
                                            </a>
                                        </td>
                                    </tr>

                                <?php } ?>

                                <tr>
                                    <td colspan="3" style="text-align:right; color:#777;">TOTAL</td>
                                    <td colspan="2">$ <?php echo number_format($total_carrito, 2, ",", "."); ?></td>
                                </tr>

                            </tbody>

                        </table>

                    <?php } ?>

                </div>


                <!-- CONFIRMAR VENTA -->
                <?php if (count($carrito) > 0) { ?>

                    <div class="tarjeta">

                        <div class="panel-titulo">
                            CONFIRMAR VENTA
                        </div>

                        <?php if (count($clientes) === 0) { ?>

                            <div class="panel-vacio">
                                Todavía no hay clientes cargados. Cargá uno en la sección Clientes antes de confirmar.
                            </div>

                        <?php } else { ?>

                        <form method="POST" class="formulario-stock">

                            <input type="hidden" name="accion" value="confirmar">

                            <div>
                                <label>CLIENTE</label>
                                <select name="cliente_id" required>
                                    <?php foreach ($clientes as $c) { ?>
                                        <option value="<?php echo $c["id"]; ?>">
                                            <?php echo htmlspecialchars($c["nombre"] . " " . $c["apellido"]); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div>
                                <label>MÉTODO DE PAGO</label>
                                <select name="metodo_pago" required>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="tarjeta">Tarjeta</option>
                                </select>
                            </div>

                            <button type="submit">REGISTRAR VENTA</button>

                        </form>

                        <?php } ?>

                    </div>

                <?php } ?>

            <?php } ?>


            <!-- HISTORIAL DE VENTAS -->
            <div class="tarjeta">

                <div class="panel-titulo">
                    VENTAS REGISTRADAS
                </div>

                <?php if (count($historial_ventas) === 0) { ?>

                    <div class="panel-vacio">
                        Todavía no hay ventas registradas.
                    </div>

                <?php } else { ?>

                    <table class="tabla-datos">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Método de pago</th>
                                <th>Estado</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($historial_ventas as $v) { ?>

                                <tr>
                                    <td>#<?php echo $v["id"]; ?></td>
                                    <td><?php echo htmlspecialchars($v["fecha"]); ?></td>
                                    <td><?php echo htmlspecialchars($v["cliente_nombre"] . " " . $v["cliente_apellido"]); ?></td>
                                    <td>$ <?php echo number_format($v["total"], 2, ",", "."); ?></td>
                                    <td><?php echo htmlspecialchars($v["metodo_pago"]); ?></td>
                                    <td><?php echo htmlspecialchars($v["estado"]); ?></td>
                                </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                <?php } ?>

            </div>

        </section>

    </main>

</body>

</html>
