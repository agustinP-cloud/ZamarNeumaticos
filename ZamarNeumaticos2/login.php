<?php

session_start();
require "conexion.php";

if (isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit();
}

$error = "";
$usuario_ingresado = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = trim($_POST["usuario"]);
    $clave = $_POST["clave"];

    $usuario_ingresado = $usuario;

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

    <!-- Estilos incluidos acá mismo: el login no depende de ningún otro archivo CSS -->
    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;

            display: grid;
            grid-template-columns: 1fr 1fr;

            background-color: #0d0d0d;
            color: #ffffff;

            font-family: Arial, Helvetica, sans-serif;
        }


        /* ===== PANEL IZQUIERDO: MARCA ===== */

        .panel-marca {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            padding: 40px;

            background:
                radial-gradient(circle at center, rgba(255, 217, 0, 0.10), transparent 65%),
                #0a0a0a;

            border-right: 1px solid #242424;
        }

        .panel-marca img {
            display: block;

            width: 100%;
            max-width: 420px;
            height: auto;
        }

        .panel-marca .lema {
            margin-top: 30px;

            color: #666;

            font-size: 9px;
            letter-spacing: 4px;

            text-align: center;
        }

        .panel-marca .linea {
            width: 60px;
            height: 3px;

            margin-top: 18px;

            background-color: #ffd900;
        }


        /* ===== PANEL DERECHO: FORMULARIO ===== */

        .panel-form {
            display: flex;
            align-items: center;
            justify-content: center;

            padding: 40px;
        }

        .caja-login {
            width: 100%;
            max-width: 380px;
        }

        .caja-login .etiqueta {
            color: #777;

            font-size: 9px;
            letter-spacing: 3px;
        }

        .caja-login h2 {
            margin-top: 8px;
            margin-bottom: 35px;

            font-size: 26px;
            letter-spacing: 1px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 7px;

            color: #888;

            font-size: 9px;
            letter-spacing: 2px;
        }

        input {
            height: 46px;

            margin-bottom: 22px;
            padding: 0 14px;

            background-color: #111111;

            border: 1px solid #303030;

            color: #ffffff;
            font-size: 14px;

            outline: none;
        }

        input:focus {
            border-color: #ffd900;
        }

        input::placeholder {
            color: #555;
        }

        .error {
            margin-top: -6px;
            margin-bottom: 18px;

            color: #e6c400;

            font-size: 12px;
        }

        button {
            height: 48px;

            margin-top: 5px;

            background-color: #ffd900;

            border: none;

            color: #111;

            font-size: 11px;
            font-weight: bold;
            letter-spacing: 2px;

            cursor: pointer;
        }

        button:hover {
            background-color: #e6c400;
        }

        .pie {
            margin-top: 40px;
            padding-top: 20px;

            border-top: 1px solid #292929;

            color: #555;

            font-size: 8px;
            letter-spacing: 2px;

            text-align: center;
        }


        /* ===== CELULAR / PANTALLA CHICA ===== */

        @media (max-width: 800px) {

            body {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
            }

            .panel-marca {
                padding: 30px 20px;

                border-right: none;
                border-bottom: 1px solid #242424;
            }

            .panel-marca img {
                max-width: 220px;
            }

            .panel-marca .lema,
            .panel-marca .linea {
                display: none;
            }

        }

    </style>

</head>

<body>

    <!-- IZQUIERDA: LOGO DEL NEGOCIO -->
    <div class="panel-marca">

        <img
            src="fotos/logo.png"
            alt="Logo Zamar Neumáticos"
            style="max-width: 420px; width: 100%; height: auto;"
        >

        <div class="linea"></div>

        <div class="lema">
            SISTEMA DE GESTIÓN
        </div>

    </div>


    <!-- DERECHA: FORMULARIO -->
    <div class="panel-form">

        <div class="caja-login">

            <span class="etiqueta">ACCESO AL SISTEMA</span>

            <h2>INICIAR SESIÓN</h2>


            <form method="POST">

                <label for="usuario">
                    USUARIO
                </label>

                <input
                    type="text"
                    id="usuario"
                    name="usuario"
                    placeholder="Ingrese su usuario"
                    value="<?php echo htmlspecialchars($usuario_ingresado); ?>"
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

    </div>

</body>

</html>
