<?php

session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require "conexion.php";

$pagina_activa = "presupuestos";

if (!isset($_SESSION["carrito_presupuesto"])) {
    $_SESSION["carrito_presupuesto"] = [];
}

$mensaje = "";
$error = "";

// Agregar una línea (neumático + cantidad + precio) al carrito del presupuesto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "agregar_linea") {

    $neumatico_id = $_POST["neumatico_id"];
    $cantidad = (int) $_POST["cantidad"];
    $precio_unitario = (float) $_POST["precio_unitario"];

    $stmt = $pdo->prepare("SELECT * FROM neumaticos WHERE id = :id");
    $stmt->execute(["id" => $neumatico_id]);
    $neu = $stmt->fetch();

    if ($neu && $cantidad > 0) {
        $_SESSION["carrito_presupuesto"][] = [
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

    if (isset($_SESSION["carrito_presupuesto"][$indice])) {
        unset($_SESSION["carrito_presupuesto"][$indice]);
        $_SESSION["carrito_presupuesto"] = array_values($_SESSION["carrito_presupuesto"]);
    }

    header("Location: presupuestos.php");
    exit();
}

// Confirmar el presupuesto: NO toca el stock, solo deja la cotización guardada
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "confirmar") {

    if (empty($_SESSION["carrito_presupuesto"])) {

        $error = "Agregá al menos un neumático antes de confirmar el presupuesto.";

    } else {

        $cliente_id = $_POST["cliente_id"];
        $fecha_vencimiento = $_POST["fecha_vencimiento"];
        $total = array_sum(array_column($_SESSION["carrito_presupuesto"], "subtotal"));

        $stmtUsuario = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
        $stmtUsuario->execute(["usuario" => $_SESSION["usuario"]]);
        $usuario_id = $stmtUsuario->fetchColumn();

        $pdo->beginTransaction();

        try {

            $stmt = $pdo->prepare(
                "INSERT INTO presupuestos (cliente_id, usuario_id, fecha_vencimiento, total, estado)
                 VALUES (:cliente_id, :usuario_id, :fecha_vencimiento, :total, 'pendiente')"
            );
            $stmt->execute([
                "cliente_id" => $cliente_id,
                "usuario_id" => $usuario_id,
                "fecha_vencimiento" => $fecha_vencimiento ?: null,
                "total" => $total,
            ]);

            $presupuesto_id = $pdo->lastInsertId();

            $stmtDetalle = $pdo->prepare(
                "INSERT INTO detalle_presupuesto (presupuesto_id, neumatico_id, cantidad, precio_unitario, subtotal)
                 VALUES (:presupuesto_id, :neumatico_id, :cantidad, :precio_unitario, :subtotal)"
            );

            foreach ($_SESSION["carrito_presupuesto"] as $linea) {
                $stmtDetalle->execute([
                    "presupuesto_id" => $presupuesto_id,
                    "neumatico_id" => $linea["neumatico_id"],
                    "cantidad" => $linea["cantidad"],
                    "precio_unitario" => $linea["precio_unitario"],
                    "subtotal" => $linea["subtotal"],
                ]);
            }

            $pdo->commit();

            $_SESSION["carrito_presupuesto"] = [];

            header("Location: presupuesto_imprimir.php?id=" . $presupuesto_id);
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "No se pudo registrar el presupuesto. Probá de nuevo.";
        }
    }
}

// Cambiar el estado de un presupuesto ya generado
if (isset($_GET["estado"]) && isset($_GET["id"])) {

    $estados_validos = ["pendiente", "aprobado", "rechazado"];

    if (in_array($_GET["estado"], $estados_validos)) {
        $stmt = $pdo->prepare("UPDATE presupuestos SET estado = :estado WHERE id = :id");
        $stmt->execute(["estado" => $_GET["estado"], "id" => $_GET["id"]]);
    }

    header("Location: presupuestos.php");
    exit();
}

// Datos para los selects
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY apellido, nombre")->fetchAll();
$neumaticos = $pdo->query("SELECT * FROM neumaticos ORDER BY marca, modelo")->fetchAll();

