<?php
// Incluir este archivo después de definir $pagina_activa, por ejemplo:
// $pagina_activa = "panel"; // panel | stock | presupuestos | ventas | clientes | usuarios
?>
<aside class="sidebar">

    <div class="logo">

        <img src="fotos/logo.png" alt="Logo Zamar Neumáticos">

    </div>


    <nav class="menu">

        <a href="index.php" class="menu-item <?php echo ($pagina_activa == "panel") ? "activo" : ""; ?>">
            <span>◉</span>
            <p>Panel</p>
        </a>

        <a href="stock.php" class="menu-item <?php echo ($pagina_activa == "stock") ? "activo" : ""; ?>">
            <span>◉</span>
            <p>Stock</p>
        </a>

        <a href="compra.php" class="menu-item <?php echo ($pagina_activa == "compras") ? "activo" : ""; ?>">
            <span>◉</span>
            <p>Compras</p>
        </a>

        <a href="presupuestos.php" class="menu-item <?php echo ($pagina_activa == "presupuestos") ? "activo" : ""; ?>">
            <span>◉</span>
            <p>Presupuestos</p>
        </a>

        <a href="ventas.php" class="menu-item <?php echo ($pagina_activa == "ventas") ? "activo" : ""; ?>">
            <span>$</span>
            <p>Ventas</p>
        </a>

        <a href="clientes.php" class="menu-item <?php echo ($pagina_activa == "clientes") ? "activo" : ""; ?>">
            <span>◉</span>
            <p>Clientes</p>
        </a>

        <a href="proveedores.php" class="menu-item <?php echo ($pagina_activa == "proveedores") ? "activo" : ""; ?>">
            <span>◉</span>
            <p>Proveedores</p>
        </a>

        <a href="usuarios.php" class="menu-item <?php echo ($pagina_activa == "usuarios") ? "activo" : ""; ?>">
            <span>◉</span>
            <p>Usuarios</p>
        </a>

    </nav>


    <div class="usuario">

        <strong><?php echo htmlspecialchars($_SESSION["usuario"] ?? "Administrador"); ?></strong>

        <span>ADMIN</span>

        <a href="logout.php" class="boton-salir">
            ⇥ &nbsp; CERRAR SESIÓN
        </a>

    </div>

</aside>
