<?php
require_once __DIR__ . '/../config.php';

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'GET':
        // ?categoria=tacos  -> filtra por nombre de categoria
        // ?categoria_id=1   -> filtra por id de categoria
        $sql = "SELECT p.id, p.categoria_id, c.nombre AS categoria, p.nombre, p.precio, p.icono
                FROM productos p
                JOIN categorias c ON c.id = p.categoria_id";
        $condiciones = [];
        $tipos = '';
        $valores = [];

        if (!empty($_GET['categoria'])) {
            $condiciones[] = "c.nombre = ?";
            $tipos .= 's';
            $valores[] = $_GET['categoria'];
        }
        if (!empty($_GET['categoria_id'])) {
            $condiciones[] = "p.categoria_id = ?";
            $tipos .= 'i';
            $valores[] = $_GET['categoria_id'];
        }
        if ($condiciones) {
            $sql .= " WHERE " . implode(' AND ', $condiciones);
        }
        $sql .= " ORDER BY p.nombre";

        $stmt = $conexion->prepare($sql);
        if ($valores) $stmt->bind_param($tipos, ...$valores);
        $stmt->execute();
        responder($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        $d = leerCuerpoJSON();
        if (empty($d['categoria_id']) || empty($d['nombre']) || !isset($d['precio'])) {
            responder(['error' => 'Faltan datos (categoria_id, nombre, precio)'], 400);
        }
        $icono = $d['icono'] ?? null;
        $stmt = $conexion->prepare("INSERT INTO productos (categoria_id, nombre, precio, icono) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('isds', $d['categoria_id'], $d['nombre'], $d['precio'], $icono);
        if ($stmt->execute()) {
            responder(['id' => $conexion->insert_id], 201);
        } else {
            responder(['error' => $stmt->error], 400);
        }
        break;

    case 'PUT':
        $d = leerCuerpoJSON();
        if (empty($d['id'])) responder(['error' => 'Falta id'], 400);

        $stmt = $conexion->prepare("UPDATE productos SET categoria_id=?, nombre=?, precio=?, icono=? WHERE id=?");
        $stmt->bind_param('isdsi', $d['categoria_id'], $d['nombre'], $d['precio'], $d['icono'], $d['id']);
        $stmt->execute();
        responder(['ok' => true]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) responder(['error' => 'Falta id'], 400);

        $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        responder(['ok' => true]);
        break;

    default:
        responder(['error' => 'Método no soportado'], 405);
}
