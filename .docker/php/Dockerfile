FROM php:8.2-fpm
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install mysqli

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

ADD xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
