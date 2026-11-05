FROM php:8.2-cli

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Copy project files
COPY . /var/www/html/

WORKDIR /var/www/html

# Fix permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port 80 for Railway edge proxy
EXPOSE 80

# Dynamically bind to Railway's $PORT (default 80)
CMD ["sh", "-c", "PORT=${PORT:-80}; echo \"Starting PHP server on port $PORT...\"; exec php -S 0.0.0.0:$PORT router.php"]
