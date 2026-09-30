# Imagen para desplegar Bolos La Chapa en Render (o cualquier host con Docker).
# Un único contenedor con PHP 8.2 + Apache y MariaDB, que se inicializa
# con database.sql en cada arranque (demo: los datos se reinician).
FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends mariadb-server \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install mysqli

# Credenciales que lee includes/core/Config.php
ENV DB_HOST=127.0.0.1 \
    DB_PORT=3306 \
    DB_NAME=bolos_la_chapa \
    DB_USER=bolos \
    DB_PASSWORD=bolos_demo

COPY . /var/www/html/

RUN rm -f /var/www/html/Dockerfile /var/www/html/docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html/imagenes

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
# Quita posibles finales de línea de Windows (CRLF) del script
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 10000

CMD ["docker-entrypoint.sh"]
