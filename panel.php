<?php
/*
 * panel.php
 * Panel personal: solo se puede ver con la sesión iniciada.
 */
require_once 'includes/funciones.php';

requerirLogin(); // sin sesión -> vuelve al login con un mensaje

// Traemos los datos actualizados desde la base de datos.
// Seleccionamos columnas puntuales: la contraseña (hash) NUNCA se pide.
$stmt = $pdo->prepare(
    'SELECT nombre, apellido, dni, email, rol, fecha_creacion FROM usuarios WHERE id = ?'
);
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

// Pedidos del usuario: un renglón por pedido, con sus productos unidos en un texto
$stmt = $pdo->prepare(
    "SELECT p.id, p.fecha, p.total,
            GROUP_CONCAT(CONCAT(i.cantidad, ' x ', i.nombre) SEPARATOR ', ') AS detalle
     FROM pedidos p
     JOIN pedido_items i ON i.pedido_id = p.id
     WHERE p.usuario_id = ?
     GROUP BY p.id, p.fecha, p.total
     ORDER BY p.fecha DESC"
);
$stmt->execute([$_SESSION['usuario_id']]);
$pedidos = $stmt->fetchAll();

encabezado('Mi cuenta');
?>

<section class="caja">
    <h1>Mi cuenta en BrickStore</h1>
    <p class="subtitulo">Estos son los datos registrados en tu cuenta.</p>

    <!-- Lista de datos: dt = nombre del dato, dd = valor -->
    <dl class="ficha">
        <dt>Nombre</dt>             <dd><?= e($usuario['nombre']) ?></dd>
        <dt>Apellido</dt>           <dd><?= e($usuario['apellido']) ?></dd>
        <dt>DNI</dt>                <dd><?= e($usuario['dni']) ?></dd>
        <dt>Correo electrónico</dt> <dd><?= e($usuario['email']) ?></dd>
        <dt>Tipo de cuenta</dt>     <dd><?= $usuario['rol'] === 'admin' ? 'Administrador' : 'Cliente' ?></dd>
        <dt>Cuenta creada el</dt>   <dd><?= e(date('d/m/Y \a \l\a\s H:i', strtotime($usuario['fecha_creacion']))) ?></dd>
    </dl>

    <?php if (esAdmin()): ?>
        <p><a href="admin_usuarios.php" class="btn btn-azul">Ir al panel de administración</a></p>
    <?php endif; ?>
</section>

<section class="caja">
    <h2>Mis pedidos</h2>

    <?php if (!$pedidos): ?>
        <p class="info">Todavía no hiciste ninguna compra. <a href="index.php#catalogo">Ver el catálogo</a></p>
    <?php else: ?>
        <div class="tabla-scroll">
            <table class="tabla">
                <thead><tr><th>Pedido</th><th>Fecha</th><th>Productos</th><th>Total</th></tr></thead>
                <tbody>
                <?php foreach ($pedidos as $ped): ?>
                    <tr>
                        <td><?= (int) $ped['id'] ?></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($ped['fecha']))) ?></td>
                        <td><?= e($ped['detalle']) ?></td>
                        <td><?= e('US$ ' . number_format((float) $ped['total'], 2, ',', '.')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php pie(); ?>
