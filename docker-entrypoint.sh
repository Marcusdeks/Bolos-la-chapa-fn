#!/bin/sh
set -e

# Render indica el puerto en la variable PORT (por defecto 10000)
PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Arrancar MariaDB en segundo plano
mkdir -p /run/mysqld
chown mysql:mysql /run/mysqld
mysqld_safe --user=mysql --bind-address=127.0.0.1 >/dev/null 2>&1 &

echo "Esperando a MariaDB..."
until mysqladmin ping --silent 2>/dev/null; do
    sleep 1
done

# Cargar esquema + datos de ejemplo y crear el usuario de la app
mysql -uroot < /var/www/html/database.sql
mysql -uroot <<SQL
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'127.0.0.1';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "Base de datos lista. Arrancando Apache en el puerto ${PORT}"
exec apache2-foreground
