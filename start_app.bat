@echo off
title Kuesioner Anggota - BMT Fastabiq
color 0A
cd /D "%~dp0"

echo ============================================================
echo   Kuesioner Anggota - Sistem Kuesioner Kepuasan Anggota
echo   KSPPS BMT Fastabiq Khoiro Ummah
echo ============================================================
echo.

:: 1. Periksa apakah MySQL XAMPP sudah berjalan
echo [1/3] Memeriksa layanan MySQL Database...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo     [OK] Server MySQL sudah aktif.
) else (
    echo     [INFO] Memulai server MySQL dari XAMPP...
    if exist "D:\xampp_8.3\mysql_start.bat" (
        start "" /min "D:\xampp_8.3\mysql_start.bat"
        timeout /t 3 /nobreak >nul
    ) else if exist "C:\xampp\mysql_start.bat" (
        start "" /min "C:\xampp\mysql_start.bat"
        timeout /t 3 /nobreak >nul
    ) else (
        echo     [PERINGATAN] Silakan aktifkan modul MySQL pada XAMPP Control Panel.
    )
)

:: 2. Tampilkan URL Akses
echo.
echo [2/3] Informasi Tautan Aplikasi:
echo     - Kuesioner Anggota (Publik) : http://localhost:8088/survey.php
echo     - Ruang Kerja Admin & Kepala : http://localhost:8088/login.php
echo     - Setup & Diagnostik Database: http://localhost:8088/setup.php
echo.

:: 3. Buka Browser Otomatis
echo [3/3] Membuka peramban (browser)...
start http://localhost:8088/survey.php

echo.
echo ============================================================
echo Server aktif di http://localhost:8088
echo Tekan Ctrl+C pada jendela ini untuk menghentikan server.
echo ============================================================
echo.

:: Jalankan Server PHP
if exist "D:\xampp_8.3\php\php.exe" (
    "D:\xampp_8.3\php\php.exe" -S 127.0.0.1:8088 -t "%~dp0"
) else if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" -S 127.0.0.1:8088 -t "%~dp0"
) else (
    php -S 127.0.0.1:8088 -t "%~dp0"
)

pause

