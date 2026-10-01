<?php

session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$pagina_activa = "panel";

require "conexion.php";

$stock_bajo = $pdo->query(
    "SELECT * FROM neumaticos WHERE stock_actual <= stock_minimo ORDER BY marca, modelo"
)->fetchAll();

$resumen_stock = $pdo->query(
    "SELECT COUNT(*) AS modelos, COALESCE(SUM(stock_actual), 0) AS unidades, COALESCE(SUM(stock_actual * precio_costo), 0) AS valor
     FROM neumaticos"
)->fetch();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Zamar Neumáticos</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include "menu.php"; ?>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main">

        <!-- BARRA SUPERIOR -->
        <header class="topbar">

            <div class="titulo-sistema">
                SISTEMA DE GESTIÓN · ZAMAR NEUMÁTICOS
            </div>

        </header>


        <!-- CONTENIDO -->
        <section class="contenido">

            <div class="encabezado">

                <div>
                    <span>RESUMEN GENERAL</span>

                    <h1>PANEL DE CONTROL</h1>
                </div>

            </div>


            <!-- TARJETAS -->
            <div class="tarjetas">

                <div class="tarjeta">

                    <div class="panel-titulo alerta">
                        ⚠ STOCK BAJO
                    </div>

                    <?php if (count($stock_bajo) === 0) { ?>

                        <div class="panel-vacio">
                            Sin alertas de stock.
                        </div>

                    <?php } else { ?>

                        <ul class="lista-alertas">
                            <?php foreach ($stock_bajo as $sb) { ?>
                                <li>
                                    <?php echo htmlspecialchars($sb["marca"] . " " . $sb["modelo"]); ?>
                                    — <?php echo $sb["stock_actual"]; ?> u.
                                </li>
                            <?php } ?>
                        </ul>

                    <?php } ?>

                </div>


                <div class="tarjeta">

                    <span>PRESUPUESTOS PENDIENTES</span>

                    <strong>0</strong>

                    <small>0 en total</small>

                </div>


                <div class="tarjeta">

                    <span>STOCK TOTAL</span>

                    <strong><?php echo $resumen_stock["unidades"]; ?> u.</strong>

                    <small>
                        <?php echo $resumen_stock["modelos"]; ?> modelos ·
                        $ <?php echo number_format($resumen_stock["valor"], 2, ",", "."); ?>
                    </small>

                </div>

            </div>

        </section>

    </main>

</body>
</html>
