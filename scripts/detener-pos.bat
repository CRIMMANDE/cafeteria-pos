@echo off
setlocal EnableExtensions EnableDelayedExpansion

title DETENER POS

set "SCRIPT_DIR=%~dp0"
call "%SCRIPT_DIR%pos-config.bat"

echo.
echo ========================================
echo DETENIENDO POS...
echo ========================================
echo.

set "DETUVO_ALGO=0"

for /f "skip=1 tokens=2 delims==" %%P in ('wmic process where "name='php.exe' and CommandLine like '%%artisan serve --host=%POS_HOST% --port=%POS_PORT%%%'" get ProcessId /value 2^>nul') do (
  if not "%%P"=="" (
    taskkill /PID %%P /F >nul 2>&1
    if not errorlevel 1 (
      set "DETUVO_ALGO=1"
      echo [OK] Servidor Laravel detenido - PID %%P.
    )
  )
)

if "%POS_TRY_SERVICE_CONTROL%"=="1" (
  call :try_stop_services
)

if "%POS_CLOSE_LARAGON_ON_STOP%"=="1" (
  taskkill /IM laragon.exe /F >nul 2>&1
  if not errorlevel 1 (
    set "DETUVO_ALGO=1"
    echo [OK] Laragon cerrado.
  )
)

if "%DETUVO_ALGO%"=="0" (
  echo [INFO] No se detectaron procesos del POS activos con ese perfil.
)

echo.
echo POS detenido correctamente.
echo.
exit /b 0

:try_stop_services
for %%S in (MySQL MySQL80 MariaDB mariadb LaragonMySQL Apache2.4 nginx) do (
  sc query "%%S" >nul 2>&1
  if not errorlevel 1 (
    sc stop "%%S" >nul 2>&1
    echo [INFO] Solicitud de stop enviada al servicio %%S.
  )
)
exit /b 0
