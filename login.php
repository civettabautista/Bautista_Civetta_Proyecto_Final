<?php
/*
 * login.php
 * Inicio de sesión con correo electrónico y contraseña.
 */
require_once 'includes/funciones.php';

// Si ya inició sesión, lo mandamos a su panel
if (estaLogueado()) {
    redirigir(esAdmin() ? 'admin_usuarios.php' : 'panel.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf('login.php');

    $email    = is_string($_POST['email'] ?? null) ? mb_strtolower(trim($_POST['email'])) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if ($email === '' || $password === '') {
        $error = 'Completá el correo electrónico y la contraseña.';
    } else {
        // 1) Buscamos al usuario por su email (consulta preparada)
        $stmt = $pdo->prepare('SELECT id, nombre, password, rol, fecha_archivado FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        // 2) Comparamos la contraseña escrita con el hash guardado
        if (!$usuario || !password_verify($password, $usuario['password'])) {
            // Mensaje genérico: no revelamos si el email existe o no
            $error = 'El correo electrónico o la contraseña son incorrectos.';
        } elseif ($usuario['fecha_archivado'] !== null) {
            // 3) Las cuentas archivadas no pueden ingresar
            $error = 'Tu cuenta está archivada. Comunicate con un administrador para reactivarla.';
        } else {
            // 4) Datos correctos: nuevo ID de sesión (evita robo de sesión) y guardamos los datos
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre']     = $usuario['nombre'];
            $_SESSION['rol']        = $usuario['rol'];

            setFlash('exito', '¡Hola, ' . $usuario['nombre'] . '! Iniciaste sesión correctamente.');

            // 5) Si tenía productos en el carrito, vuelve al carrito para terminar la compra.
            //    Si no, el admin va al panel administrativo y el cliente a su panel personal.
            //    (session_regenerate_id cambia el ID pero conserva los datos, así que el carrito sigue ahí)
            if (!empty($_SESSION['carrito'])) {
                redirigir('carrito.php');
            }
            redirigir($usuario['rol'] === 'admin' ? 'admin_usuarios.php' : 'panel.php');
        }
    }
}

encabezado('Iniciar sesión');
?>

<section class="caja caja-form caja-chica">
    <h1>Iniciar sesión</h1>
    <p class="subtitulo">Ingresá con tu correo y contraseña.</p>

    <?php if ($error): ?>
        <div class="alerta alerta-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" novalidate>
        <?= campoCsrf() ?>

        <label>Correo electrónico
            <input type="email" name="email" required value="<?= e($email) ?>" autofocus>
        </label>

        <label>Contraseña
            <input type="password" name="password" required>
        </label>

        <button type="submit" class="btn btn-azul btn-bloque">Ingresar</button>
    </form>

    <p class="pie-form">¿No tenés cuenta? <a href="registro.php">Registrate</a></p>
</section>

<?php pie(); ?>
