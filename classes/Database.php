<?php
declare(strict_types=1);

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if ( self::$instance === null ) {
            $env_file = __DIR__ . '/../.env';
            $env      = file_exists( $env_file ) ? parse_ini_file( $env_file ) : [];

            $host = $env['DB_HOST'] ?? 'localhost';
            $name = $env['DB_NAME'] ?? 'csuite_crm';
            $user = $env['DB_USER'] ?? 'root';
            $pass = $env['DB_PASS'] ?? '';

            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

            self::$instance = new PDO( $dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ] );
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}
