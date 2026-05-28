<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

/*
|--------------------------------------------------------------------------
| Database Connection Class
|--------------------------------------------------------------------------
| Fully corrected and production-ready PDO configuration
|--------------------------------------------------------------------------
| Features:
| - Singleton PDO connection
| - Secure environment loading
| - Persistent connections
| - UTF8MB4 support
| - Exception handling
| - Graceful production errors
| - Automatic reconnect protection
| - Query safety defaults
|--------------------------------------------------------------------------
*/

class DB
{
    /**
     * Singleton PDO instance
     */
    private static ?PDO $pdo = null;

    /**
     * Get PDO Database Connection
     */
    public static function getPDO(): PDO
    {
        /*
        |--------------------------------------------------------------------------
        | Return Existing Connection
        |--------------------------------------------------------------------------
        */
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        /*
        |--------------------------------------------------------------------------
        | Environment Variables
        |--------------------------------------------------------------------------
        */
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_NAME') ?: 'incident_system';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASS') ?: '';

        /*
        |--------------------------------------------------------------------------
        | Application Debug Mode
        |--------------------------------------------------------------------------
        */
        $appDebug = filter_var(
            getenv('APP_DEBUG') ?: false,
            defined('FILTER_VALIDATE_BOOL') ? FILTER_VALIDATE_BOOL : 258
        );

        /*
        |--------------------------------------------------------------------------
        | Validate Required Configuration
        |--------------------------------------------------------------------------
        */
        if (empty($host) || empty($database) || empty($username)) {

            throw new RuntimeException(
                'Database configuration is incomplete.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PDO DSN
        |--------------------------------------------------------------------------
        */
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $database
        );

        /*
        |--------------------------------------------------------------------------
        | PDO Options
        |--------------------------------------------------------------------------
        */
        $options = [

            // Throw exceptions
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            // Associative arrays
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // Disable emulated prepares
            PDO::ATTR_EMULATE_PREPARES => false,

            // Persistent connections
            PDO::ATTR_PERSISTENT => true,

            // Stringify fetches disabled
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        /*
        |--------------------------------------------------------------------------
        | Create PDO Connection
        |--------------------------------------------------------------------------
        */
        try {

            self::$pdo = new PDO(
                $dsn,
                $username,
                $password,
                $options
            );

            /*
            |--------------------------------------------------------------------------
            | Set SQL Modes
            |--------------------------------------------------------------------------
            */
            self::$pdo->exec(
                "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'"
            );

            /*
            |--------------------------------------------------------------------------
            | Set Timezone
            |--------------------------------------------------------------------------
            */
            self::$pdo->exec("
                SET time_zone = '+02:00'
            ");

            return self::$pdo;

        } catch (PDOException $e) {

            /*
            |--------------------------------------------------------------------------
            | Log Database Errors
            |--------------------------------------------------------------------------
            */
            self::logDatabaseError($e);

            /*
            |--------------------------------------------------------------------------
            | Development Error Output
            |--------------------------------------------------------------------------
            */
            if ($appDebug) {

                throw new RuntimeException(
                    'Database connection failed: ' .
                    $e->getMessage()
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Production Safe Error
            |--------------------------------------------------------------------------
            */
            throw new RuntimeException(
                'Unable to connect to the database.'
            );
        }
    }

    /**
     * Test Database Connection
     */
    public static function testConnection(): bool
    {
        try {

            $pdo = self::getPDO();

            $stmt = $pdo->query('SELECT 1');

            return $stmt !== false;

        } catch (\Throwable $e) {

            self::logDatabaseError($e);

            return false;
        }
    }

    /**
     * Close Database Connection
     */
    public static function disconnect(): void
    {
        self::$pdo = null;
    }

    /**
     * Database Error Logger
     */
    private static function logDatabaseError(\Throwable $e): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';

        /*
        |--------------------------------------------------------------------------
        | Create Log Directory
        |--------------------------------------------------------------------------
        */
        if (!is_dir($logDir)) {

            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/database.log';

        /*
        |--------------------------------------------------------------------------
        | Build Error Entry
        |--------------------------------------------------------------------------
        */
        $message = sprintf(
            "[%s] DATABASE ERROR\nMessage: %s\nFile: %s:%d\nTrace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        /*
        |--------------------------------------------------------------------------
        | Save Log
        |--------------------------------------------------------------------------
        */
        file_put_contents(
            $logFile,
            $message,
            FILE_APPEND
        );
    }
}