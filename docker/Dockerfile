FROM php:7.2-fpm

RUN apt-get update && apt-get install -y zlib1g-dev libicu-dev g++

RUN pecl install redis mongodb

RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl
RUN docker-php-ext-enable redis mongodb
RUN docker-php-ext-install bcmath pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer
