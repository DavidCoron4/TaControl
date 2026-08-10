<?php
require_once __DIR__ . '/../config.php';

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'GET':
        $res = $conexion->query("SELECT id, nombre FROM categorias ORDER BY nombre");
        responder($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        $datos = leerCuerpoJSON();
        if (empty($datos['nombre'])) responder(['error' => 'Falta el nombre'], 400);

        $stmt = $conexion->prepare("INSERT INTO categorias (nombre) VALUES (?)");
        $stmt->bind_param('s', $datos['nombre']);
        if ($stmt->execute()) {
            responder(['id' => $conexion->insert_id, 'nombre' => $datos['nombre']], 201);
        } else {
            responder(['error' => $stmt->error], 400);
        }
        break;

    case 'PUT':
        $datos = leerCuerpoJSON();
        if (empty($datos['id']) || empty($datos['nombre'])) responder(['error' => 'Falta id o nombre'], 400);

        $stmt = $conexion->prepare("UPDATE categorias SET nombre = ? WHERE id = ?");
        $stmt->bind_param('si', $datos['nombre'], $datos['id']);
        $stmt->execute();
        responder(['ok' => true]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) responder(['error' => 'Falta id'], 400);

        $stmt = $conexion->prepare("DELETE FROM categorias WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        responder(['ok' => true]);
        break;

    default:
        responder(['error' => 'Método no soportado'], 405);
}
