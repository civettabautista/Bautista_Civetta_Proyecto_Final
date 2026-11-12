<?php
/*
 * includes/plantilla.php
 * Parte de arriba y de abajo de todas las páginas.
 * Cada página llama a encabezado('Título') al empezar y a pie() al terminar.
 * El menú cambia según haya sesión iniciada y según el rol.
 */

/** Devuelve la clase "activo" si el enlace corresponde a la página actual. */
function claseActiva(string $archivo): string
{
    return $archivo === basename($_SERVER['PHP_SELF']) ? ' class="activo"' : '';
}

/** Imprime el <head>, el logo, el menú y los mensajes pendientes. */
function encabezado(string $titulo): void
{
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | BrickStore</title>
    <!-- ?v= cambia cada vez que se modifica el CSS, así el navegador no usa una copia vieja guardada -->
    <link rel="stylesheet" href="css/styles.css?v=<?= filemtime(__DIR__ . '/../css/styles.css') ?>">
</head>
<body>
<header class="barra">
    <a href="index.php" class="logo">Brick<span>Store</span></a>

    <nav class="menu">
        <a href="index.php#catalogo">Catálogo</a>
        <!-- Cantidad de unidades en el carrito (guardado en la sesión) -->
        <a href="carrito.php"<?= claseActiva('carrito.php') ?>>Carrito <span class="contador"><?= unidadesEnCarrito() ?></span></a>
        <?php if (!estaLogueado()): ?>
            <a href="login.php"<?= claseActiva('login.php') ?>>Iniciar sesión</a>
            <a href="registro.php" class="btn btn-amarillo">Crear cuenta</a>
        <?php else: ?>
            <a href="panel.php"<?= claseActiva('panel.php') ?>>Mi cuenta</a>
            <?php if (esAdmin()): ?>
                <a href="admin_usuarios.php"<?= claseActiva('admin_usuarios.php') ?>>Usuarios</a>
                <a href="usuarios_archivados.php"<?= claseActiva('usuarios_archivados.php') ?>>Archivados</a>
            <?php endif; ?>
            <span class="saludo">Hola, <?= e($_SESSION['nombre']) ?><?= esAdmin() ? ' (admin)' : '' ?></span>
            <a href="cerrar_sesion.php" class="btn btn-gris">Cerrar sesión</a>
        <?php endif; ?>
    </nav>
</header>

<main class="contenedor">
    <?php mostrarFlash(); ?>
<?php
}

/** Imprime el pie de página y cierra el HTML. */
function pie(): void
{
?>
</main>

<footer class="pie">
    <p>Trabajo realizado por Bautista Civetta 4°A</p>
</footer>
</body>
</html>
<?php
}
