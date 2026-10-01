<?php

session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require "conexion.php";

$pagina_activa = "stock";

$mensaje = "";

// Alta de un neumático nuevo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "agregar") {

    $marca = trim($_POST["marca"]);
    $modelo = trim($_POST["modelo"]);
    $medida = trim($_POST["medida"]);
    $precio_costo = $_POST["precio_costo"];
    $precio_venta = $_POST["precio_venta"];
    $stock_actual = $_POST["stock_actual"];
    $stock_minimo = $_POST["stock_minimo"];

    $stmt = $pdo->prepare(
        "INSERT INTO neumaticos (marca, modelo, medida, precio_costo, precio_venta, stock_actual, stock_minimo)
         VALUES (:marca, :modelo, :medida, :precio_costo, :precio_venta, :stock_actual, :stock_minimo)"
    );

    $stmt->execute([
        "marca" => $marca,
        "modelo" => $modelo,
        "medida" => $medida,
        "precio_costo" => $precio_costo,
        "precio_venta" => $precio_venta,
        "stock_actual" => $stock_actual,
        "stock_minimo" => $stock_minimo,
    ]);

    $mensaje = "Neumático cargado correctamente.";
}

// Baja de un neumático
if (isset($_GET["eliminar"])) {

    $stmt = $pdo->prepare("DELETE FROM neumaticos WHERE id = :id");
    $stmt->execute(["id" => $_GET["eliminar"]]);

    header("Location: stock.php");
    exit();
}

// Listado completo
$neumaticos = $pdo->query("SELECT * FROM neumaticos ORDER BY marca, modelo")->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Stock - Zamar Neumáticos</title>

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

                    <span>GESTIÓN DE PRODUCTOS</span>

                    <h1>STOCK</h1>

                </div>

            </div>


            <!-- FORMULARIO DE ALTA -->
            <div class="tarjeta">

                <div class="panel-titulo">
                    CARGAR NEUMÁTICO
                </div>

                <?php if ($mensaje != "") { ?>
                    <p class="mensaje-ok"><?php echo htmlspecialchars($mensaje); ?></p>
                <?php } ?>

                <form method="POST" class="formulario-stock">

                    <input type="hidden" name="accion" value="agregar">

                    <div>
                        <label>MARCA</label>
                        <input type="text" name="marca" required>
                    </div>

                    <div>
                        <label>MODELO</label>
                        <input type="text" name="modelo">
                    </div>

                    <div>
                        <label>MEDIDA</label>
                        <input type="text" name="medida" placeholder="175/70 R13">
                    </div>

                    <div>
                        <label>PRECIO COSTO</label>
                        <input type="number" step="0.01" min="0" name="precio_costo" required>
                    </div>

                    <div>
                        <label>PRECIO VENTA</label>
                        <input type="number" step="0.01" min="0" name="precio_venta" required>
                    </div>

                    <div>
                        <label>STOCK ACTUAL</label>
                        <input type="number" min="0" name="stock_actual" required>
                    </div>

                    <div>
                        <label>STOCK MÍNIMO</label>
                        <input type="number" min="0" name="stock_minimo" value="0" required>
                    </div>

                    <button type="submit">AGREGAR</button>

                </form>

            </div>


            <!-- LISTADO -->
            <div class="tarjeta">

                <div class="panel-titulo">
                    STOCK DE NEUMÁTICOS
                </div>

                <?php if (count($neumaticos) === 0) { ?>

                    <div class="panel-vacio">
                        Todavía no hay productos cargados.
                    </div>

                <?php } else { ?>

                    <table class="tabla-datos">

                        <thead>
                            <tr>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Medida</th>
                                <th>Costo</th>
                                <th>Venta</th>
                                <th>Stock</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($neumaticos as $n) {
                                $bajo = $n["stock_actual"] <= $n["stock_minimo"];
                            ?>

                                <tr class="<?php echo $bajo ? "fila-alerta" : ""; ?>">
                                    <td><?php echo htmlspecialchars($n["marca"]); ?></td>
                                    <td><?php echo htmlspecialchars($n["modelo"]); ?></td>
                                    <td><?php echo htmlspecialchars($n["medida"]); ?></td>
                                    <td>$ <?php echo number_format($n["precio_costo"], 2, ",", "."); ?></td>
                                    <td>$ <?php echo number_format($n["precio_venta"], 2, ",", "."); ?></td>
                                    <td>
                                        <?php echo $n["stock_actual"]; ?> u.
                                        <?php if ($bajo) { ?><span title="Stock bajo">⚠</span><?php } ?>
                                    </td>
                                    <td>
                                        <a
                                            href="stock.php?eliminar=<?php echo $n["id"]; ?>"
                                            class="accion-eliminar"
                                            onclick="return confirm('¿Eliminar este neumático?');"
                                        >
                                            Eliminar
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
