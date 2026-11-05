<?php
/**
 * Database Configuration & PDO Connection Singleton
 * Hotel Event Management System
 * Supports Local XAMPP and Cloud Hosting Environments (Railway, Render, InfinityFree, Heroku, etc.)
 */

// Check for cloud URL formats (e.g. mysql://user:pass@host:port/dbname)
$cloudDbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL') ?: getenv('JAWSDB_URL') ?: getenv('CLEARDB_DATABASE_URL');

if ($cloudDbUrl && ($urlParts = parse_url($cloudDbUrl))) {
    defined('DB_HOST') or define('DB_HOST', $urlParts['host'] ?? 'localhost');
    defined('DB_PORT') or define('DB_PORT', (string)($urlParts['port'] ?? 3306));
    defined('DB_USER') or define('DB_USER', $urlParts['user'] ?? 'root');
    defined('DB_PASS') or define('DB_PASS', $urlParts['pass'] ?? '');
    defined('DB_NAME') or define('DB_NAME', ltrim($urlParts['path'] ?? 'hotel_event_db', '/'));
} else {
    // Individual environment variables or standard XAMPP defaults
    defined('DB_HOST') or define('DB_HOST', getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'localhost');
    defined('DB_NAME') or define('DB_NAME', getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'hotel_event_db');
    defined('DB_USER') or define('DB_USER', getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root');
    defined('DB_PASS') or define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : ''));
    defined('DB_PORT') or define('DB_PORT', getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306');
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
                $msg = $e->getMessage();
                if (str_contains($msg, 'Unknown database')) {
                    die("
                        <div style='font-family:sans-serif; max-width:650px; margin:50px auto; padding:25px; border:1px solid #e0c280; background:#fffcf5; border-radius:8px;'>
                            <h2 style='color:#0d1b2a; margin-top:0;'>Hotel Event Management System - Setup Required</h2>
                            <p>The database <code>" . htmlspecialchars(DB_NAME) . "</code> does not exist yet.</p>
                            <p>You can automatically initialize the database by running the web installer:</p>
                            <p><a href='install.php' style='display:inline-block; padding:10px 20px; background:#0d1b2a; color:#c5a880; text-decoration:none; border-radius:5px; font-weight:bold;'>Run 1-Click Database Installer (install.php)</a></p>
                            <p style='color:#666; font-size:13px;'>Or import <code>database/schema.sql</code> and <code>database/seed_data.sql</code> via phpMyAdmin.</p>
                        </div>
                    ");
                }
                die("<div style='font-family:sans-serif; padding:20px; color:#721c24; background:#f8d7da;'>Database Connection Error: " . htmlspecialchars($msg) . "</div>");
            }
        }
        return self::$instance;
    }
}
