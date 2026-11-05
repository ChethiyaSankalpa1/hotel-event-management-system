<?php
/**
 * Database Configuration & PDO Connection Singleton
 * Hotel Event Management System
 * Auto-detects Live InfinityFree Hosting and Local XAMPP Environment
 */

$isLiveHosting = (
    str_contains($_SERVER['HTTP_HOST'] ?? '', 'great-site.net') ||
    str_contains($_SERVER['SERVER_NAME'] ?? '', 'great-site.net') ||
    str_contains($_SERVER['HTTP_HOST'] ?? '', 'infinityfree') ||
    str_contains(__DIR__, 'if0_') ||
    str_contains(__DIR__, 'htdocs') && !str_contains(__DIR__, 'xampp') && !str_contains(__DIR__, 'WW')
);

if ($isLiveHosting) {
    // -------------------------------------------------------------
    // LIVE HOSTING CREDENTIALS (InfinityFree)
    // -------------------------------------------------------------
    defined('DB_HOST') or define('DB_HOST', 'sql312.infinityfree.com');
    defined('DB_NAME') or define('DB_NAME', 'if0_43363048_hotel_event_db');
    defined('DB_USER') or define('DB_USER', 'if0_43363048');
    defined('DB_PASS') or define('DB_PASS', '9Kk64T5iuXS');
    defined('DB_PORT') or define('DB_PORT', '3306');
} else {
    // -------------------------------------------------------------
    // LOCAL XAMPP / DOCKER / CLOUD ENVIRONMENT (Railway, Render, etc.)
    // -------------------------------------------------------------
    $cloudDbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
    if ($cloudDbUrl && ($urlParts = parse_url($cloudDbUrl))) {
        defined('DB_HOST') or define('DB_HOST', $urlParts['host'] ?? 'localhost');
        defined('DB_PORT') or define('DB_PORT', (string)($urlParts['port'] ?? 3306));
        defined('DB_USER') or define('DB_USER', $urlParts['user'] ?? 'root');
        defined('DB_PASS') or define('DB_PASS', $urlParts['pass'] ?? '');
        defined('DB_NAME') or define('DB_NAME', ltrim($urlParts['path'] ?? 'hotel_event_db', '/'));
    } elseif (getenv('MYSQLHOST')) {
        defined('DB_HOST') or define('DB_HOST', getenv('MYSQLHOST'));
        defined('DB_PORT') or define('DB_PORT', getenv('MYSQLPORT') ?: '3306');
        defined('DB_USER') or define('DB_USER', getenv('MYSQLUSER') ?: 'root');
        defined('DB_PASS') or define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
        defined('DB_NAME') or define('DB_NAME', getenv('MYSQLDATABASE') ?: 'railway');
    } else {
        defined('DB_HOST') or define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
        defined('DB_NAME') or define('DB_NAME', getenv('DB_NAME') ?: 'hotel_event_db');
        defined('DB_USER') or define('DB_USER', getenv('DB_USER') ?: 'root');
        defined('DB_PASS') or define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
        defined('DB_PORT') or define('DB_PORT', getenv('DB_PORT') ?: '3306');
    }
}

class Database {
    private static ?PDO $instance = null;

    /**
     * Get the active PDO instance (Singleton pattern)
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // If the database has truncated name if0_43363048_hotel_event_d, retry with that
                if (str_contains($e->getMessage(), 'Unknown database') && DB_NAME === 'if0_43363048_hotel_event_db') {
                    try {
                        $fallbackDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=if0_43363048_hotel_event_d;charset=utf8mb4";
                        self::$instance = new PDO($fallbackDsn, DB_USER, DB_PASS, $options);
                        return self::$instance;
                    } catch (Exception $e2) {
                        // ignore and proceed to main error handler
                    }
                }

                $msg = $e->getMessage();
                die("<div style='font-family:sans-serif; padding:20px; color:#721c24; background:#f8d7da; border-radius:6px; max-width:600px; margin:40px auto;'>
                    <h3 style='margin-top:0;'>Database Connection Notice</h3>
                    <p>Unable to connect to database: <code>" . htmlspecialchars($msg) . "</code></p>
                    <p>Please check that database details in <code>config/database.php</code> match your hosting control panel.</p>
                </div>");
            }
        }
        return self::$instance;
    }
}
