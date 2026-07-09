# Migración: Split de `producto_imagenes`

Refactorización de la tabla `producto_imagenes` para separar la responsabilidad
de "imagen" de las responsabilidades de "plantilla de mensaje de WhatsApp" y
"plantilla de mensaje de Email", que hoy viven mezcladas en la misma tabla.

Patrón usado: **Expand → Migrate → Contract** (bajo riesgo, reversible en cada paso).

## Estructura de este paquete

```
database/migrations/
  2026_07_04_100000_create_producto_whatsapp_pasos_table.php   <- FASE EXPAND
  2026_07_04_100001_create_producto_email_pasos_table.php      <- FASE EXPAND
  2026_07_18_100000_drop_legacy_columns_from_producto_imagenes_table.php <- FASE CONTRACT (NO EJECUTAR AÚN)

app/Console/Commands/
  MigrateProductoImagenesData.php   <- FASE MIGRATE (comando Artisan)

app/Models/
  ProductoWhatsappPaso.php
  ProductoEmailPaso.php
```

## Cómo probarlo en tu entorno local

1. **Copia los archivos** a las rutas equivalentes de tu proyecto Laravel
   (`tami-back-teams`), respetando la misma estructura de carpetas.

2. **Registra el comando** (si tu versión de Laravel no auto-descubre comandos
   en `app/Console/Commands`, agrégalo en `app/Console/Kernel.php` dentro del
   arreglo `$commands`).

3. **Corre solo las migraciones EXPAND** (por ahora, NO la de "drop_legacy_columns"):

   ```bash
   php artisan migrate --path=database/migrations/2026_07_04_100000_create_producto_whatsapp_pasos_table.php
   php artisan migrate --path=database/migrations/2026_07_04_100001_create_producto_email_pasos_table.php
   ```

4. **Prueba primero en modo simulado** (no escribe nada, solo te muestra qué haría):

   ```bash
   php artisan migrate:producto-imagenes-data --dry-run
   ```

   Revisa la salida en consola: deberías ver una línea por cada mensaje de
   WhatsApp/Email detectado en `producto_imagenes`.

5. **Corre la migración de datos real:**

   ```bash
   php artisan migrate:producto-imagenes-data
   ```

   Al final te mostrará una tabla comparando conteos origen vs destino.
   **Verifica manualmente** que los números tengan sentido antes de continuar
   (recuerda: una fila de `tipo='whatsapp'` puede generar hasta 3 filas en
   `producto_whatsapp_pasos`, una por cada mensaje no vacío).

6. **Actualiza tu código de aplicación** (controllers, jobs de envío de
   campañas, vistas de administración) para que lean de
   `ProductoWhatsappPaso` / `ProductoEmailPaso` en vez de las columnas viejas
   de `producto_imagenes`.

7. **Agrega la relación en tu modelo `Producto`** (no incluido aquí porque no
   tengo el archivo original, agrégalo tú):

   ```php
   public function whatsappPasos()
   {
       return $this->hasMany(ProductoWhatsappPaso::class);
   }

   public function emailPasos()
   {
       return $this->hasMany(ProductoEmailPaso::class);
   }
   ```

8. **Solo cuando todo lo anterior esté validado en staging** y desplegado de
   forma estable, corre la migración CONTRACT que elimina las columnas viejas:

   ```bash
   php artisan migrate --path=database/migrations/2026_07_18_100000_drop_legacy_columns_from_producto_imagenes_table.php
   ```

## Rollback

- Si algo sale mal en las fases EXPAND o MIGRATE, simplemente puedes hacer
  `php artisan migrate:rollback` sobre esas migraciones puntuales — no se
  tocó `producto_imagenes` todavía, así que no hay pérdida de datos posible.
- Si ya corriste la fase CONTRACT y necesitas revertir, el `down()` de esa
  migración **restaura la estructura de columnas pero no los datos
  eliminados** — por eso es crítico no llegar a esta fase sin haber
  verificado los conteos antes.
