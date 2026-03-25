# POS Cafeteria (Laravel)

Repositorio del sistema POS estable para operacion local en LAN.

Este README esta orientado a reconstruir el sistema en otra maquina de forma conservadora, sin cambios destructivos.

## 1) Estado del repositorio para replicacion

- El esquema de base esta versionado con migraciones en `database/migrations`.
- El flujo de catalogo maestro esta versionado:
  - Excel maestro en `database/catalogos/catalogo_maestro.xlsx`
  - Comando `php artisan pos:importar-catalogo-maestro`
  - Script `scripts/importar-catalogo.bat`
- Hay scripts operativos para iniciar/detener POS:
  - `scripts/iniciar-pos.bat`
  - `scripts/detener-pos.bat`
  - `scripts/pos-config.bat`

## 2) Requisitos de entorno (nueva maquina)

- Windows con Laragon (o stack equivalente con PHP + MySQL)
- PHP 8.2+
- Composer
- Node.js + npm (solo si se recompilan assets)
- MySQL activo
- Impresoras termicas configuradas en Windows (USB o red)

## 3) Migracion a otro equipo (paso a paso)

1. Clonar/copiar el repositorio en una ruta local.
2. Instalar dependencias PHP:
   - `composer install`
3. Instalar dependencias frontend (si aplica):
   - `npm install`
4. Crear entorno:
   - copiar `.env.example` a `.env`
5. Configurar `.env`:
   - `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_URL`
   - `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - variables de impresoras `IMPRESORA_*` / `THERMAL_*`
6. Generar llave de app:
   - `php artisan key:generate`
7. Crear base de datos vacia en MySQL.
8. Ejecutar migraciones en la nueva maquina:
   - `php artisan migrate --force`
9. Importar catalogo maestro:
   - `php artisan pos:importar-catalogo-maestro database/catalogos/catalogo_maestro.xlsx`
10. Ajustar scripts de red local en `scripts/pos-config.bat`:
   - `POS_PROJECT_DIR`
   - `POS_LARAGON_DIR`
   - `POS_HOST` (IP LAN del servidor)
   - `POS_PORT`
11. Levantar POS:
   - `scripts/iniciar-pos.bat`
12. Validar acceso local, acceso por IP LAN e impresion.

## 4) Base de datos y seeders

- Flujo recomendado de reconstruccion: `migrate` + importacion de catalogo maestro.
- `db:seed` no es necesario para reconstruccion estandar.
- Los seeders actuales se conservan como legado/soporte, no como paso obligatorio.

## 5) Catalogo maestro

- Archivo esperado por defecto:
  - `database/catalogos/catalogo_maestro.xlsx`
- Importacion:
  - `php artisan pos:importar-catalogo-maestro database/catalogos/catalogo_maestro.xlsx`
- Generacion de plantilla (opcional):
  - `php artisan pos:generar-catalogo-maestro-template --force`

## 6) Impresoras termicas

Configurar en `.env` segun instalacion local:

- Cocina:
  - `IMPRESORA_COCINA_DRIVER`, `IMPRESORA_COCINA_NOMBRE` o `IMPRESORA_COCINA_IP`
- Barra:
  - `IMPRESORA_BARRA_DRIVER`, `IMPRESORA_BARRA_NOMBRE` o `IMPRESORA_BARRA_IP`
- Ventas/Ticket:
  - `IMPRESORA_VENTAS_DRIVER`, `IMPRESORA_VENTAS_NOMBRE` o `IMPRESORA_VENTAS_IP`
  - `IMPRESORA_VENTAS_TIENDA_NOMBRE`
  - `IMPRESORA_VENTAS_TIENDA_DIRECCION`
  - `IMPRESORA_VENTAS_TIENDA_TELEFONO`

## 7) Pruebas sugeridas despues de migrar

1. Abrir `http://127.0.0.1:8000` en el servidor local.
2. Abrir `http://<IP_SERVIDOR>:8000` desde otro dispositivo de la LAN.
3. Crear orden en mesa normal, llevar y empleados.
4. Verificar comandas de cocina y barra.
5. Cerrar cuenta y revisar ticket de ventas.
6. Probar recuperacion de folio pagado.
7. Validar corte de ventas (vista, impresion y exportacion).

## 8) Desarrollo (DEV)

Flujo recomendado:

1. `composer install`
2. `npm install`
3. `php artisan serve --host=127.0.0.1 --port=8000`
4. `npm run dev` (si hay cambios frontend)
5. Probar flujo completo de orden, cierre e impresion.

## 9) Produccion local (operacion interna)

1. Ajustar `scripts/pos-config.bat` con IP LAN correcta.
2. Ejecutar `scripts/iniciar-pos.bat`.
3. Validar acceso desde servidor y desde otro dispositivo.
4. Ejecutar `scripts/detener-pos.bat` al cierre.

## 10) Validacion de impresoras

1. Confirmar impresora asignada por area (`cocina`, `barra`, `ventas`).
2. Confirmar variables `.env` de cada impresora.
3. Enviar una orden de prueba y validar comanda en cocina/barra.
4. Cerrar cuenta y validar ticket cliente.
5. Revisar `storage/logs/laravel.log` si hay error de impresion.

## 11) Operacion diaria

Ver guia operativa extendida en `README_POS.md`.

## 12) Notas de seguridad operativa

- No usar `migrate:fresh` en entornos con datos reales.
- No ejecutar `pos:limpiar-datos` durante servicio activo.
- No exponer credenciales reales en el repositorio.
