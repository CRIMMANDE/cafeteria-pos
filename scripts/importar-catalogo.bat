@echo off
setlocal EnableExtensions EnableDelayedExpansion

title IMPORTAR CATALOGO POS

set "SCRIPT_DIR=%~dp0"
call "%SCRIPT_DIR%pos-config.bat"

echo.
echo ========================================
echo IMPORTACION DE CATALOGO MAESTRO
echo ========================================
echo.

if not exist "%POS_PROJECT_DIR%\artisan" (
  echo [ERROR] No se encontro artisan en:
  echo         %POS_PROJECT_DIR%
  echo         Revisa POS_PROJECT_DIR en scripts\pos-config.bat
  goto :fail
)

call :resolve_php
if not defined PHP_BIN (
  echo [ERROR] No se pudo localizar PHP.
  echo         Instala/abre Laragon o agrega PHP al PATH.
  goto :fail
)

if not exist "%POS_LARAGON_EXE%" (
  echo [ERROR] No se encontro Laragon en:
  echo         %POS_LARAGON_EXE%
  echo         Revisa POS_LARAGON_DIR en scripts\pos-config.bat
  goto :fail
)

set "CATALOGO_PATH=%POS_CATALOGO_EXCEL%"
if not exist "%CATALOGO_PATH%" (
  set "CATALOGO_PATH=%POS_PROJECT_DIR%\%POS_CATALOGO_EXCEL%"
)
if not exist "%CATALOGO_PATH%" (
  echo [ERROR] No se encontro el archivo Excel del catalogo:
  echo         %POS_CATALOGO_EXCEL%
  echo         Revisa POS_CATALOGO_EXCEL en scripts\pos-config.bat
  goto :fail
)

echo [OK] PHP      : !PHP_BIN!
echo [OK] Proyecto : %POS_PROJECT_DIR%
echo [OK] Excel    : %CATALOGO_PATH%

tasklist /FI "IMAGENAME eq laragon.exe" | find /I "laragon.exe" >nul
if errorlevel 1 (
  if "%POS_AUTO_OPEN_LARAGON%"=="1" (
    echo [INFO] Abriendo Laragon...
    start "" "%POS_LARAGON_EXE%"
    timeout /t 3 >nul
  ) else (
    echo [INFO] Laragon no esta abierto. Abre Laragon manualmente.
  )
)


call :check_db
if errorlevel 1 (
  if "%POS_TRY_SERVICE_CONTROL%"=="1" (
    echo [INFO] Intentando iniciar servicio MySQL de Windows...
    call :try_start_mysql_service
    timeout /t 2 >nul
  )

  call :check_db
  if errorlevel 1 (
    echo [ERROR] No se pudo validar conexion a base de datos.
    echo         En Laragon, presiona Start All o inicia MySQL.
    goto :fail
  )
)

echo [OK] Conexion a base de datos validada.

call :check_url
if errorlevel 1 (
  echo [INFO] La URL %POS_URL% no responde en este momento.
  echo [INFO] Se continuara con la importacion porque es una tarea administrativa.
) else (
  echo [OK] URL del POS accesible: %POS_URL%
)

pushd "%POS_PROJECT_DIR%" >nul
"!PHP_BIN!" artisan pos:importar-catalogo-maestro "%CATALOGO_PATH%"
set "IMPORT_EXIT=%errorlevel%"
popd >nul

if not "%IMPORT_EXIT%"=="0" (
  echo.
  echo [ERROR] La importacion del catalogo fallo.
  goto :fail
)

echo.
echo [OK] Catalogo maestro importado correctamente.
echo.
exit /b 0

:resolve_php
set "PHP_BIN="
for /f "delims=" %%I in ('where php 2^>nul') do (
  set "PHP_BIN=%%I"
  goto :php_found
)
for /f "delims=" %%D in ('dir /b /ad /o-n "%POS_LARAGON_DIR%\bin\php\php*" 2^>nul') do (
  if exist "%POS_LARAGON_DIR%\bin\php\%%D\php.exe" (
    set "PHP_BIN=%POS_LARAGON_DIR%\bin\php\%%D\php.exe"
    goto :php_found
  )
)
:php_found
exit /b 0

:check_db
pushd "%POS_PROJECT_DIR%" >nul
"!PHP_BIN!" artisan migrate:status --no-interaction >nul 2>&1
set "DB_EXIT=%errorlevel%"
popd >nul
exit /b %DB_EXIT%

:try_start_mysql_service
for %%S in (MySQL MySQL80 MariaDB mariadb LaragonMySQL) do (
  sc query "%%S" >nul 2>&1
  if not errorlevel 1 sc start "%%S" >nul 2>&1
)
exit /b 0

:check_url
powershell -NoProfile -ExecutionPolicy Bypass -Command "try { $r = Invoke-WebRequest -Uri '%POS_URL%' -UseBasicParsing -TimeoutSec 3; if ($r.StatusCode -ge 200 -and $r.StatusCode -lt 500) { exit 0 } else { exit 1 } } catch { exit 1 }" >nul 2>&1
exit /b %errorlevel%

:fail
echo.
echo No fue posible completar la importacion.
echo.
exit /b 1

