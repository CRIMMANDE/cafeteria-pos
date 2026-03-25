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
  echo [INFO] POS no responde en %POS_URL%
  echo [INFO] Iniciando servidor Laravel en segundo plano...

  if not exist "%POS_PROJECT_DIR%\storage\logs" mkdir "%POS_PROJECT_DIR%\storage\logs" >nul 2>&1
  start "POS_ARTISAN_SERVER" /min cmd /c "cd /d ""%POS_PROJECT_DIR%"" && ""!PHP_BIN!"" artisan serve --host=%POS_HOST% --port=%POS_PORT% >> storage\logs\pos-serve.log 2>&1"

  call :wait_for_url
  if errorlevel 1 (
    echo [ERROR] El POS no respondio despues de %POS_WAIT_SECONDS% segundos.
    echo         Revisa el log: %POS_PROJECT_DIR%\storage\logs\pos-serve.log
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
powershell -NoProfile -ExecutionPolicy Bypass -Command "try { $r = Invoke-WebRequest -Uri '%POS_URL%' -UseBasicParsing -TimeoutSec 3; if ($r.StatusCode -ge 200 -and $r.StatusCode -lt 500) { exit 0 } else { exit 1 } } catch { exit 1 }" >nul 2>&1
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

