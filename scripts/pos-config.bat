@echo off
rem ==================================================
rem Configuracion central del POS (Windows / Laragon)
rem ==================================================

if not defined POS_PROJECT_DIR set "POS_PROJECT_DIR=C:\laragon\www\cafeteria-pos"
if not defined POS_LARAGON_DIR set "POS_LARAGON_DIR=C:\laragon"
if not defined POS_HOST set "POS_HOST=192.168.1.211"
if not defined POS_PORT set "POS_PORT=8000"
if not defined POS_OPEN_BROWSER set "POS_OPEN_BROWSER=1"
if not defined POS_WAIT_SECONDS set "POS_WAIT_SECONDS=35"
if not defined POS_CATALOGO_EXCEL set "POS_CATALOGO_EXCEL=database\catalogos\catalogo_maestro.xlsx"
if not defined POS_CLOSE_LARAGON_ON_STOP set "POS_CLOSE_LARAGON_ON_STOP=0"
if not defined POS_TRY_SERVICE_CONTROL set "POS_TRY_SERVICE_CONTROL=0"
if not defined POS_AUTO_OPEN_LARAGON set "POS_AUTO_OPEN_LARAGON=1"

set "POS_URL=http://%POS_HOST%:%POS_PORT%"
set "POS_LARAGON_EXE=%POS_LARAGON_DIR%\laragon.exe"
