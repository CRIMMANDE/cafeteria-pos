# POS Cafeteria - Operacion y Mantenimiento Local

## Seccion A: Resumen del Proyecto

Este proyecto es un sistema POS de cafeteria en Laravel, operando en red local dentro del establecimiento.

- Una laptop funciona como servidor local.
- Meseros y caja acceden desde celulares/tablets/PC en la misma red.
- Se manejan impresoras termicas por area:
  - cocina
  - barra
  - ticket cliente/ventas

Funcionalidades principales:

- mesas, llevar y empleados
- comandas por area
- impresion de ticket y cierre de cuenta
- recuperacion de cuentas
- catalogo maestro por Excel
- menu del dia
- productos configurables
- modalidades (solo/desayuno/comida)
- extras, notas, salsa y grupos dinamicos

Arquitectura:

- Laravel + Blade + JavaScript
- MySQL
- Laragon en Windows
- red LAN del establecimiento

---

## Seccion B: Uso Operativo (No Tecnico)

### 1) Iniciar POS (doble clic)

Archivo:

- `scripts\iniciar-pos.bat`

Que hace:

1. Carga `scripts\pos-config.bat`
2. Verifica proyecto, Laragon y PHP
3. Abre Laragon si no esta abierto
4. Valida conexion a MySQL desde Laravel
5. Si la URL no responde, inicia `php artisan serve` en segundo plano
6. Muestra URL operativa y opcionalmente abre navegador

Salida esperada:

- `POS INICIADO CORRECTAMENTE`
- URL local del POS

### 2) Detener POS

Archivo:

- `scripts\detener-pos.bat`

Que hace:

1. Detiene servidor Laravel del POS (`artisan serve`)
2. Opcionalmente intenta detener servicios Windows (si existen)
3. Opcionalmente cierra Laragon (configurable)
4. Muestra confirmacion

Salida esperada:

- `POS detenido correctamente`

### 3) Importar catalogo maestro

Archivo:

- `scripts\importar-catalogo.bat`

Que hace:

1. Verifica proyecto, Laragon, PHP y Excel maestro
2. Valida conexion a MySQL desde Laravel
3. Ejecuta importacion:
   - `php artisan pos:importar-catalogo-maestro database/catalogos/catalogo_maestro.xlsx`
4. Muestra resultado claro

### 4) Limpiar datos transaccionales del POS

Este comando elimina solo datos operativos/transaccionales del POS y mantiene intactos los catalogos.

Tablas que limpia:

- `ordenes` o `ordens` (segun exista en la BD)
- `orden_detalles`
- `orden_detalle_opciones`
- `orden_detalle_extras`
- `orden_detalle_componentes`

Importante:

- No elimina catalogos (`productos`, `categorias`, `extras`, `grupos_opciones`, `opciones`, `menu_dia`, etc.)
- Usa `truncate` para reiniciar IDs
- Desactiva temporalmente llaves foraneas durante la limpieza

Desde donde ejecutarlo:

1. Abrir terminal (PowerShell o CMD)
2. Ir a la raiz del proyecto:
   - `cd C:\laragon\www\cafeteria-pos`
3. Ejecutar:
   - `php artisan pos:limpiar-datos`

En produccion/no interactivo:

- `php artisan pos:limpiar-datos --force`

Salida esperada:

- `Limpiando datos...`
- `✔ orden_detalles limpiado`
- `✔ ordenes limpiado` (o `✔ ordens limpiado`)
- `Datos limpiados correctamente.`

Recomendacion operativa:

- Ejecutarlo cuando no haya pedidos en curso (por ejemplo, antes de abrir o despues del cierre).

### 5) Ubicacion del Excel maestro

- `database\catalogos\catalogo_maestro.xlsx`

### 6) URL para otros dispositivos

Se define en `scripts\pos-config.bat` con:

- `POS_HOST`
- `POS_PORT`

Ejemplo:

- `http://192.168.1.211:800`

### 7) Si no carga desde otro dispositivo

Checklist:

1. Confirmar laptop servidor encendida
2. Ejecutar `iniciar-pos.bat`
3. Confirmar misma red LAN/WiFi
4. Verificar firewall Windows (red privada)
5. Verificar IP actual del servidor

---

## Seccion C: Desarrollo y Pruebas

### Desarrollo (DEV)

1. Hacer cambios en codigo
2. Probar flujo en navegador local
3. Si hubo cambios de datos, correr `importar-catalogo.bat`
4. Validar orden, impresion y cierre
5. Revisar logs en `storage\logs`

### Produccion local / operacion real

1. Ejecutar `iniciar-pos.bat`
2. Validar acceso por IP desde otro dispositivo
3. Validar comandas cocina/barra
4. Validar ticket cliente
5. Validar catalogo si hubo cambios

---

## Seccion D: Flujo Recomendado de Cambios

1. Hacer cambio en codigo o catalogo
2. Probar localmente
3. Probar por IP de red local
4. Validar impresion
5. Validar catalogo maestro
6. Liberar a operacion real

---

## Configuracion Centralizada

Archivo:

- `scripts\pos-config.bat`

Variables:

- `POS_PROJECT_DIR`
- `POS_LARAGON_DIR`
- `POS_HOST`
- `POS_PORT`
- `POS_OPEN_BROWSER` (`1`/`0`)
- `POS_WAIT_SECONDS`
- `POS_CATALOGO_EXCEL`
- `POS_CLOSE_LARAGON_ON_STOP` (`1`/`0`)
- `POS_TRY_SERVICE_CONTROL` (`1`/`0`, recomendado `0` para evitar permisos de administrador)
- `POS_AUTO_OPEN_LARAGON` (`1`/`0`)

Nota:

- Estos scripts NO usan `laragon start`/`laragon stop` para evitar errores de licencia/comando no soportado en algunas instalaciones.

---

## Scripts Disponibles

En `scripts\`:

- `iniciar-pos.bat`
- `detener-pos.bat`
- `importar-catalogo.bat`
- `iniciar-pos.ps1` (opcional)
- `detener-pos.ps1` (opcional)
- `importar-catalogo.ps1` (opcional)
- `pos-config.bat`

---

## Mensajes de Error Frecuentes

- `Laragon no encontrado`: revisar `POS_LARAGON_DIR`
- `No se encontro artisan`: revisar `POS_PROJECT_DIR`
- `No se pudo validar conexion a base de datos`: abrir Laragon y arrancar MySQL
- `No se pudo localizar PHP`: abrir Laragon o corregir PATH
- `La importacion del catalogo fallo`: revisar salida del comando artisan

---

## Seccion Extra: Accesos Directos de Escritorio

Crear 3 accesos directos:

1. `INICIAR POS`
2. `DETENER POS`
3. `IMPORTAR CATALOGO`

Pasos:

1. Abrir carpeta `scripts\`
2. Click derecho en cada `.bat` -> `Enviar a` -> `Escritorio (crear acceso directo)`
3. Renombrar accesos para personal
4. Opcional: `Propiedades` -> `Ejecutar minimizado`

---

## Comando Tecnico de Referencia

```bat
php artisan pos:importar-catalogo-maestro database/catalogos/catalogo_maestro.xlsx
php artisan pos:limpiar-datos
```

