<?php
require_once __DIR__ . '/../config.php';

// Este endpoint es de solo lectura: el alta/baja de detalles se maneja
// siempre junto con su comanda en api/comandas.php (POST crea comanda + detalles,
// DELETE de la comanda borra sus detalles en cascada).

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responder(['error' => 'Usa api/comandas.php para crear/eliminar. Este endpoint solo permite GET.'], 405);
}

// ?comanda_id=5 (requerido)
if (empty($_GET['comanda_id'])) {
    responder(['error' => 'Falta comanda_id'], 400);
}

$stmt = $conexion->prepare(
    "SELECT cd.id, cd.comanda_id, cd.producto_id, p.nombre AS producto, cd.cantidad, cd.precio_unitario,
            (cd.cantidad * cd.precio_unitario) AS subtotal
     FROM comanda_detalles cd
     JOIN productos p ON p.id = cd.producto_id
     WHERE cd.comanda_id = ?"
);
$stmt->bind_param('i', $_GET['comanda_id']);
$stmt->execute();
responder($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
