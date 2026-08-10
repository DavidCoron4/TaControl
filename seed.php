<?php
/**
 * seed.php
 * EJECUTAR UNA SOLA VEZ desde el navegador: http://localhost/tacontrol_backend/seed.php
 * Llena las 8 tablas con los datos que ya traía el HTML original (catálogo de tacos,
 * inventario, recetas y personal), usando password_hash() real de PHP.
 *
 * Después de correrlo, bórralo o renómbralo para que nadie lo vuelva a ejecutar
 * (volvería a duplicar los datos).
 */

require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); // texto plano para leer el log fácil

$conexion->begin_transaction();
try {
    // ---------- 1. CATEGORIAS ----------
    $categorias = ['tacos', 'tortas', 'quesadillas', 'bebidas'];
    $catIds = [];
    $stmt = $conexion->prepare("INSERT INTO categorias (nombre) VALUES (?)");
    foreach ($categorias as $cat) {
        $stmt->bind_param('s', $cat);
        $stmt->execute();
        $catIds[$cat] = $conexion->insert_id;
    }
    echo "Categorías creadas: " . implode(', ', $categorias) . "\n";

    // ---------- 2. INSUMOS ----------
    $insumosData = [
        'queso'     => ['nombre' => 'Queso',                    'stock' => 20.0,  'unidad' => 'Kg',   'critico' => 5,   'advertencia' => 10],
        'bistec'    => ['nombre' => 'Bistec',                   'stock' => 15.0,  'unidad' => 'Kg',   'critico' => 3,   'advertencia' => 7],
        'arrachera' => ['nombre' => 'Arrachera',                'stock' => 10.0,  'unidad' => 'Kg',   'critico' => 2,   'advertencia' => 5],
        'pastor'    => ['nombre' => 'Pastor',                   'stock' => 12.0,  'unidad' => 'Kg',   'critico' => 3,   'advertencia' => 6],
        'costilla'  => ['nombre' => 'Costilla',                 'stock' => 8.0,   'unidad' => 'Kg',   'critico' => 2,   'advertencia' => 4],
        'cabeza'    => ['nombre' => 'Cabeza',                   'stock' => 6.0,   'unidad' => 'Kg',   'critico' => 1,   'advertencia' => 3],
        'buche'     => ['nombre' => 'Buche',                    'stock' => 5.0,   'unidad' => 'Kg',   'critico' => 1,   'advertencia' => 2],
        'suadero'   => ['nombre' => 'Suadero',                  'stock' => 8.0,   'unidad' => 'Kg',   'critico' => 2,   'advertencia' => 4],
        'tortillas' => ['nombre' => 'Tortillas (Maíz/Harina)',  'stock' => 300,   'unidad' => 'Pzas', 'critico' => 150, 'advertencia' => 250],
        'bebidas'   => ['nombre' => 'Bebidas / Refrescos',      'stock' => 48,    'unidad' => 'Pzas', 'critico' => 12,  'advertencia' => 24],
        'bolillo'   => ['nombre' => 'Bolillos',                 'stock' => 120,   'unidad' => 'Pzas', 'critico' => 20,  'advertencia' => 50],
    ];
    $insumoIds = [];
    $stmt = $conexion->prepare(
        "INSERT INTO insumos (nombre, stock_actual, unidad_medida, limite_critico, limite_advertencia) VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($insumosData as $clave => $ins) {
        $stmt->bind_param('sdsdd', $ins['nombre'], $ins['stock'], $ins['unidad'], $ins['critico'], $ins['advertencia']);
        $stmt->execute();
        $insumoIds[$clave] = $conexion->insert_id;
    }
    echo "Insumos creados: " . count($insumoIds) . "\n";

    // ---------- 3. PRODUCTOS + RECETAS ----------
    $carnes = ['Pastor', 'Costilla', 'Cabeza', 'Buche', 'Suadero', 'Bistec', 'Arrachera'];
    $bebidasNombres = ['Agua Jamaica', 'Agua Horchata', 'Coca-Cola', 'Sidral Mundet'];

    $stmtProd = $conexion->prepare(
        "INSERT INTO productos (categoria_id, nombre, precio, icono) VALUES (?, ?, ?, ?)"
    );
    $stmtReceta = $conexion->prepare(
        "INSERT INTO recetas (producto_id, insumo_id, cantidad_consumo) VALUES (?, ?, ?)"
    );

    $totalProductos = 0;

    // --- Tacos: 0.08 Kg de carne + 2 tortillas ---
    foreach ($carnes as $carne) {
        $nombre = "Taco de $carne";
        $precio = ($carne === 'Arrachera') ? 20 : 17;
        $icono = 'fa-fire-burner';
        $stmtProd->bind_param('isds', $catIds['tacos'], $nombre, $precio, $icono);
        $stmtProd->execute();
        $pid = $conexion->insert_id;
        $totalProductos++;

        $claveCarne = strtolower($carne);
        $cantCarne = 0.08; $cantTortillas = 2;
        $stmtReceta->bind_param('iid', $pid, $insumoIds[$claveCarne], $cantCarne); $stmtReceta->execute();
        $stmtReceta->bind_param('iid', $pid, $insumoIds['tortillas'], $cantTortillas); $stmtReceta->execute();
    }

    // --- Tortas sin queso: 0.18 Kg de carne + 1 bolillo ---
    foreach ($carnes as $carne) {
        $nombre = "Torta s/Queso ($carne)";
        $precio = 45; $icono = 'fa-bread-slice';
        $stmtProd->bind_param('isds', $catIds['tortas'], $nombre, $precio, $icono);
        $stmtProd->execute();
        $pid = $conexion->insert_id;
        $totalProductos++;

        $claveCarne = strtolower($carne);
        $cantCarne = 0.18; $cantBolillo = 1;
        $stmtReceta->bind_param('iid', $pid, $insumoIds[$claveCarne], $cantCarne); $stmtReceta->execute();
        $stmtReceta->bind_param('iid', $pid, $insumoIds['bolillo'], $cantBolillo); $stmtReceta->execute();
    }

    // --- Tortas con queso: 0.18 Kg de carne + 1 bolillo + queso ---
    foreach ($carnes as $carne) {
        $nombre = "Torta c/Queso ($carne)";
        $precio = 55; $icono = 'fa-cheese';
        $stmtProd->bind_param('isds', $catIds['tortas'], $nombre, $precio, $icono);
        $stmtProd->execute();
        $pid = $conexion->insert_id;
        $totalProductos++;

        $claveCarne = strtolower($carne);
        $cantCarne = 0.18; $cantBolillo = 1; $cantQueso = 0.05;
        $stmtReceta->bind_param('iid', $pid, $insumoIds[$claveCarne], $cantCarne); $stmtReceta->execute();
        $stmtReceta->bind_param('iid', $pid, $insumoIds['bolillo'], $cantBolillo); $stmtReceta->execute();
        $stmtReceta->bind_param('iid', $pid, $insumoIds['queso'], $cantQueso); $stmtReceta->execute();
    }

    // --- Quesadillas: 0.12 Kg de carne + 1 tortilla + queso ---
    foreach ($carnes as $carne) {
        $nombre = "Quesadilla de $carne";
        $precio = 35; $icono = 'fa-folder';
        $stmtProd->bind_param('isds', $catIds['quesadillas'], $nombre, $precio, $icono);
        $stmtProd->execute();
        $pid = $conexion->insert_id;
        $totalProductos++;

        $claveCarne = strtolower($carne);
        $cantCarne = 0.12; $cantTortilla = 1; $cantQueso = 0.04;
        $stmtReceta->bind_param('iid', $pid, $insumoIds[$claveCarne], $cantCarne); $stmtReceta->execute();
        $stmtReceta->bind_param('iid', $pid, $insumoIds['tortillas'], $cantTortilla); $stmtReceta->execute();
        $stmtReceta->bind_param('iid', $pid, $insumoIds['queso'], $cantQueso); $stmtReceta->execute();
    }

    // --- Bebidas: 1 pza de bebida ---
    foreach ($bebidasNombres as $bebida) {
        $precio = 20; $icono = 'fa-bottle-water';
        $stmtProd->bind_param('isds', $catIds['bebidas'], $bebida, $precio, $icono);
        $stmtProd->execute();
        $pid = $conexion->insert_id;
        $totalProductos++;

        $cantBebida = 1;
        $stmtReceta->bind_param('iid', $pid, $insumoIds['bebidas'], $cantBebida); $stmtReceta->execute();
    }

    echo "Productos creados: $totalProductos\n";

    // ---------- 4. USUARIOS (personal) con contraseñas hasheadas ----------
    $personal = [
        ['nombre' => 'Luis Cuevas', 'puesto' => 'Administrador',              'usuario' => 'AdminTaquero', 'pass' => '123'],
        ['nombre' => 'Memo',        'puesto' => 'Cajero / Administración',    'usuario' => 'memin',        'pass' => 'memin71'],
        ['nombre' => 'David',       'puesto' => 'Taquero Principal',          'usuario' => 'david',        'pass' => 'negratomasa'],
    ];
    $stmtUser = $conexion->prepare(
        "INSERT INTO usuarios (nombre, puesto, usuario, contrasena) VALUES (?, ?, ?, ?)"
    );
    foreach ($personal as $p) {
        $hash = password_hash($p['pass'], PASSWORD_DEFAULT);
        $stmtUser->bind_param('ssss', $p['nombre'], $p['puesto'], $p['usuario'], $hash);
        $stmtUser->execute();
    }
    echo "Personal creado: " . count($personal) . " usuarios\n";

    $conexion->commit();
    echo "\n✅ LISTO. La base 'tacontrol' quedó poblada con los datos originales.\n";
    echo "⚠️  IMPORTANTE: borra o renombra este archivo (seed.php) ahora para que nadie lo vuelva a correr.\n";

} catch (Exception $e) {
    $conexion->rollback();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "No se guardó nada (se revirtió todo).\n";
}
