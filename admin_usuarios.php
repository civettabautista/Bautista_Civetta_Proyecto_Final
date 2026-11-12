<?php
/*
 * admin_usuarios.php
 * Panel administrativo: alta de usuarios, listado de activos y buscador.
 * Solo accesible para usuarios con rol "admin".
 */
require_once 'includes/funciones.php';

requerirAdmin(); // sin sesión -> login / cliente -> acceso denegado

// ---------- 1) Alta de un usuario nuevo desde el panel ----------
$errores = [];
$nuevo   = ['nombre' => '', 'apellido' => '', 'dni' => '', 'email' => '', 'rol' => 'cliente'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf('admin_usuarios.php');

    $nuevo = obtenerDatosUsuario($_POST);

    // Mismas validaciones que el registro público + validación del rol
    $errores = validarUsuario($pdo, $nuevo, true, true);

    if (!$errores) {
        $resultado = insertarUsuario($pdo, $nuevo, $nuevo['rol']);
        if ($resultado === true) {
            setFlash('exito', 'Usuario ' . $nuevo['nombre'] . ' ' . $nuevo['apellido'] . ' registrado correctamente.');
            redirigir('admin_usuarios.php');
        }
        $errores[] = $resultado;
    }
}

// ---------- 2) Listado de usuarios ACTIVOS, con buscador opcional ----------
$busqueda = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';

$sql = 'SELECT id, nombre, apellido, dni, email, rol, fecha_creacion
        FROM usuarios
        WHERE fecha_archivado IS NULL';
$parametros = [];

if ($busqueda !== '') {
    // Un solo campo busca en nombre, apellido, DNI o email.
    // El texto va como parámetro (nunca concatenado en el SQL).
    $sql .= ' AND (nombre LIKE ? OR apellido LIKE ? OR dni LIKE ? OR email LIKE ?)';
    $patron = '%' . $busqueda . '%';
    $parametros = [$patron, $patron, $patron, $patron];
}
$sql .= ' ORDER BY apellido, nombre';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$usuarios = $stmt->fetchAll();

encabezado('Administrar usuarios');
?>

<div class="encabezado">
    <div>
        <h1>Administrar usuarios</h1>
        <p class="subtitulo">Usuarios activos de BrickStore.</p>
    </div>
    <a href="usuarios_archivados.php" class="btn btn-gris">Ver archivados</a>
</div>

<div class="grilla-admin">
    <!-- ===== Formulario de alta ===== -->
    <section class="caja">
        <h2>Registrar usuario</h2>

        <?php if ($errores): ?>
            <div class="alerta alerta-error">
                <strong>No se pudo registrar:</strong>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="admin_usuarios.php" novalidate>
            <?= campoCsrf() ?>
            <label>Nombre
                <input type="text" name="nombre" maxlength="50" required value="<?= e($nuevo['nombre']) ?>">
            </label>
            <label>Apellido
                <input type="text" name="apellido" maxlength="50" required value="<?= e($nuevo['apellido']) ?>">
            </label>
            <label>DNI
                <input type="text" name="dni" inputmode="numeric" maxlength="8" required value="<?= e($nuevo['dni']) ?>">
            </label>
            <label>Correo electrónico
                <input type="email" name="email" maxlength="100" required value="<?= e($nuevo['email']) ?>">
            </label>
            <label>Contraseña
                <input type="password" name="password" minlength="8" maxlength="72" required>
            </label>
            <label>Rol
                <select name="rol">
                    <option value="cliente" <?= $nuevo['rol'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                    <option value="admin"   <?= $nuevo['rol'] === 'admin'   ? 'selected' : '' ?>>Administrador</option>
                </select>
            </label>
            <button type="submit" class="btn btn-amarillo btn-bloque">Registrar</button>
        </form>
    </section>

    <!-- ===== Buscador y listado ===== -->
    <section class="caja">
        <form method="get" action="admin_usuarios.php" class="buscador">
            <input type="search" name="q" placeholder="Buscar por nombre, apellido, DNI o email"
                   value="<?= e($busqueda) ?>">
            <button type="submit" class="btn btn-azul">Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="admin_usuarios.php" class="btn btn-gris">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if ($busqueda !== ''): ?>
            <p class="info">Resultados para "<strong><?= e($busqueda) ?></strong>": <?= count($usuarios) ?></p>
        <?php endif; ?>

        <?php if (!$usuarios): ?>
            <div class="alerta alerta-info">
                <?= $busqueda !== '' ? 'No se encontraron usuarios que coincidan con la búsqueda.' : 'Todavía no hay usuarios activos.' ?>
            </div>
        <?php else: ?>
            <div class="tabla-scroll">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Nombre</th><th>DNI</th><th>Email</th><th>Rol</th><th>Alta</th><th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><?= e($u['apellido'] . ', ' . $u['nombre']) ?></td>
                            <td><?= e($u['dni']) ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td><span class="chip chip-<?= e($u['rol']) ?>"><?= e($u['rol']) ?></span></td>
                            <td><?= e(date('d/m/Y', strtotime($u['fecha_creacion']))) ?></td>
                            <td><div class="acciones-tabla">
                                <a href="editar_usuario.php?id=<?= (int) $u['id'] ?>" class="btn btn-azul btn-chico">Editar</a>

                                <?php if ((int) $u['id'] !== (int) $_SESSION['usuario_id']): ?>
                                    <!-- Archivar es una acción que modifica datos: va por POST con token CSRF -->
                                    <form method="post" action="archivar_usuario.php"
                                          data-confirmar="¿Archivar a <?= e($u['nombre'] . ' ' . $u['apellido']) ?>?"
                                          onsubmit="return confirm(this.dataset.confirmar);">
                                        <?= campoCsrf() ?>
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit" class="btn btn-rojo btn-chico">Archivar</button>
                                    </form>
                                <?php else: ?>
                                    <span class="info">(tu cuenta)</span>
                                <?php endif; ?>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php pie(); ?>
