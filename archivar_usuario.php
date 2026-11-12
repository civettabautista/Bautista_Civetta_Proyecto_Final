<?php
/*
 * archivar_usuario.php
 * Archiva un usuario: guarda la fecha actual en fecha_archivado.
 * NO borra el registro (no se usa DELETE). No muestra pantalla:
 * procesa la acción y vuelve al listado con un mensaje.
 */
require_once 'includes/funciones.php';

requerirAdmin();                         // 1) solo administradores
requerirPost('admin_usuarios.php');      // 2) solo por POST (no por un enlace)
verificarCsrf('admin_usuarios.php');     // 3) el formulario salió de nuestro sistema

$id = validarId($_POST['id'] ?? null);   // 4) el ID es un entero válido

if ($id === null) {
    setFlash('error', 'El usuario indicado no es válido.');
    redirigir('admin_usuarios.php');
}

// 5) Un admin no puede archivarse a sí mismo
if ($id === (int) $_SESSION['usuario_id']) {
    setFlash('error', 'No podés archivar tu propia cuenta.');
    redirigir('admin_usuarios.php');
}

// 6) Archivamos solo si existe y todavía está activo
$stmt = $pdo->prepare('UPDATE usuarios SET fecha_archivado = NOW() WHERE id = ? AND fecha_archivado IS NULL');
$stmt->execute([$id]);

if ($stmt->rowCount() === 1) {
    setFlash('exito', 'El usuario fue archivado. Podés reactivarlo desde "Archivados".');
} else {
    setFlash('error', 'El usuario no existe o ya estaba archivado.');
}

redirigir('admin_usuarios.php');
