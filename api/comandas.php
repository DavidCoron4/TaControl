<?php
require_once __DIR__ . '/../config.php';

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'GET':
        // ?estado=Pendiente | Cobrada | Cancelada  (opcional)
        // ?id=5 -> trae una sola comanda con sus detalles
        if (!empty($_GET['id'])) {
            $stmt = $conexion->prepare(
                "SELECT c.*, u.nombre AS usuario FROM comandas c JOIN usuarios u ON u.id = c.usuario_id WHERE c.id = ?"
            );
            $stmt->bind_param('i', $_GET['id']);
            $stmt->execute();
            $comanda = $stmt->get_result()->fetch_assoc();
            if (!$comanda) responder(['error' => 'No encontrada'], 404);

            $stmt2 = $conexion->prepare(
                "SELECT cd.id, cd.producto_id, p.nombre, cd.cantidad, cd.precio_unitario
                 FROM comanda_detalles cd JOIN productos p ON p.id = cd.producto_id
                 WHERE cd.comanda_id = ?"
            );
            $stmt2->bind_param('i', $_GET['id']);
            $stmt2->execute();
            $comanda['detalles'] = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
            responder($comanda);
        }

        $sql = "SELECT c.id, c.usuario_id, u.nombre AS usuario, c.fecha_creacion, c.fecha_pago, c.total, c.estado
                FROM comandas c JOIN usuarios u ON u.id = c.usuario_id";
        if (!empty($_GET['estado'])) {
            $stmt = $conexion->prepare($sql . " WHERE c.estado = ? ORDER BY c.fecha_creacion DESC");
            $stmt->bind_param('s', $_GET['estado']);
            $stmt->execute();
            responder($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        }
        $res = $conexion->query($sql . " ORDER BY c.fecha_creacion DESC");
        responder($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        // Body: { usuario_id, estado: "Pendiente", items: [{producto_id, cantidad, precio_unitario}, ...] }
        $d = leerCuerpoJSON();
        if (empty($d['usuario_id']) || empty($d['items']) || !is_array($d['items'])) {
            responder(['error' => 'Faltan datos (usuario_id, items)'], 400);
        }
        $estado = $d['estado'] ?? 'Pendiente';
        $total = 0;
        foreach ($d['items'] as $it) {
            $total += $it['cantidad'] * $it['precio_unitario'];
        }

        $conexion->begin_transaction();
        try {
            $stmt = $conexion->prepare("INSERT INTO comandas (usuario_id, total, estado) VALUES (?, ?, ?)");
            $stmt->bind_param('ids', $d['usuario_id'], $total, $estado);
            $stmt->execute();
            $comandaId = $conexion->insert_id;

            $stmtDet = $conexion->prepare(
                "INSERT INTO comanda_detalles (comanda_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)"
            );
            foreach ($d['items'] as $it) {
                $stmtDet->bind_param('iiid', $comandaId, $it['producto_id'], $it['cantidad'], $it['precio_unitario']);
                $stmtDet->execute();
            }

            $conexion->commit();
            responder(['id' => $comandaId, 'total' => $total], 201);
        } catch (Exception $e) {
            $conexion->rollback();
            responder(['error' => $e->getMessage()], 400);
        }
        break;

    case 'PUT':
        // Body: { id, estado: "Cobrada" | "Cancelada" }
        // Al pasar a "Cobrada" se descuenta automáticamente el inventario según las recetas.
        $d = leerCuerpoJSON();
        if (empty($d['id']) || empty($d['estado'])) responder(['error' => 'Falta id o estado'], 400);

        $conexion->begin_transaction();
        try {
            if ($d['estado'] === 'Cobrada') {
                $stmt = $conexion->prepare(
                    "UPDATE comandas SET estado = 'Cobrada', fecha_pago = NOW() WHERE id = ?"
                );
                $stmt->bind_param('i', $d['id']);
                $stmt->execute();

                // Descontar inventario: por cada producto vendido, restar según su receta
                $stmtItems = $conexion->prepare(
                    "SELECT producto_id, cantidad FROM comanda_detalles WHERE comanda_id = ?"
                );
                $stmtItems->bind_param('i', $d['id']);
                $stmtItems->execute();
                $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

                $stmtReceta = $conexion->prepare(
                    "SELECT insumo_id, cantidad_consumo FROM recetas WHERE producto_id = ?"
                );
                $stmtDescontar = $conexion->prepare(
                    "UPDATE insumos SET stock_actual = stock_actual - ? WHERE id = ?"
                );

                foreach ($items as $item) {
                    $stmtReceta->bind_param('i', $item['producto_id']);
                    $stmtReceta->execute();
                    $ingredientes = $stmtReceta->get_result()->fetch_all(MYSQLI_ASSOC);
                    foreach ($ingredientes as $ing) {
                        $consumo = $ing['cantidad_consumo'] * $item['cantidad'];
                        $stmtDescontar->bind_param('di', $consumo, $ing['insumo_id']);
                        $stmtDescontar->execute();
                    }
                }
            } else {
                $stmt = $conexion->prepare("UPDATE comandas SET estado = ? WHERE id = ?");
                $stmt->bind_param('si', $d['estado'], $d['id']);
                $stmt->execute();
            }

            $conexion->commit();
            responder(['ok' => true]);
        } catch (Exception $e) {
            $conexion->rollback();
            responder(['error' => $e->getMessage()], 400);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) responder(['error' => 'Falta id'], 400);

        // comanda_detalles se borra en cascada por la FK ON DELETE CASCADE
        $stmt = $conexion->prepare("DELETE FROM comandas WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        responder(['ok' => true]);
        break;

    default:
        responder(['error' => 'Método no soportado'], 405);
}
