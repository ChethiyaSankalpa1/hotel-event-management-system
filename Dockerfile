FROM php:8.2-apache

# Install PDO MySQL extensions
RUN docker-php-ext-install pdo pdo_mysql

# Fix Apache MPM conflict: physically remove conflicting MPM module symlinks,
# then explicitly enable only mpm_prefork (required for mod_php) + rewrite
RUN rm -f /etc/apache2/mods-enabled/mpm_event.conf \
          /etc/apache2/mods-enabled/mpm_event.load \
          /etc/apache2/mods-enabled/mpm_worker.conf \
          /etc/apache2/mods-enabled/mpm_worker.load \
    && a2enmod mpm_prefork \
    && a2enmod rewrite \
    && apache2 -t

# Copy project files to Apache root
COPY . /var/www/html/

# Set working directory permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
