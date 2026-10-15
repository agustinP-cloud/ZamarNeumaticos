<?php

session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require "conexion.php";

$pagina_activa = "clientes";

$mensaje = "";

// Alta de un cliente nuevo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "agregar") {

    $nombre = trim($_POST["nombre"]);
    $apellido = trim($_POST["apellido"]);
    $dni_cuit = trim($_POST["dni_cuit"]);
    $telefono = trim($_POST["telefono"]);
    $email = trim($_POST["email"]);
    $direccion = trim($_POST["direccion"]);

    $stmt = $pdo->prepare(
        "INSERT INTO clientes (nombre, apellido, dni_cuit, telefono, email, direccion)
         VALUES (:nombre, :apellido, :dni_cuit, :telefono, :email, :direccion)"
    );

    $stmt->execute([
        "nombre" => $nombre,
        "apellido" => $apellido,
        "dni_cuit" => $dni_cuit,
        "telefono" => $telefono,
        "email" => $email,
        "direccion" => $direccion,
    ]);

    $mensaje = "Cliente cargado correctamente.";
}

// Baja de un cliente
if (isset($_GET["eliminar"])) {

    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
    $stmt->execute(["id" => $_GET["eliminar"]]);

    header("Location: clientes.php");
    exit();
}

// Listado completo
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY apellido, nombre")->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Clientes - Zamar Neumáticos</title>

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

                    <span>GESTIÓN DEL SISTEMA</span>

                    <h1>CLIENTES</h1>

                </div>

            </div>


            <!-- FORMULARIO DE ALTA -->
            <div class="tarjeta">

                <div class="panel-titulo">
                    CARGAR CLIENTE
                </div>

                <?php if ($mensaje != "") { ?>
                    <p class="mensaje-ok"><?php echo htmlspecialchars($mensaje); ?></p>
                <?php } ?>

                <form method="POST" class="formulario-stock">

                    <input type="hidden" name="accion" value="agregar">

                    <div>
                        <label>NOMBRE</label>
                        <input type="text" name="nombre" required>
                    </div>

                    <div>
                        <label>APELLIDO</label>
                        <input type="text" name="apellido" required>
                    </div>

                    <div>
                        <label>DNI / CUIT</label>
                        <input type="text" name="dni_cuit">
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
                    CLIENTES
                </div>

                <?php if (count($clientes) === 0) { ?>

                    <div class="panel-vacio">
                        Esta sección se encuentra disponible en el sistema.
                    </div>

                <?php } else { ?>

                    <table class="tabla-datos">

                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>DNI / CUIT</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Dirección</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($clientes as $c) { ?>

                                <tr>
                                    <td><?php echo htmlspecialchars($c["nombre"]); ?></td>
                                    <td><?php echo htmlspecialchars($c["apellido"]); ?></td>
                                    <td><?php echo htmlspecialchars($c["dni_cuit"]); ?></td>
                                    <td><?php echo htmlspecialchars($c["telefono"]); ?></td>
                                    <td><?php echo htmlspecialchars($c["email"]); ?></td>
                                    <td><?php echo htmlspecialchars($c["direccion"]); ?></td>
                                    <td>
                                        <a
                                            href="clientes.php?eliminar=<?php echo $c["id"]; ?>"
                                            class="accion-eliminar"
                                            onclick="return confirm('¿Eliminar este cliente?');"
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
