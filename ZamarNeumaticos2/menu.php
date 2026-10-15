<?php
// Incluir este archivo después de definir $pagina_activa, por ejemplo:
// $pagina_activa = "panel"; // panel | stock | compras | presupuestos | ventas | clientes | proveedores | usuarios
?>
<aside class="sidebar">

    <div class="logo">

        <img src="fotos/logo.png" alt="Logo Zamar Neumáticos">

    </div>


    <nav class="menu">

        <!-- PANEL: cuadrícula de tablero -->
        <a href="index.php" class="menu-item <?php echo ($pagina_activa == "panel") ? "activo" : ""; ?>">
            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#777777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </span>
            <p>Panel</p>
        </a>

        <!-- STOCK: rueda / neumático -->
        <a href="stock.php" class="menu-item <?php echo ($pagina_activa == "stock") ? "activo" : ""; ?>">
            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#777777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3.5"/><line x1="12" y1="2" x2="12" y2="8.5"/><line x1="12" y1="15.5" x2="12" y2="22"/><line x1="2" y1="12" x2="8.5" y2="12"/><line x1="15.5" y1="12" x2="22" y2="12"/></svg>
            </span>
            <p>Stock</p>
        </a>

        <!-- COMPRAS: carrito -->
        <a href="compra.php" class="menu-item <?php echo ($pagina_activa == "compras") ? "activo" : ""; ?>">
            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#777777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            </span>
            <p>Compras</p>
        </a>

        <!-- PRESUPUESTOS: documento -->
        <a href="presupuestos.php" class="menu-item <?php echo ($pagina_activa == "presupuestos") ? "activo" : ""; ?>">
            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#777777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </span>
            <p>Presupuestos</p>
        </a>

        <!-- VENTAS: signo pesos -->
        <a href="ventas.php" class="menu-item <?php echo ($pagina_activa == "ventas") ? "activo" : ""; ?>">
            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#777777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </span>
            <p>Ventas</p>
        </a>

        <!-- CLIENTES: grupo de personas -->
        <a href="clientes.php" class="menu-item <?php echo ($pagina_activa == "clientes") ? "activo" : ""; ?>">
            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#777777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <p>Clientes</p>
        </a>

        <!-- PROVEEDORES: camión -->
        <a href="proveedores.php" class="menu-item <?php echo ($pagina_activa == "proveedores") ? "activo" : ""; ?>">
            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#777777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            </span>
            <p>Proveedores</p>
        </a>

        <!-- USUARIOS: una persona -->
        <a href="usuarios.php" class="menu-item <?php echo ($pagina_activa == "usuarios") ? "activo" : ""; ?>">
            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#777777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
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
