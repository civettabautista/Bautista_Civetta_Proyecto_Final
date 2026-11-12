<?php
/*
 * registro.php
 * Registro público de clientes.
 * Recorrido: formulario HTML -> $_POST -> validación en PHP -> INSERT con PDO.
 */
require_once 'includes/funciones.php';

// Si ya tiene la sesión iniciada, no necesita registrarse
if (estaLogueado()) {
    redirigir('panel.php');
}

$errores = [];
$datos   = ['nombre' => '', 'apellido' => '', 'dni' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf('registro.php');

    $datos = obtenerDatosUsuario($_POST);

    // El registro público exige contraseña y NO deja elegir el rol
    $errores = validarUsuario($pdo, $datos, true, false);

    if (!$errores) {
        // El rol es SIEMPRE 'cliente', sin importar lo que llegue en el formulario
        $resultado = insertarUsuario($pdo, $datos, 'cliente');

        if ($resultado === true) {
            setFlash('exito', '¡Cuenta creada con éxito! Ya podés iniciar sesión.');
            redirigir('login.php');
        }
        $errores[] = $resultado;
    }
}

encabezado('Crear cuenta');
?>

<section class="caja caja-form">
    <h1>Crear cuenta</h1>
    <p class="subtitulo">Sumate a BrickStore completando tus datos.</p>

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

    <form method="post" action="registro.php" novalidate>
        <?= campoCsrf() ?>

        <div class="fila">
            <label>Nombre
                <input type="text" name="nombre" maxlength="50" required value="<?= e($datos['nombre']) ?>">
            </label>
            <label>Apellido
                <input type="text" name="apellido" maxlength="50" required value="<?= e($datos['apellido']) ?>">
            </label>
        </div>

        <label>DNI <small>(7 u 8 números, sin puntos)</small>
            <input type="text" name="dni" inputmode="numeric" pattern="\d{7,8}" maxlength="8" required value="<?= e($datos['dni']) ?>">
        </label>

        <label>Correo electrónico
            <input type="email" name="email" maxlength="100" required value="<?= e($datos['email']) ?>">
        </label>

        <!-- La contraseña nunca se vuelve a mostrar, por eso no tiene value -->
        <label>Contraseña <small>(mínimo 8 caracteres)</small>
            <input type="password" name="password" minlength="8" maxlength="72" required>
        </label>

        <button type="submit" class="btn btn-amarillo btn-bloque">Crear mi cuenta</button>
    </form>

    <p class="pie-form">¿Ya tenés cuenta? <a href="login.php">Iniciá sesión</a></p>
</section>

<?php pie(); ?>
