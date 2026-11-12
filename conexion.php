<?php
/*
 * conexion.php
 * Crea la conexión con la base de datos MySQL usando PDO.
 * Todos los archivos que necesitan la base de datos incluyen este archivo
 * y usan la variable $pdo.
 */

// Datos de conexión (los valores por defecto de XAMPP y Laragon)
$host    = 'localhost';
$base    = 'tienda_legos';
$usuario = 'root';
$clave   = '';           // En XAMPP y Laragon el usuario root no tiene contraseña
$charset = 'utf8mb4';    // Soporta tildes, eñes y cualquier carácter

// DSN: "dirección" de la base de datos para PDO
$dsn = "mysql:host=$host;dbname=$base;charset=$charset";

// Opciones de PDO
$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Los errores lanzan excepciones
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Los resultados vienen como arrays asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Consultas preparadas reales del servidor MySQL
];

try {
    $pdo = new PDO($dsn, $usuario, $clave, $opciones);
} catch (PDOException $e) {
    // Guardamos el detalle técnico en el log del servidor, pero al usuario
    // solo le mostramos un mensaje genérico (no exponemos datos internos).
    error_log('Error de conexión: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Verificá que MySQL esté encendido e intentá de nuevo.');
}
