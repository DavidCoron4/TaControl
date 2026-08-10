<?php
/**
 * config.php
 * Conexión central a la base de datos "tacontrol" (MySQL/MariaDB vía phpMyAdmin/XAMPP).
 * Todos los archivos dentro de /api/ incluyen este archivo primero.
 */

// --- Datos de conexión (coinciden con tu phpMyAdmin: root / 127.0.0.1 / sin contraseña) ---
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tacontrol');

// --- Cabeceras: todo responde en JSON y se permite llamar desde el HTML ---
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Peticiones OPTIONS (preflight de CORS) se responden vacías
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Conexión ---
$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conexion->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo conectar a la base de datos: ' . $conexion->connect_error]);
    exit;
}

$conexion->set_charset('utf8mb4');

/**
 * Lee el cuerpo JSON de la petición (para POST/PUT) y lo regresa como array asociativo.
 */
function leerCuerpoJSON() {
    $datos = json_decode(file_get_contents('php://input'), true);
    return is_array($datos) ? $datos : [];
}

/**
 * Responde JSON y termina la ejecución.
 */
function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}
