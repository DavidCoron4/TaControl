# TaControl — Conexión a MySQL/phpMyAdmin

## Qué contiene esta entrega

```
htdocs/tacontrol/               <- crea esta carpeta dentro de tu servidor (XAMPP: C:\xampp\htdocs\tacontrol)
├── Tacontrol_conectado.html    <- tu app, ya conectada a la API (ábrela con http://, no con doble clic)
├── config.php                  <- conexión a MySQL (usuario root, sin contraseña, 127.0.0.1)
├── seed.php                    <- EJECUTAR UNA VEZ para llenar la BD con el catálogo original
└── api/
    ├── categorias.php
    ├── productos.php
    ├── insumos.php
    ├── recetas.php
    ├── usuarios.php
    ├── comandas.php
    ├── comanda_detalles.php
    └── cortes_caja.php
```

## Pasos de instalación (XAMPP / WAMP)

1. **Copia la carpeta completa** `tacontrol/` (con `config.php`, `seed.php`, `api/` y el `.html`)
   dentro de tu carpeta de servidor, por ejemplo:
   `C:\xampp\htdocs\tacontrol\`

2. **Verifica que Apache y MySQL estén corriendo** en el Panel de Control de XAMPP (ambos en verde).

3. **Importa la estructura** (si aún no está creada): en phpMyAdmin → base `tacontrol` → pestaña
   *Importar* → selecciona tu archivo `tacontrol.sql` (el que ya tenías). Si las 8 tablas ya
   existen, sáltate este paso.

4. **Llena los datos iniciales — solo una vez:** abre en el navegador
   `http://localhost/tacontrol/seed.php`
   Verás un log de texto confirmando cuántas categorías, productos, insumos y usuarios se crearon.
   ⚠️ Si lo corres dos veces, duplicará todo. Después de usarlo, bórralo o renómbralo
   (ej. `seed.php.bak`).

5. **Abre la app real:**
   `http://localhost/tacontrol/Tacontrol_conectado.html`
   (No la abras con doble clic desde el explorador de archivos — `file://` no puede hacer
   peticiones a la API. Tiene que ser por `http://localhost/...`)

6. **Inicia sesión** con cualquiera de los usuarios creados por el seed:
   - Usuario: `AdminTaquero` — Contraseña: `123`
   - Usuario: `memin` — Contraseña: `memin71`
   - Usuario: `david` — Contraseña: `negratomasa`

## Qué cambió respecto al HTML original

El HTML original tenía **todo** (menú, inventario, personal, comandas) como variables de
JavaScript en memoria — se perdía todo al recargar la página. Ahora:

- El catálogo (`CATALOGO`) se carga desde `api/productos.php` (tabla `productos` + `categorias`).
- El inventario se lee y actualiza contra la tabla `insumos` vía `api/insumos.php`.
- Las comandas (pendientes y cobradas) viven en las tablas `comandas` y `comanda_detalles`.
  Al marcar una comanda como **Cobrada**, el servidor descuenta automáticamente el inventario
  usando la tabla `recetas` (esto antes pasaba solo en el navegador y se perdía al recargar).
- El personal/login usa la tabla `usuarios`, con contraseñas guardadas de forma segura con
  `password_hash()` de PHP (nunca en texto plano).
- El corte de caja se calcula en vivo sumando las comandas cobradas del día, y al confirmar
  el cierre se guarda un registro permanente en `cortes_caja`.

## Notas / limitaciones a tener en cuenta

- **"Editar" una nota pendiente** ahora borra la comanda anterior y crea una nueva (el número
  de folio cambia). Si te importa conservar el mismo folio al editar, se puede agregar un
  endpoint de actualización de items — dime si lo necesitas.
- El "fondo fijo de caja" ($500) sigue siendo un valor fijo en el HTML porque no hay una tabla
  para configurarlo; si quieres que sea editable y persistente, se puede agregar una tabla de
  configuración.
- Las contraseñas antiguas del HTML (texto plano) ya no se muestran ni se pueden recuperar —
  solo se pueden **cambiar** desde el formulario de personal (dejar el campo contraseña vacío
  al editar significa "no cambiarla").
- Esta conexión usa `root` sin contraseña, que es normal para desarrollo local en XAMPP. Si algún
  día subes esto a un servidor público, crea un usuario de MySQL con permisos limitados y
  contraseña, y actualízalo en `config.php`.
