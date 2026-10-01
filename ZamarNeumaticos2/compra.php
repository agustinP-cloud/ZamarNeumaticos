<?php

session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require "conexion.php";

$pagina_activa = "compras";

if (!isset($_SESSION["carrito_compra"])) {
    $_SESSION["carrito_compra"] = [];
}

$mensaje = "";
$error = "";

// Agregar una línea (neumático + cantidad + precio) al carrito de la compra
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "agregar_linea") {

    $neumatico_id = $_POST["neumatico_id"];
    $cantidad = (int) $_POST["cantidad"];
    $precio_unitario = (float) $_POST["precio_unitario"];

    $stmt = $pdo->prepare("SELECT * FROM neumaticos WHERE id = :id");
    $stmt->execute(["id" => $neumatico_id]);
    $neu = $stmt->fetch();

    if ($neu && $cantidad > 0) {
        $_SESSION["carrito_compra"][] = [
            "neumatico_id" => $neumatico_id,
            "nombre" => $neu["marca"] . " " . $neu["modelo"] . " (" . $neu["medida"] . ")",
            "cantidad" => $cantidad,
            "precio_unitario" => $precio_unitario,
            "subtotal" => $cantidad * $precio_unitario,
        ];
    } else {
        $error = "Elegí un neumático y una cantidad válida.";
    }
}

// Quitar una línea del carrito
if (isset($_GET["quitar"])) {

    $indice = (int) $_GET["quitar"];

    if (isset($_SESSION["carrito_compra"][$indice])) {
        unset($_SESSION["carrito_compra"][$indice]);
        $_SESSION["carrito_compra"] = array_values($_SESSION["carrito_compra"]);
    }

    header("Location: compra.php");
    exit();
}

