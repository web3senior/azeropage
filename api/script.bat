:: print details
@echo off
title Watch SASS/SCSS files (%CD%)
echo *==================================================
echo * Author: Amir Rahimi [web3senior[at]gmail[dot]com]
echo * Date: 2023-05-15
echo *==================================================

:: data members
:: set variables as local
SETLOCAL 
set adminI=views/admin/src/scss/Main.scss
set adminO=public/css/admin.min.css

set panelI=views/panel/assets/scss/global.scss
set panelO=dist/panel.min.css

::run scripts
echo start...
sass --watch --style compressed %panelI%:%panelO%
::--style compressed
:: end local
ENDLOCAL

:: stay open!
pause