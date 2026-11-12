<?php
/*
 * usuarios_archivados.php
 * Listado de usuarios archivados, con opción de reactivarlos (solo admin).
 */
require_once 'includes/funciones.php';

requerirAdmin();

$stmt = $pdo->prepare(
    'SELECT id, nombre, apellido, dni, email, rol, fecha_archivado
     FROM usuarios
     WHERE fecha_archivado IS NOT NULL
     ORDER BY fecha_archivado DESC'
);
$stmt->execute();
$archivados = $stmt->fetchAll();

encabezado('Usuarios archivados');
?>

<div class="encabezado">
    <div>
        <h1>Usuarios archivados</h1>
        <p class="subtitulo">Estas cuentas no pueden iniciar sesión hasta que se reactiven.</p>
    </div>
    <a href="admin_usuarios.php" class="btn btn-gris">Volver a usuarios activos</a>
</div>

<section class="caja">
    <?php if (!$archivados): ?>
        <div class="alerta alerta-info">No hay usuarios archivados.</div>
    <?php else: ?>
        <div class="tabla-scroll">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Nombre</th><th>DNI</th><th>Email</th><th>Rol</th><th>Archivado el</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($archivados as $u): ?>
                    <tr>
                        <td><?= e($u['apellido'] . ', ' . $u['nombre']) ?></td>
                        <td><?= e($u['dni']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="chip chip-<?= e($u['rol']) ?>"><?= e($u['rol']) ?></span></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($u['fecha_archivado']))) ?></td>
                        <td><div class="acciones-tabla">
                            <!-- Reactivar modifica datos: va por POST con token CSRF -->
                            <form method="post" action="reactivar_usuario.php">
                                <?= campoCsrf() ?>
                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                <button type="submit" class="btn btn-verde btn-chico">Reactivar</button>
                            </form>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php pie(); ?>
