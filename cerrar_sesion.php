<?php
/*
 * cerrar_sesion.php
 * Cierra la sesión del usuario y lo devuelve al login.
 */
require_once 'includes/funciones.php';

// 1) Vaciamos todas las variables de sesión
$_SESSION = [];

// 2) Eliminamos la cookie de sesión del navegador
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

// 3) Destruimos la sesión en el servidor
session_destroy();

// 4) Iniciamos una sesión nueva y vacía (con un ID nuevo) solo para mostrar el mensaje de despedida
session_start();
session_regenerate_id(true);
setFlash('exito', 'Cerraste sesión correctamente. ¡Volvé pronto!');
redirigir('login.php');
