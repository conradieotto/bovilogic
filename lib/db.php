<?php
/**
 * BoviLogic – PDO Database Singleton
 */

require_once __DIR__ . '/config.php';

class DB {
    private static ?PDO $pdo = null;

    public static function conn(): PDO {
        if (self::$pdo === null) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                if (BL_DEBUG) {
                    die('DB Connection failed: ' . $e->getMessage());
                }
                http_response_code(500);
                die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
            }
        }
        return self::$pdo;
    }

    /** Run a SELECT query, return all rows */
    public static function rows(string $sql, array $params = []): array {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Run a SELECT query, return single row */
    public static function row(string $sql, array $params = []): ?array {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Run a SELECT query, return single column value */
    public static function val(string $sql, array $params = []): mixed {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /** Execute INSERT/UPDATE/DELETE, return affected rows */
    public static function exec(string $sql, array $params = []): int {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** Execute INSERT, return last insert ID */
    public static function insert(string $sql, array $params = []): int {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        return (int) self::conn()->lastInsertId();
    }

    public static function beginTransaction(): void { self::conn()->beginTransaction(); }
    public static function commit(): void           { self::conn()->commit(); }
    public static function rollback(): void         { self::conn()->rollBack(); }

    /** Generate a UUID v4 */
    public static function uuid(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
