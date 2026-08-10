<?php
require_once __DIR__ . '/../config.php';

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'GET':
        $sql = "SELECT cc.id, cc.usuario_id, u.nombre AS usuario, cc.fecha_cierre,
                       cc.fondo_inicial, cc.ingreso_esperado, cc.ingreso_real, cc.diferencia
                FROM cortes_caja cc JOIN usuarios u ON u.id = cc.usuario_id
                ORDER BY cc.fecha_cierre DESC";
        $res = $conexion->query($sql);
        responder($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        // Body: { usuario_id, fondo_inicial, ingreso_esperado, ingreso_real }
        // diferencia se calcula automáticamente
        $d = leerCuerpoJSON();
        foreach (['usuario_id', 'fondo_inicial', 'ingreso_esperado', 'ingreso_real'] as $campo) {
            if (!isset($d[$campo])) responder(['error' => "Falta $campo"], 400);
        }
        $diferencia = $d['ingreso_real'] - $d['ingreso_esperado'];

        $stmt = $conexion->prepare(
            "INSERT INTO cortes_caja (usuario_id, fondo_inicial, ingreso_esperado, ingreso_real, diferencia)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('idddd', $d['usuario_id'], $d['fondo_inicial'], $d['ingreso_esperado'], $d['ingreso_real'], $diferencia);
        if ($stmt->execute()) {
            responder(['id' => $conexion->insert_id, 'diferencia' => $diferencia], 201);
        } else {
            responder(['error' => $stmt->error], 400);
        }
        break;

    default:
        responder(['error' => 'Método no soportado'], 405);
}
