#!/bin/bash

# 1. Ubah konfigurasi default Nginx ke folder public milik Laravel
sed -i 's|root /home/site/wwwroot;|root /home/site/wwwroot/public;|g' /etc/nginx/sites-available/default

# 2. Masukkan aturan URL routing Laravel biar menu-menu di web gak eror 404
sed -i 's|try_files $uri $uri/ =404;|try_files $uri $uri/ /index.php?$query_string;|g' /etc/nginx/sites-available/default

# 3. Reload Nginx secara aman
sudo service nginx reload