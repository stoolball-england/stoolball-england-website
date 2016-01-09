@echo off
java -jar "%~dp0yuicompressor-2.4.2.jar" "%~dp0public_HTML\css\mobile.css" -o "%~dp0public_HTML\css\mobile.min.css"
java -jar "%~dp0yuicompressor-2.4.2.jar" "%~dp0public_HTML\css\medium.css" -o "%~dp0public_HTML\css\medium.min.css"
java -jar "%~dp0yuicompressor-2.4.2.jar" "%~dp0public_HTML\css\stoolball.css" -o "%~dp0public_HTML\css\stoolball.min.css"

java -jar "%~dp0yuicompressor-2.4.2.jar" "%~dp0public_HTML\scripts\stoolball.js" -o "%~dp0public_HTML\scripts\stoolball.min.js"