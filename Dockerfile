FROM php:8.2-cli

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Copy project files
COPY . /var/www/html/

WORKDIR /var/www/html

# Fix permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Use shell to dynamically bind to Railway's $PORT (defaults to 80 if not set)
CMD ["sh", "-c", "PORT=${PORT:-80}; echo \"Listening on 0.0.0.0:$PORT...\"; exec php -S 0.0.0.0:$PORT router.php"]
