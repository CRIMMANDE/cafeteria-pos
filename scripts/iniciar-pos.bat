@echo off
setlocal EnableExtensions EnableDelayedExpansion

title INICIAR POS

set "SCRIPT_DIR=%~dp0"
call "%SCRIPT_DIR%pos-config.bat"

echo.
echo ========================================
echo INICIANDO POS...
echo ========================================
echo.

if not exist "%POS_PROJECT_DIR%\artisan" (
  echo [ERROR] No se encontro el proyecto Laravel en:
  echo         %POS_PROJECT_DIR%
  echo         Revisa POS_PROJECT_DIR en scripts\pos-config.bat
  goto :fail
)

if not exist "%POS_LARAGON_EXE%" (
  echo [ERROR] No se encontro Laragon en:
  echo         %POS_LARAGON_EXE%
  echo         Revisa POS_LARAGON_DIR en scripts\pos-config.bat
  goto :fail
)

call :resolve_php
if not defined PHP_BIN (
  echo [ERROR] No se pudo localizar PHP.
  echo         Instala/abre Laragon o agrega PHP al PATH.
  goto :fail
)

echo [OK] Proyecto: %POS_PROJECT_DIR%
echo [OK] Laragon : %POS_LARAGON_EXE%
echo [OK] PHP     : !PHP_BIN!
echo [OK] Bind    : %POS_BIND_HOST%:%POS_PORT%

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
  echo [INFO] POS aun no responde en %POS_URL%
  echo [INFO] Iniciando servidor Laravel en segundo plano...

  if not exist "%POS_PROJECT_DIR%\storage\logs" mkdir "%POS_PROJECT_DIR%\storage\logs" >nul 2>&1
  set "POS_SERVE_LOG=%POS_PROJECT_DIR%\storage\logs\pos-serve.log"
  set "POS_SERVE_ERR=%POS_PROJECT_DIR%\storage\logs\pos-serve-error.log"
  if exist "!POS_SERVE_LOG!" del /q "!POS_SERVE_LOG!" >nul 2>&1
  if exist "!POS_SERVE_ERR!" del /q "!POS_SERVE_ERR!" >nul 2>&1

  powershell -NoProfile -ExecutionPolicy Bypass -Command "try { Start-Process -FilePath '!PHP_BIN!' -WorkingDirectory '%POS_PROJECT_DIR%' -ArgumentList @('artisan','serve','--host=%POS_BIND_HOST%','--port=%POS_PORT%') -WindowStyle Hidden -RedirectStandardOutput '!POS_SERVE_LOG!' -RedirectStandardError '!POS_SERVE_ERR!' | Out-Null; exit 0 } catch { exit 1 }" >nul 2>&1
  if errorlevel 1 (
    echo [ERROR] No se pudo lanzar el servidor Laravel en segundo plano.
    echo         Revisa permisos de ejecucion de PowerShell y PHP.
    goto :fail
  )

  call :wait_for_url
  if errorlevel 1 (
    echo [ERROR] El POS no respondio despues de %POS_WAIT_SECONDS% segundos.
    echo         Revisa logs:
    echo         - %POS_PROJECT_DIR%\storage\logs\pos-serve.log
    echo         - %POS_PROJECT_DIR%\storage\logs\pos-serve-error.log
    goto :fail
  )
) else (
  echo [OK] POS ya estaba respondiendo en %POS_URL%
)

echo.
echo ----------------------------------------
echo POS INICIADO CORRECTAMENTE
echo URL LOCAL:
echo %POS_URL%
echo Acceso desde otros dispositivos de la red:
echo %POS_URL%
echo ----------------------------------------
echo.

if "%POS_OPEN_BROWSER%"=="1" (
  start "" "%POS_URL%"
)

echo Puedes cerrar esta ventana.
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
powershell -NoProfile -ExecutionPolicy Bypass -Command "try { $r = Invoke-WebRequest -Uri '%POS_HEALTH_URL%' -UseBasicParsing -TimeoutSec 3; if ($r.StatusCode -ge 200 -and $r.StatusCode -lt 500) { exit 0 } else { exit 1 } } catch { exit 1 }" >nul 2>&1
exit /b %errorlevel%

:wait_for_url
for /l %%S in (1,1,%POS_WAIT_SECONDS%) do (
  call :check_url
  if not errorlevel 1 exit /b 0
  timeout /t 1 >nul
)
exit /b 1

:fail
echo.
echo No fue posible iniciar el POS.
echo.
exit /b 1

