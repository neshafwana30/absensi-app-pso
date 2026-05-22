#!/bin/bash

# 1. Copas paksa gerbang utama Laravel langsung ke folder root terluar biar Nginx gak usah nyari ke folder public
cp /home/site/wwwroot/public/index.php /home/site/wwwroot/index.php
cp /home/site/wwwroot/public/.htaccess /home/site/wwwroot/.htaccess

# 2. Perbaiki jalur autoload di dalam file index yang baru dipindah agar gak error vendor
sed -i "s|require __DIR__.'/../vendor/autoload.php'|require __DIR__.'/vendor/autoload.php'|g" /home/site/wwwroot/index.php
sed -i "s|__DIR__.'/../bootstrap/app.php'|__DIR__.'/bootstrap/app.php'|g" /home/site/wwwroot/index.php

# 3. Paksa Nginx reload konfigurasi standar
service nginx reload