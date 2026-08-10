<?php
require_once __DIR__ . '/../config.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['action'] ?? null;

switch ($metodo) {

    case 'GET':
        // Lista de personal (nunca se regresa la contraseña)
        $res = $conexion->query("SELECT id, nombre, puesto, usuario FROM usuarios ORDER BY nombre");
        responder($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        $d = leerCuerpoJSON();

        // --- LOGIN ---
        if ($accion === 'login') {
            if (empty($d['usuario']) || empty($d['contrasena'])) {
                responder(['error' => 'Faltan usuario o contraseña'], 400);
            }
            $stmt = $conexion->prepare("SELECT id, nombre, puesto, usuario, contrasena FROM usuarios WHERE usuario = ?");
            $stmt->bind_param('s', $d['usuario']);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();

            if ($fila && password_verify($d['contrasena'], $fila['contrasena'])) {
                unset($fila['contrasena']);
                responder(['ok' => true, 'usuario' => $fila]);
            } else {
                responder(['ok' => false, 'error' => 'Usuario o contraseña incorrectos'], 401);
            }
        }

        // --- CREAR NUEVO EMPLEADO ---
        if (empty($d['nombre']) || empty($d['puesto']) || empty($d['usuario']) || empty($d['contrasena'])) {
            responder(['error' => 'Faltan datos (nombre, puesto, usuario, contrasena)'], 400);
        }
        $hash = password_hash($d['contrasena'], PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, puesto, usuario, contrasena) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $d['nombre'], $d['puesto'], $d['usuario'], $hash);
        if ($stmt->execute()) {
            responder(['id' => $conexion->insert_id], 201);
        } else {
            responder(['error' => $stmt->error], 400);
        }
        break;

    case 'PUT':
        $d = leerCuerpoJSON();
        if (empty($d['id'])) responder(['error' => 'Falta id'], 400);

        if (!empty($d['contrasena'])) {
            $hash = password_hash($d['contrasena'], PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE usuarios SET nombre=?, puesto=?, usuario=?, contrasena=? WHERE id=?");
            $stmt->bind_param('ssssi', $d['nombre'], $d['puesto'], $d['usuario'], $hash, $d['id']);
        } else {
            $stmt = $conexion->prepare("UPDATE usuarios SET nombre=?, puesto=?, usuario=? WHERE id=?");
            $stmt->bind_param('sssi', $d['nombre'], $d['puesto'], $d['usuario'], $d['id']);
        }
        $stmt->execute();
        responder(['ok' => true]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) responder(['error' => 'Falta id'], 400);

        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        responder(['ok' => true]);
        break;

    default:
        responder(['error' => 'Método no soportado'], 405);
}
