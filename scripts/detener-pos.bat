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
set "SE_ENCONTRO_PID=0"
set "HUBO_FALLO_PERMISOS=0"
set "PIDS_VISTOS=,"

for /f "tokens=5" %%P in ('netstat -ano ^| findstr /R /C:"^ *TCP" ^| findstr /C:":%POS_PORT%"') do (
  call :kill_pid %%P
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
  if "%HUBO_FALLO_PERMISOS%"=="1" (
    if /I not "%~1"=="--elevated" (
      echo [INFO] Reintentando con permisos de Administrador...
      powershell -NoProfile -ExecutionPolicy Bypass -Command "try { Start-Process -FilePath '%~f0' -WorkingDirectory '%~dp0' -ArgumentList '--elevated' -Verb RunAs | Out-Null; exit 0 } catch { exit 1 }" >nul 2>&1
      if not errorlevel 1 (
        echo [INFO] Se abrio una nueva ventana para completar el detenido.
        exit /b 0
      )
    )
    echo [WARN] Se detecto un proceso en el puerto %POS_PORT%, pero Windows denego permisos para detenerlo.
    echo [WARN] Ejecuta este script como Administrador para cerrarlo.
  ) else (
    if "%SE_ENCONTRO_PID%"=="1" (
      echo [WARN] Se detectaron procesos en el puerto %POS_PORT%, pero no fue posible detenerlos.
    ) else (
      echo [INFO] No se detectaron procesos del POS activos en el puerto %POS_PORT%.
    )
  )
)

echo.
echo POS detenido correctamente.
echo.
exit /b 0

:kill_pid
set "TARGET_PID=%~1"
if "!TARGET_PID!"=="" exit /b 0
if "!TARGET_PID!"=="0" exit /b 0

rem Evita repetir el mismo PID (IPv4/IPv6 pueden duplicarlo).
if not "!PIDS_VISTOS:,!TARGET_PID!,=!"=="!PIDS_VISTOS!" exit /b 0
set "PIDS_VISTOS=!PIDS_VISTOS!!TARGET_PID!,"
set "SE_ENCONTRO_PID=1"

taskkill /PID !TARGET_PID! /F >nul 2>&1
if errorlevel 1 (
  set "HUBO_FALLO_PERMISOS=1"
  echo [WARN] No fue posible detener PID !TARGET_PID! - acceso denegado.
  exit /b 0
)

set "DETUVO_ALGO=1"
echo [OK] Servidor Laravel detenido - PID !TARGET_PID!.
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
