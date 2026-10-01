<?php

session_start();
require "conexion.php";

if (isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = trim($_POST["usuario"]);
    $clave = $_POST["clave"];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
    $stmt->execute(["usuario" => $usuario]);
    $fila = $stmt->fetch();

    if ($fila && password_verify($clave, $fila["clave"])) {

        $_SESSION["usuario"] = $fila["usuario"];

        header("Location: index.php");
        exit();

    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Acceso - Zamar Neumáticos</title>

    <link rel="stylesheet" href="login.css">

</head>

<body>

    <div class="login-container">

        <div class="logo">

            <div class="logo-cuadro">
                Z
            </div>

            <h1>ZAMAR</h1>

            <span>NEUMÁTICOS</span>

        </div>


        <div class="titulo">

            <span>ACCESO AL SISTEMA</span>

            <h2>INICIAR SESIÓN</h2>

        </div>


        <form method="POST">

            <label for="usuario">
                USUARIO
            </label>

            <input
                type="text"
                id="usuario"
                name="usuario"
                placeholder="Ingrese su usuario"
                required
            >


            <label for="clave">
                CONTRASEÑA
            </label>

            <input
                type="password"
                id="clave"
                name="clave"
                placeholder="Ingrese su contraseña"
                required
            >


            <?php if ($error != "") { ?>

                <p class="error">
                    <?php echo htmlspecialchars($error); ?>
                </p>

            <?php } ?>


            <button type="submit">
                INGRESAR
            </button>

        </form>


        <div class="pie">
            SISTEMA DE GESTIÓN · ZAMAR NEUMÁTICOS
        </div>

    </div>

</body>

</html>
