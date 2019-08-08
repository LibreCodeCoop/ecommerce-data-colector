if [ ! -d "vendor" ]; then
    composer global require hirak/prestissimo
    export COMPOSER_ALLOW_SUPERUSER=1
    composer install
fi
php .docker/php7/wait-for-postgres.php
php vendor/bin/phinx migrate
php banner.php
php-fpm
