<?php
/*
 * Database connection settings.
 * Replace these with the details from your hosting control panel
 * (for example InfinityFree: "MySQL Databases" page).
 */
define('DB_HOST', getenv('HH_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('HH_DB_NAME') ?: 'harvest_home');
define('DB_USER', getenv('HH_DB_USER') ?: 'root');
define('DB_PASS', getenv('HH_DB_PASS') !== false ? getenv('HH_DB_PASS') : '');

// Flat delivery fee for the Western Cape (Rands)
define('DELIVERY_FEE', 50.00);

// South African local time, used by PHP and by the database session
date_default_timezone_set('Africa/Johannesburg');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec("SET time_zone = '+02:00'");
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Sorry, the shop is unavailable right now. Please try again later.');
        }
    }
    return $pdo;
}
