<?php
/*
 * editar_usuario.php
 * Edición de los datos de un usuario existente (solo admin).
 * GET  editar_usuario.php?id=5  -> muestra el formulario con los datos actuales
 * POST editar_usuario.php       -> valida y guarda los cambios
 */
require_once 'includes/funciones.php';

requerirAdmin();

// El ID llega por GET al abrir la página o por POST al guardar
$id = validarId($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['id'] ?? null) : ($_GET['id'] ?? null));

if ($id === null) {
    setFlash('error', 'El usuario indicado no es válido.');
    redirigir('admin_usuarios.php');
}

// Buscamos al usuario (sin la contraseña)
$stmt = $pdo->prepare('SELECT id, nombre, apellido, dni, email, rol, fecha_archivado FROM usuarios WHERE id = ?');
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    setFlash('error', 'El usuario que querés editar no existe.');
    redirigir('admin_usuarios.php');
}

$esUnoMismo = ($id === (int) $_SESSION['usuario_id']);
$errores    = [];
$datos      = $usuario; // valores iniciales del formulario

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf('editar_usuario.php?id=' . $id);

    $datos = obtenerDatosUsuario($_POST);

    // Un admin no puede quitarse su propio rol (se quedaría sin acceso al panel)
    if ($esUnoMismo) {
        $datos['rol'] = 'admin';
    }

    // Contraseña opcional; DNI y email no pueden coincidir con los de OTRO usuario
    $errores = validarUsuario($pdo, $datos, false, true, $id);

    if (!$errores) {
        try {
            if ($datos['password'] !== '') {
                // Se escribió una nueva contraseña: la guardamos hasheada
                $stmt = $pdo->prepare(
                    'UPDATE usuarios SET nombre = ?, apellido = ?, dni = ?, email = ?, rol = ?, password = ? WHERE id = ?'
                );
                $stmt->execute([$datos['nombre'], $datos['apellido'], $datos['dni'], $datos['email'],
                                $datos['rol'], password_hash($datos['password'], PASSWORD_DEFAULT), $id]);
            } else {
                // Campo vacío: la contraseña actual no se modifica
                $stmt = $pdo->prepare(
                    'UPDATE usuarios SET nombre = ?, apellido = ?, dni = ?, email = ?, rol = ? WHERE id = ?'
                );
                $stmt->execute([$datos['nombre'], $datos['apellido'], $datos['dni'], $datos['email'],
                                $datos['rol'], $id]);
            }

            setFlash('exito', 'Los datos de ' . $datos['nombre'] . ' ' . $datos['apellido'] . ' se actualizaron correctamente.');
            redirigir($usuario['fecha_archivado'] === null ? 'admin_usuarios.php' : 'usuarios_archivados.php');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errores[] = $e->getCode() === '23000'
                ? 'El DNI o el correo electrónico ya pertenecen a otro usuario.'
                : 'No se pudieron guardar los cambios. Intentá de nuevo.';
        }
    }
}

encabezado('Editar usuario');
?>

<section class="caja caja-form">
    <h1>Editar usuario</h1>
    <p class="subtitulo">Modificá los datos y guardá los cambios.</p>

    <?php if ($errores): ?>
        <div class="alerta alerta-error">
            <strong>Revisá los siguientes datos:</strong>
            <ul>
                <?php foreach ($errores as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="editar_usuario.php" novalidate>
        <?= campoCsrf() ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <div class="fila">
            <label>Nombre
                <input type="text" name="nombre" maxlength="50" required value="<?= e($datos['nombre']) ?>">
            </label>
            <label>Apellido
                <input type="text" name="apellido" maxlength="50" required value="<?= e($datos['apellido']) ?>">
            </label>
        </div>

        <label>DNI
            <input type="text" name="dni" inputmode="numeric" maxlength="8" required value="<?= e($datos['dni']) ?>">
        </label>

        <label>Correo electrónico
            <input type="email" name="email" maxlength="100" required value="<?= e($datos['email']) ?>">
        </label>

        <label>Rol
            <?php if ($esUnoMismo): ?>
                <input type="text" value="Administrador (no podés quitarte tu propio rol)" disabled>
                <input type="hidden" name="rol" value="admin">
            <?php else: ?>
                <select name="rol">
                    <option value="cliente" <?= $datos['rol'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                    <option value="admin"   <?= $datos['rol'] === 'admin'   ? 'selected' : '' ?>>Administrador</option>
                </select>
            <?php endif; ?>
        </label>

        <!-- La contraseña actual nunca se muestra -->
        <label>Nueva contraseña <small>(dejalo vacío para no cambiarla)</small>
            <input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password">
        </label>

        <div class="acciones">
            <button type="submit" class="btn btn-azul">Guardar cambios</button>
            <a href="admin_usuarios.php" class="btn btn-gris">Cancelar</a>
        </div>
    </form>
</section>

<?php pie(); ?>
