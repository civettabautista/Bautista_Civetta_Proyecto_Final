<?php
/*
 * reactivar_usuario.php
 * Reactiva un usuario archivado: vuelve a poner fecha_archivado en NULL.
 * El usuario reaparece en el listado de activos y puede iniciar sesión.
 */
require_once 'includes/funciones.php';

requerirAdmin();                              // 1) solo administradores
requerirPost('usuarios_archivados.php');      // 2) solo por POST
verificarCsrf('usuarios_archivados.php');     // 3) token CSRF válido

$id = validarId($_POST['id'] ?? null);        // 4) ID entero válido

if ($id === null) {
    setFlash('error', 'El usuario indicado no es válido.');
    redirigir('usuarios_archivados.php');
}

// 5) Reactivamos solo si existe y está archivado
$stmt = $pdo->prepare('UPDATE usuarios SET fecha_archivado = NULL WHERE id = ? AND fecha_archivado IS NOT NULL');
$stmt->execute([$id]);

if ($stmt->rowCount() === 1) {
    setFlash('exito', 'El usuario fue reactivado y ya puede iniciar sesión.');
} else {
    setFlash('error', 'El usuario no existe o ya estaba activo.');
}

redirigir('usuarios_archivados.php');
