<?php

session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require "conexion.php";

$pagina_activa = "usuarios";

$mensaje = "";
$error = "";

// Alta de un usuario nuevo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "agregar") {

    $usuario = trim($_POST["usuario"]);
    $clave = $_POST["clave"];
    $nombre_completo = trim($_POST["nombre_completo"]);
    $rol = $_POST["rol"];

    // Chequear que el nombre de usuario no exista ya
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
    $stmt->execute(["usuario" => $usuario]);

    if ($stmt->fetch()) {
        $error = "Ya existe un usuario con ese nombre.";
    } else {

        $clave_hasheada = password_hash($clave, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (usuario, clave, nombre_completo, rol)
             VALUES (:usuario, :clave, :nombre_completo, :rol)"
        );

        $stmt->execute([
            "usuario" => $usuario,
            "clave" => $clave_hasheada,
            "nombre_completo" => $nombre_completo,
            "rol" => $rol,
        ]);

        $mensaje = "Usuario cargado correctamente.";
    }
}

// Baja de un usuario (no se puede eliminar a uno mismo)
if (isset($_GET["eliminar"])) {

    $stmt = $pdo->prepare("SELECT usuario FROM usuarios WHERE id = :id");
    $stmt->execute(["id" => $_GET["eliminar"]]);
    $objetivo = $stmt->fetch();

    if ($objetivo && $objetivo["usuario"] == $_SESSION["usuario"]) {
        $error = "No podés eliminar tu propio usuario mientras estás conectado.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute(["id" => $_GET["eliminar"]]);
        header("Location: usuarios.php");
        exit();
    }
}

// Listado completo
$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY usuario")->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Usuarios - Zamar Neumáticos</title>

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

                    <span>ADMINISTRACIÓN</span>

                    <h1>USUARIOS</h1>

                </div>

            </div>


            <!-- FORMULARIO DE ALTA -->
            <div class="tarjeta">

                <div class="panel-titulo">
                    CARGAR USUARIO
                </div>

                <?php if ($mensaje != "") { ?>
                    <p class="mensaje-ok"><?php echo htmlspecialchars($mensaje); ?></p>
                <?php } ?>

                <?php if ($error != "") { ?>
                    <p class="mensaje-error"><?php echo htmlspecialchars($error); ?></p>
                <?php } ?>

                <form method="POST" class="formulario-stock">

                    <input type="hidden" name="accion" value="agregar">

                    <div>
                        <label>NOMBRE COMPLETO</label>
                        <input type="text" name="nombre_completo" required>
                    </div>

                    <div>
                        <label>USUARIO</label>
                        <input type="text" name="usuario" required>
                    </div>

                    <div>
                        <label>CONTRASEÑA</label>
                        <input type="password" name="clave" required>
                    </div>

                    <div>
                        <label>ROL</label>
                        <select name="rol" required>
                            <option value="vendedor">Vendedor</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>

                    <button type="submit">AGREGAR</button>

                </form>

            </div>


            <!-- LISTADO -->
            <div class="tarjeta">

                <div class="panel-titulo">
                    USUARIOS DEL SISTEMA
                </div>

                <?php if (count($usuarios) === 0) { ?>

                    <div class="panel-vacio">
                        Todavía no hay usuarios registrados.
                    </div>

                <?php } else { ?>

                    <table class="tabla-datos">

                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nombre completo</th>
                                <th>Rol</th>
                                <th>Alta</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($usuarios as $u) { ?>

                                <tr>
                                    <td><?php echo htmlspecialchars($u["usuario"]); ?></td>
                                    <td><?php echo htmlspecialchars($u["nombre_completo"]); ?></td>
                                    <td><?php echo htmlspecialchars($u["rol"]); ?></td>
                                    <td><?php echo htmlspecialchars($u["creado_en"]); ?></td>
                                    <td>
                                        <?php if ($u["usuario"] != $_SESSION["usuario"]) { ?>
                                            <a
                                                href="usuarios.php?eliminar=<?php echo $u["id"]; ?>"
                                                class="accion-eliminar"
                                                onclick="return confirm('¿Eliminar este usuario?');"
                                            >
                                                Eliminar
                                            </a>
                                        <?php } else { ?>
                                            <span style="color:#555; font-size:11px;">(vos)</span>
                                        <?php } ?>
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
