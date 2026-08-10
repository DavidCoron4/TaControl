<?php
require_once __DIR__ . '/../config.php';

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'GET':
        // ?producto_id=5 -> receta de un producto específico (qué insumos consume y cuánto)
        $sql = "SELECT r.id, r.producto_id, p.nombre AS producto, r.insumo_id, i.nombre AS insumo,
                       i.unidad_medida, r.cantidad_consumo
                FROM recetas r
                JOIN productos p ON p.id = r.producto_id
                JOIN insumos i ON i.id = r.insumo_id";
        if (!empty($_GET['producto_id'])) {
            $sql .= " WHERE r.producto_id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param('i', $_GET['producto_id']);
            $stmt->execute();
            responder($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        }
        $res = $conexion->query($sql);
        responder($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        $d = leerCuerpoJSON();
        if (empty($d['producto_id']) || empty($d['insumo_id']) || !isset($d['cantidad_consumo'])) {
            responder(['error' => 'Faltan datos (producto_id, insumo_id, cantidad_consumo)'], 400);
        }
        $stmt = $conexion->prepare(
            "INSERT INTO recetas (producto_id, insumo_id, cantidad_consumo) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('iid', $d['producto_id'], $d['insumo_id'], $d['cantidad_consumo']);
        if ($stmt->execute()) {
            responder(['id' => $conexion->insert_id], 201);
        } else {
            responder(['error' => $stmt->error], 400);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) responder(['error' => 'Falta id'], 400);

        $stmt = $conexion->prepare("DELETE FROM recetas WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        responder(['ok' => true]);
        break;

    default:
        responder(['error' => 'Método no soportado'], 405);
}
