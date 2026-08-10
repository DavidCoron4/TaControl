<?php
require_once __DIR__ . '/../config.php';

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'GET':
        $res = $conexion->query(
            "SELECT id, nombre, stock_actual, unidad_medida, limite_critico, limite_advertencia
             FROM insumos ORDER BY nombre"
        );
        responder($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        $d = leerCuerpoJSON();
        if (empty($d['nombre']) || !isset($d['stock_actual']) || empty($d['unidad_medida'])) {
            responder(['error' => 'Faltan datos'], 400);
        }
        $stmt = $conexion->prepare(
            "INSERT INTO insumos (nombre, stock_actual, unidad_medida, limite_critico, limite_advertencia)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sdsdd', $d['nombre'], $d['stock_actual'], $d['unidad_medida'], $d['limite_critico'], $d['limite_advertencia']);
        if ($stmt->execute()) {
            responder(['id' => $conexion->insert_id], 201);
        } else {
            responder(['error' => $stmt->error], 400);
        }
        break;

    case 'PUT':
        $d = leerCuerpoJSON();
        if (empty($d['id'])) responder(['error' => 'Falta id'], 400);

        // Acción especial: ajustar stock sumando/restando una cantidad (delta)
        // Body: { "id": 3, "delta": -0.5 }
        if (isset($d['delta'])) {
            $stmt = $conexion->prepare("UPDATE insumos SET stock_actual = stock_actual + ? WHERE id = ?");
            $stmt->bind_param('di', $d['delta'], $d['id']);
            $stmt->execute();
            responder(['ok' => true]);
        }

        // Actualización completa del registro
        $stmt = $conexion->prepare(
            "UPDATE insumos SET nombre=?, stock_actual=?, unidad_medida=?, limite_critico=?, limite_advertencia=? WHERE id=?"
        );
        $stmt->bind_param('sdsddi', $d['nombre'], $d['stock_actual'], $d['unidad_medida'], $d['limite_critico'], $d['limite_advertencia'], $d['id']);
        $stmt->execute();
        responder(['ok' => true]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) responder(['error' => 'Falta id'], 400);

        $stmt = $conexion->prepare("DELETE FROM insumos WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        responder(['ok' => true]);
        break;

    default:
        responder(['error' => 'Método no soportado'], 405);
}
