# Usar imagen base de PHP con Apache
FROM php:8.1-apache

# Instalar extensiones necesarias para MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Copiar código fuente al contenedor
COPY . /var/www/html/

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/

# Crear directorio temp si no existe
RUN mkdir -p /var/www/html/temp && chmod 777 /var/www/html/temp

# Exponer puerto 80
EXPOSE 80

# Configurar Apache para mostrar errores PHP (opcional para desarrollo)
RUN echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker-php-errors.ini
RUN echo "error_log = /var/log/apache2/php_errors.log" >> /usr/local/etc/php/conf.d/docker-php-errors.ini