$carrito = $_SESSION["carrito_presupuesto"];
$total_carrito = array_sum(array_column($carrito, "subtotal"));

// Historial de presupuestos ya generados
$historial_presupuestos = $pdo->query(
    "SELECT p.id, p.fecha, p.fecha_vencimiento, p.total, p.estado,
            c.nombre AS cliente_nombre, c.apellido AS cliente_apellido
     FROM presupuestos p
     JOIN clientes c ON c.id = p.cliente_id
     ORDER BY p.fecha DESC"
)->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Presupuestos - Zamar Neumáticos</title>

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

                    <h1>PRESUPUESTOS</h1>

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
                        Todavía no hay neumáticos cargados en Stock. Cargá al menos uno antes de armar un presupuesto.
                    </div>
                </div>

            <?php } else { ?>

                <!-- AGREGAR NEUMÁTICO AL CARRITO -->
                <div class="tarjeta">

                    <div class="panel-titulo">
                        AGREGAR NEUMÁTICO AL PRESUPUESTO
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
                            <label>PRECIO UNITARIO</label>
                            <input type="number" name="precio_unitario" step="0.01" min="0" required>
                        </div>

                        <button type="submit">AGREGAR AL CARRITO</button>

                    </form>

                </div>


                <!-- CARRITO DEL PRESUPUESTO -->
                <div class="tarjeta">

                    <div class="panel-titulo">
                        DETALLE DEL PRESUPUESTO
                    </div>

                    <?php if (count($carrito) === 0) { ?>

                        <div class="panel-vacio">
                            Todavía no agregaste ningún neumático a este presupuesto.
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
                                            <a href="presupuestos.php?quitar=<?php echo $indice; ?>" class="accion-eliminar">
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


                <!-- CONFIRMAR PRESUPUESTO -->
                <?php if (count($carrito) > 0) { ?>

                    <div class="tarjeta">

                        <div class="panel-titulo">
                            CONFIRMAR PRESUPUESTO
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
                                <label>VÁLIDO HASTA</label>
                                <input type="date" name="fecha_vencimiento">
                            </div>

                            <button type="submit">GENERAR PRESUPUESTO</button>

                        </form>

                        <?php } ?>

                    </div>

                <?php } ?>

            <?php } ?>


            <!-- HISTORIAL DE PRESUPUESTOS -->
            <div class="tarjeta">

                <div class="panel-titulo">
                    PRESUPUESTOS GENERADOS
                </div>

                <?php if (count($historial_presupuestos) === 0) { ?>

                    <div class="panel-vacio">
                        Todavía no hay presupuestos cargados.
                    </div>

                <?php } else { ?>

                    <table class="tabla-datos">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Válido hasta</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($historial_presupuestos as $p) { ?>

                                <tr>
                                    <td>#<?php echo $p["id"]; ?></td>
                                    <td><?php echo htmlspecialchars($p["fecha"]); ?></td>
                                    <td><?php echo htmlspecialchars($p["cliente_nombre"] . " " . $p["cliente_apellido"]); ?></td>
                                    <td><?php echo htmlspecialchars($p["fecha_vencimiento"] ?? "-"); ?></td>
                                    <td>$ <?php echo number_format($p["total"], 2, ",", "."); ?></td>
                                    <td>
                                        <select onchange="location.href='presupuestos.php?id=<?php echo $p['id']; ?>&estado=' + this.value">
                                            <option value="pendiente" <?php echo $p["estado"] == "pendiente" ? "selected" : ""; ?>>Pendiente</option>
                                            <option value="aprobado" <?php echo $p["estado"] == "aprobado" ? "selected" : ""; ?>>Aprobado</option>
                                            <option value="rechazado" <?php echo $p["estado"] == "rechazado" ? "selected" : ""; ?>>Rechazado</option>
                                        </select>
                                    </td>
                                    <td>
                                        <a href="presupuesto_imprimir.php?id=<?php echo $p["id"]; ?>" target="_blank">
                                            Ver / Imprimir
                                        </a>
                                    </td>
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
