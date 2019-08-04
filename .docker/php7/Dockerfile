FROM php:7.3-fpm

RUN apt-get update

RUN apt-get install -y \
        git \
        libpq-dev \
        libzip-dev
RUN docker-php-ext-install -j "$(nproc)" \
        zip

# Install Postgre PDO
RUN apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

RUN git clone https://github.com/nikic/php-ast.git \
    && cd php-ast \
    && phpize \
    && ./configure \
    && make install \
    && echo 'extension=ast.so' > /usr/local/etc/php/php.ini \
    && cd .. && rm -rf php-ast

# Install xdebug
RUN pecl install xdebug-2.8.0beta1 \
    && docker-php-ext-enable xdebug

COPY config/php.ini /usr/local/etc/php/conf.d/

RUN curl https://getcomposer.org/composer.phar --output /usr/bin/composer
RUN chmod +x /usr/bin/composer

WORKDIR /app
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
ENTRYPOINT ["sh", "/usr/local/bin/entrypoint.sh"]