// Confirmar la compra: se graba la cabecera, el detalle, y se suma el stock
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "confirmar") {

    if (empty($_SESSION["carrito_compra"])) {

        $error = "Agregá al menos un neumático antes de confirmar la compra.";

    } else {

        $proveedor_id = $_POST["proveedor_id"];
        $numero_factura = trim($_POST["numero_factura"]);
        $total = array_sum(array_column($_SESSION["carrito_compra"], "subtotal"));

        $stmtUsuario = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
        $stmtUsuario->execute(["usuario" => $_SESSION["usuario"]]);
        $usuario_id = $stmtUsuario->fetchColumn();

        $pdo->beginTransaction();

        try {

            $stmt = $pdo->prepare(
                "INSERT INTO compra (proveedor_id, usuario_id, numero_factura, total)
                 VALUES (:proveedor_id, :usuario_id, :numero_factura, :total)"
            );
            $stmt->execute([
                "proveedor_id" => $proveedor_id,
                "usuario_id" => $usuario_id,
                "numero_factura" => $numero_factura,
                "total" => $total,
            ]);

            $compra_id = $pdo->lastInsertId();

            $stmtDetalle = $pdo->prepare(
                "INSERT INTO detalle_compra (compra_id, neumatico_id, cantidad, precio_unitario, subtotal)
                 VALUES (:compra_id, :neumatico_id, :cantidad, :precio_unitario, :subtotal)"
            );

            $stmtStock = $pdo->prepare(
                "UPDATE neumaticos SET stock_actual = stock_actual + :cantidad WHERE id = :id"
            );

            foreach ($_SESSION["carrito_compra"] as $linea) {

                $stmtDetalle->execute([
                    "compra_id" => $compra_id,
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

            $_SESSION["carrito_compra"] = [];
            $mensaje = "Compra registrada correctamente. El stock ya fue actualizado.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Ocurrió un error al registrar la compra. Probá de nuevo.";
        }
    }
}

// Datos para los selects y el listado
$proveedores = $pdo->query("SELECT * FROM proveedores ORDER BY nombre_empresa")->fetchAll();
$neumaticos = $pdo->query("SELECT * FROM neumaticos ORDER BY marca, modelo")->fetchAll();

$carrito = $_SESSION["carrito_compra"];
$total_carrito = array_sum(array_column($carrito, "subtotal"));

// Historial de compras ya registradas
$historial_compras = $pdo->query(
    "SELECT c.id, c.fecha, c.numero_factura, c.total,
            p.nombre_empresa
     FROM compra c
     JOIN proveedores p ON p.id = c.proveedor_id
     ORDER BY c.fecha DESC"
)->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Compras - Zamar Neumáticos</title>

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

                    <span>GESTIÓN DE COMPRAS</span>

                    <h1>COMPRAS</h1>

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
                        Todavía no hay neumáticos cargados en Stock. Cargá al menos uno antes de registrar una compra.
                    </div>
                </div>

            <?php } else { ?>

                <!-- AGREGAR NEUMÁTICO AL CARRITO -->
                <div class="tarjeta">

                    <div class="panel-titulo">
                        AGREGAR NEUMÁTICO A LA COMPRA
                    </div>

                    <form method="POST" class="formulario-stock">

                        <input type="hidden" name="accion" value="agregar_linea">

                        <div>
                            <label>NEUMÁTICO</label>
                            <select name="neumatico_id" required>
                                <?php foreach ($neumaticos as $n) { ?>
                                    <option value="<?php echo $n["id"]; ?>">
                                        <?php echo htmlspecialchars($n["marca"] . " " . $n["modelo"] . " (" . $n["medida"] . ")"); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div>
                            <label>CANTIDAD</label>
                            <input type="number" name="cantidad" min="1" required>
                        </div>

                        <div>
                            <label>PRECIO UNITARIO (COSTO)</label>
                            <input type="number" name="precio_unitario" step="0.01" min="0" required>
                        </div>

                        <button type="submit">AGREGAR AL CARRITO</button>

                    </form>

                </div>


                <!-- CARRITO DE LA COMPRA -->
                <div class="tarjeta">

                    <div class="panel-titulo">
                        DETALLE DE LA COMPRA
                    </div>

                    <?php if (count($carrito) === 0) { ?>

                        <div class="panel-vacio">
                            Todavía no agregaste ningún neumático a esta compra.
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
                                            <a href="compra.php?quitar=<?php echo $indice; ?>" class="accion-eliminar">
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


                <!-- CONFIRMAR COMPRA -->
                <?php if (count($carrito) > 0) { ?>

                    <div class="tarjeta">

                        <div class="panel-titulo">
                            CONFIRMAR COMPRA
                        </div>

                        <?php if (count($proveedores) === 0) { ?>

                            <div class="panel-vacio">
                                Todavía no hay proveedores cargados. Cargá uno en la sección Proveedores antes de confirmar.
                            </div>

                        <?php } else { ?>

                        <form method="POST" class="formulario-stock">

                            <input type="hidden" name="accion" value="confirmar">

                            <div>
                                <label>PROVEEDOR</label>
                                <select name="proveedor_id" required>
                                    <?php foreach ($proveedores as $p) { ?>
                                        <option value="<?php echo $p["id"]; ?>">
                                            <?php echo htmlspecialchars($p["nombre_empresa"]); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div>
                                <label>NÚMERO DE FACTURA</label>
                                <input type="text" name="numero_factura">
                            </div>

                            <button type="submit">REGISTRAR COMPRA</button>

                        </form>

                        <?php } ?>

                    </div>

                <?php } ?>

            <?php } ?>


            <!-- HISTORIAL DE COMPRAS -->
            <div class="tarjeta">

                <div class="panel-titulo">
                    COMPRAS REGISTRADAS
                </div>

                <?php if (count($historial_compras) === 0) { ?>

                    <div class="panel-vacio">
                        Todavía no hay compras registradas.
                    </div>

                <?php } else { ?>

                    <table class="tabla-datos">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>N° Factura</th>
                                <th>Total</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($historial_compras as $c) { ?>

                                <tr>
                                    <td>#<?php echo $c["id"]; ?></td>
                                    <td><?php echo htmlspecialchars($c["fecha"]); ?></td>
                                    <td><?php echo htmlspecialchars($c["nombre_empresa"]); ?></td>
                                    <td><?php echo htmlspecialchars($c["numero_factura"]); ?></td>
                                    <td>$ <?php echo number_format($c["total"], 2, ",", "."); ?></td>
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
