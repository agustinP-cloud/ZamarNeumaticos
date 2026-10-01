<?php

session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require "conexion.php";

$pagina_activa = "proveedores";

$mensaje = "";

// Alta de un proveedor nuevo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "agregar") {

    $nombre_empresa = trim($_POST["nombre_empresa"]);
    $cuit = trim($_POST["cuit"]);
    $telefono = trim($_POST["telefono"]);
    $email = trim($_POST["email"]);
    $direccion = trim($_POST["direccion"]);

    $stmt = $pdo->prepare(
        "INSERT INTO proveedores (nombre_empresa, cuit, telefono, email, direccion)
         VALUES (:nombre_empresa, :cuit, :telefono, :email, :direccion)"
    );

    $stmt->execute([
        "nombre_empresa" => $nombre_empresa,
        "cuit" => $cuit,
        "telefono" => $telefono,
        "email" => $email,
        "direccion" => $direccion,
    ]);

    $mensaje = "Proveedor cargado correctamente.";
}

// Baja de un proveedor
if (isset($_GET["eliminar"])) {

    $stmt = $pdo->prepare("DELETE FROM proveedores WHERE id = :id");
    $stmt->execute(["id" => $_GET["eliminar"]]);

    header("Location: proveedores.php");
    exit();
}

// Listado completo
$proveedores = $pdo->query("SELECT * FROM proveedores ORDER BY nombre_empresa")->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Proveedores - Zamar Neumáticos</title>

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

                    <h1>PROVEEDORES</h1>

                </div>

            </div>


            <!-- FORMULARIO DE ALTA -->
            <div class="tarjeta">

                <div class="panel-titulo">
                    CARGAR PROVEEDOR
                </div>

                <?php if ($mensaje != "") { ?>
                    <p class="mensaje-ok"><?php echo htmlspecialchars($mensaje); ?></p>
                <?php } ?>

                <form method="POST" class="formulario-stock">

                    <input type="hidden" name="accion" value="agregar">

                    <div>
                        <label>EMPRESA</label>
                        <input type="text" name="nombre_empresa" required>
                    </div>

                    <div>
                        <label>CUIT</label>
                        <input type="text" name="cuit">
                    </div>

                    <div>
                        <label>TELÉFONO</label>
                        <input type="text" name="telefono">
                    </div>

                    <div>
                        <label>EMAIL</label>
                        <input type="email" name="email">
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <label>DIRECCIÓN</label>
                        <input type="text" name="direccion">
                    </div>

                    <button type="submit">AGREGAR</button>

                </form>

            </div>


            <!-- LISTADO -->
            <div class="tarjeta">

                <div class="panel-titulo">
                    PROVEEDORES
                </div>

                <?php if (count($proveedores) === 0) { ?>

                    <div class="panel-vacio">
                        Todavía no hay proveedores cargados.
                    </div>

                <?php } else { ?>

                    <table class="tabla-datos">

                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>CUIT</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Dirección</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($proveedores as $p) { ?>

                                <tr>
                                    <td><?php echo htmlspecialchars($p["nombre_empresa"]); ?></td>
                                    <td><?php echo htmlspecialchars($p["cuit"]); ?></td>
                                    <td><?php echo htmlspecialchars($p["telefono"]); ?></td>
                                    <td><?php echo htmlspecialchars($p["email"]); ?></td>
                                    <td><?php echo htmlspecialchars($p["direccion"]); ?></td>
                                    <td>
                                        <a
                                            href="proveedores.php?eliminar=<?php echo $p["id"]; ?>"
                                            class="accion-eliminar"
                                            onclick="return confirm('¿Eliminar este proveedor?');"
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
