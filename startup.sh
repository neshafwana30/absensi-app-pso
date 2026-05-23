#!/bin/bash

cp /home/site/wwwroot/public/index.php /home/site/wwwroot/index.php

sed -i "s|require __DIR__.'/../vendor/autoload.php'|require __DIR__.'/vendor/autoload.php'|g" /home/site/wwwroot/index.php

sed -i "s|\\$app = require_once __DIR__.'/../bootstrap/app.php';|\\$app = require_once __DIR__.'/bootstrap/app.php';|g" /home/site/wwwroot/index.php

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear