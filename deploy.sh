#!/bin/bash

# 1. Pindahkan kiblat Nginx ke folder public milik Laravel
sed -i 's|root /home/site/wwwroot;|root /home/site/wwwroot/public;|g' /etc/nginx/sites-available/default

# 2. Kasih aturan routing Laravel biar gak 404 pas klik menu-menu
sed -i 's|try_files $uri $uri/ =404;|try_files $uri $uri/ /index.php?$query_string;|g' /etc/nginx/sites-available/default

# 3. Reload Nginx biar konfigurasinya langsung aktif
service nginx reload