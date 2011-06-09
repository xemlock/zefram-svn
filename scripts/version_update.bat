@echo off
for /f %%i in ("%0") do @set dirname=%%~dpi
bash %dirname%version_update
