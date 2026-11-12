<?php
/*
 * includes/funciones.php
 * Todo el código que comparten las páginas, en un solo archivo:
 *   1. Sesión y seguridad: escape HTML, mensajes, control de acceso, token CSRF
 *   2. Validación y alta de usuarios (registro, alta desde el admin, edición)
 *   3. Catálogo de la tienda
 * Al final incluye plantilla.php (encabezado y pie de las páginas).
 *
 * Cada página empieza con: require_once 'includes/funciones.php';
 */

require_once __DIR__ . '/../conexion.php';

/* =====================================================================
   1. SESIÓN Y SEGURIDAD
   ===================================================================== */

// Iniciamos la sesión una sola vez. La cookie de sesión no es accesible
// desde JavaScript (httponly) y no se envía desde otros sitios (samesite).
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Escapa un texto para mostrarlo en HTML de forma segura.
 * Convierte < > " ' & en entidades para que no se ejecute código en la página.
 */
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/** Redirige a otra página y corta la ejecución del script. */
function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Guarda un mensaje para mostrarlo en la próxima página (tipo: exito o error). */
function setFlash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/** Muestra los mensajes guardados y los borra para que no se repitan. */
function mostrarFlash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    foreach ($_SESSION['flash'] as $f) {
        echo '<div class="alerta alerta-' . e($f['tipo']) . '">' . e($f['mensaje']) . '</div>';
    }
    unset($_SESSION['flash']);
}

/** ¿Hay un usuario con la sesión iniciada? */
function estaLogueado(): bool
{
    return isset($_SESSION['usuario_id']);
}

/** ¿El usuario logueado es administrador? */
function esAdmin(): bool
{
    return estaLogueado() && ($_SESSION['rol'] ?? '') === 'admin';
}

/**
 * Protege una página privada: si no hay sesión, manda al login.
 * Además vuelve a consultar la base de datos para confirmar que el usuario
 * sigue existiendo y no fue archivado mientras tenía la sesión abierta.
 */
function requerirLogin(): void
{
    global $pdo;

    if (!estaLogueado()) {
        setFlash('error', 'Tenés que iniciar sesión para acceder a esa sección.');
        redirigir('login.php');
    }

    $stmt = $pdo->prepare('SELECT nombre, rol, fecha_archivado FROM usuarios WHERE id = ?');
    $stmt->execute([$_SESSION['usuario_id']]);
    $u = $stmt->fetch();

    if (!$u || $u['fecha_archivado'] !== null) {
        // El usuario ya no está activo: cerramos su sesión
        $_SESSION = [];
        session_regenerate_id(true);
        setFlash('error', 'Tu cuenta no está activa. Comunicate con un administrador.');
        redirigir('login.php');
    }

    // Actualizamos los datos de la sesión por si un admin los cambió
    $_SESSION['nombre'] = $u['nombre'];
    $_SESSION['rol']    = $u['rol'];
}

/** Protege una página o acción administrativa: exige sesión Y rol admin. */
function requerirAdmin(): void
{
    requerirLogin();
    if (!esAdmin()) {
        setFlash('error', 'Acceso denegado: esa sección es solo para administradores.');
        redirigir('panel.php');
    }
}

/** Devuelve el token CSRF de la sesión (lo crea si todavía no existe). */
function tokenCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Campo oculto con el token CSRF para poner dentro de cada formulario. */
function campoCsrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(tokenCsrf()) . '">';
}

/**
 * Verifica que el formulario recibido traiga el token CSRF correcto.
 * Si no coincide, la solicitud no salió de nuestro sistema: se rechaza.
 */
function verificarCsrf(string $volverA): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(tokenCsrf(), $token)) {
        setFlash('error', 'La solicitud no es válida o expiró. Volvé a intentarlo.');
        redirigir($volverA);
    }
}

/** Exige que la página se haya llamado por POST (para acciones que modifican datos). */
function requerirPost(string $volverA): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        setFlash('error', 'Acción no permitida.');
        redirigir($volverA);
    }
}

