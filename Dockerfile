FROM php:8.2-cli

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Copy project files
COPY . /var/www/html/

WORKDIR /var/www/html

# Fix permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose Railway's configured port (8080)
EXPOSE 8080

# Listen on PORT (default 8080 to match Railway Public Networking)
CMD ["sh", "-c", "PORT=${PORT:-8080}; echo \"Starting PHP server on 0.0.0.0:$PORT...\"; exec php -S 0.0.0.0:$PORT router.php"]
