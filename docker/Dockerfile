# Omeka Classic for trying the Web Archive plugin. Don't run this image in production.
FROM php:8.2-apache

ARG OMEKA_VERSION=3.2.2

RUN apt-get update \
    && apt-get install -y --no-install-recommends imagemagick unzip curl \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install mysqli pdo_mysql exif \
    && a2enmod rewrite \
    && sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && printf 'upload_max_filesize = 512M\npost_max_size = 520M\n' > /usr/local/etc/php/conf.d/uploads.ini

RUN curl -fsSL -o /tmp/omeka.zip \
        https://github.com/omeka/Omeka/releases/download/v${OMEKA_VERSION}/omeka-${OMEKA_VERSION}.zip \
    && unzip -q /tmp/omeka.zip -d /tmp \
    && rm -rf /var/www/html \
    && mv /tmp/omeka-${OMEKA_VERSION} /var/www/html \
    && rm /tmp/omeka.zip \
    && cd /var/www/html \
    && ([ -f .htaccess ] || cp .htaccess.changeme .htaccess) \
    && chown -R www-data:www-data files

COPY db.ini /var/www/html/db.ini