/** Valida que un ID recibido sea un número entero positivo. Devuelve el ID o null. */
function validarId($valor): ?int
{
    $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

/* =====================================================================
   2. VALIDACIÓN Y ALTA DE USUARIOS
   Las usan el registro público, el alta desde el panel admin y la edición,
   así no repetimos código.
   ===================================================================== */

/**
 * Toma los datos del formulario ($_POST) y devuelve solo los campos que nos
 * interesan, como texto y sin espacios al principio ni al final.
 */
function obtenerDatosUsuario(array $form): array
{
    $campos = ['nombre', 'apellido', 'dni', 'email', 'password', 'rol'];
    $datos  = [];
    foreach ($campos as $campo) {
        $valor = $form[$campo] ?? '';
        $datos[$campo] = is_string($valor) ? trim($valor) : '';
    }
    $datos['email'] = mb_strtolower($datos['email']); // emails siempre en minúscula
    return $datos;
}

/**
 * Valida los datos de un usuario y devuelve un array de errores
 * (si el array está vacío, los datos son correctos).
 *
 * @param bool     $exigirPassword true en el registro; false en la edición (la contraseña es opcional)
 * @param bool     $validarRol     true cuando el formulario permite elegir rol (solo admin)
 * @param int|null $idExcluir      en la edición, el ID del usuario que se edita (para no compararlo consigo mismo)
 */
function validarUsuario(PDO $pdo, array $d, bool $exigirPassword, bool $validarRol, ?int $idExcluir = null): array
{
    $errores = [];

    // --- Campos obligatorios ---
    if ($d['nombre'] === '')   { $errores[] = 'El nombre es obligatorio.'; }
    if ($d['apellido'] === '') { $errores[] = 'El apellido es obligatorio.'; }
    if ($d['dni'] === '')      { $errores[] = 'El DNI es obligatorio.'; }
    if ($d['email'] === '')    { $errores[] = 'El correo electrónico es obligatorio.'; }
    if ($exigirPassword && $d['password'] === '') { $errores[] = 'La contraseña es obligatoria.'; }

    // --- Formato de cada campo ---
    $soloLetras = "/^[\p{L} '\-]+$/u"; // letras (con tildes y ñ), espacios, apóstrofo y guion

    if ($d['nombre'] !== '' && (mb_strlen($d['nombre']) > 50 || !preg_match($soloLetras, $d['nombre']))) {
        $errores[] = 'El nombre solo puede tener letras y hasta 50 caracteres.';
    }
    if ($d['apellido'] !== '' && (mb_strlen($d['apellido']) > 50 || !preg_match($soloLetras, $d['apellido']))) {
        $errores[] = 'El apellido solo puede tener letras y hasta 50 caracteres.';
    }
    if ($d['dni'] !== '' && !preg_match('/^\d{7,8}$/', $d['dni'])) {
        $errores[] = 'El DNI debe tener 7 u 8 números, sin puntos ni espacios.';
    }
    if ($d['email'] !== '' && (mb_strlen($d['email']) > 100 || !filter_var($d['email'], FILTER_VALIDATE_EMAIL))) {
        $errores[] = 'El correo electrónico no tiene un formato válido.';
    }
    if ($d['password'] !== '' && (strlen($d['password']) < 8 || strlen($d['password']) > 72)) {
        $errores[] = 'La contraseña debe tener entre 8 y 72 caracteres.';
    }
    if ($validarRol && !in_array($d['rol'], ['cliente', 'admin'], true)) {
        $errores[] = 'El rol seleccionado no es válido.';
    }

    // --- Duplicados (solo si el formato ya es correcto) ---
    // Si estamos editando, excluimos al propio usuario de la búsqueda.
    $id = $idExcluir ?? 0;

    if (preg_match('/^\d{7,8}$/', $d['dni'])) {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE dni = ? AND id <> ?');
        $stmt->execute([$d['dni'], $id]);
        if ($stmt->fetch()) {
            $errores[] = 'Ya existe un usuario registrado con ese DNI.';
        }
    }
    if (filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ?');
        $stmt->execute([$d['email'], $id]);
        if ($stmt->fetch()) {
            $errores[] = 'Ya existe un usuario registrado con ese correo electrónico.';
        }
    }

    return $errores;
}

/**
 * Inserta un usuario nuevo con la contraseña hasheada.
 * Devuelve true si se guardó, o un mensaje de error si el DNI/email
 * se repitió justo en ese momento (lo detecta la restricción UNIQUE).
 */
function insertarUsuario(PDO $pdo, array $d, string $rol)
{
    $hash = password_hash($d['password'], PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nombre, apellido, dni, email, password, rol) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$d['nombre'], $d['apellido'], $d['dni'], $d['email'], $hash, $rol]);
        return true;
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') { // violación de UNIQUE
            return 'El DNI o el correo electrónico ya están registrados.';
        }
        error_log($e->getMessage());
        return 'No se pudo guardar el usuario. Intentá de nuevo.';
    }
}

