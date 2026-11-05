<?php
// Router for PHP built-in web server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$path = __DIR__ . $uri;

// If request is for a directory, check for index.php or index.html
if (is_dir($path)) {
    if (file_exists($path . '/index.php')) {
        require_once $path . '/index.php';
        return true;
    }
    if (file_exists($path . '/index.html')) {
        return false;
    }
}

// If file exists (css, js, png, php, etc.), let PHP serve it
if ($uri !== '/' && file_exists($path)) {
    return false;
}

// Otherwise route to index.php
require_once __DIR__ . '/index.php';