/* =====================================================================
   3. CATÁLOGO DE LA TIENDA
   Sets reales que se venden en la tienda oficial de LEGO (lego.com, Estados Unidos):
   nombre, número de set, precio en dólares, piezas y edad se tomaron de esa página.
   Las descripciones están escritas para este proyecto.
   La clave de cada producto es su número de set: se usa en la URL
   (producto.php?id=75192) y en el carrito.
   ===================================================================== */

$productos = [
    '75192' => [
        'nombre'      => 'Halcón Milenario',
        'linea'       => 'Star Wars',
        'precio'      => 849.99,
        'piezas'      => 7541,
        'edad'        => '16+',
        'foto'        => 'set-halcon-milenario.jpg',
        'descripcion' => 'La nave de Han Solo en su versión más grande, pensada para coleccionistas. '
                       . 'Mide más de 80 cm de largo, tiene el interior armado con detalle y trae dos '
                       . 'juegos de antenas para armarla como en las películas clásicas o como en la nueva trilogía. '
                       . 'Incluye minifiguras de la tripulación.',
    ],
    '10300' => [
        'nombre'      => 'Máquina del tiempo de Volver al Futuro',
        'linea'       => 'LEGO Icons',
        'precio'      => 199.99,
        'piezas'      => 1872,
        'edad'        => '18+',
        'foto'        => 'set-delorean.jpg',
        'descripcion' => 'El DeLorean de la trilogía Volver al Futuro. Se puede armar en tres versiones '
                       . 'distintas, una por cada película, y viene con las minifiguras de Marty McFly y del Doc Brown.',
    ],
    '92176' => [
        'nombre'      => 'NASA Apollo Saturn V',
        'linea'       => 'LEGO Ideas',
        'precio'      => 95.99,
        'piezas'      => 1969,
        'edad'        => '14+',
        'foto'        => 'set-saturn-v.jpg',
        'descripcion' => 'Réplica del cohete que llevó a los astronautas del programa Apolo a la Luna, '
                       . 'a escala 1:110 y de casi un metro de alto. Se separa en sus tres etapas e incluye '
                       . 'el módulo lunar y tres astronautas en miniatura. Trae soportes para exhibirlo acostado.',
    ],
    '10294' => [
        'nombre'      => 'Titanic',
        'linea'       => 'LEGO Icons',
        'precio'      => 679.99,
        'piezas'      => 9090,
        'edad'        => '18+',
        'foto'        => 'set-titanic.jpg',
        'descripcion' => 'El transatlántico más famoso en una maqueta de más de un metro de largo. '
                       . 'Se arma en tres secciones que, al separarse, dejan ver por dentro los camarotes, '
                       . 'los salones y la sala de máquinas.',
    ],
    '10698' => [
        'nombre'      => 'Caja grande de ladrillos creativos',
        'linea'       => 'LEGO Classic',
        'precio'      => 59.99,
        'piezas'      => 790,
        'edad'        => '4+',
        'foto'        => 'set-ladrillos-clasicos.jpg',
        'descripcion' => 'Ladrillos en 33 colores, con ventanas, puertas, ojos y ruedas para armar lo que '
                       . 'se te ocurra. Viene en una caja con tapa que sirve para guardar todas las piezas.',
    ],
    '10914' => [
        'nombre'      => 'Caja de ladrillos Deluxe',
        'linea'       => 'LEGO DUPLO',
        'precio'      => 49.99,
        'piezas'      => 85,
        'edad'        => '1½+',
        'foto'        => 'set-duplo.jpg',
        'descripcion' => 'Ladrillos grandes y fáciles de agarrar, en colores brillantes, para que los más '
                       . 'chicos empiecen a armar sus primeras construcciones. Todo se guarda en la misma caja.',
    ],
];

/** Formatea un precio en dólares: 849.99 -> "US$ 849,99" */
function precio(float $valor): string
{
    return 'US$ ' . number_format($valor, 2, ',', '.');
}

/** Devuelve la cantidad total de unidades que hay en el carrito (para el menú). */
function unidadesEnCarrito(): int
{
    return array_sum($_SESSION['carrito'] ?? []);
}

// Encabezado y pie de las páginas
require_once __DIR__ . '/plantilla.php';
