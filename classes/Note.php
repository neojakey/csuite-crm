<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class Note {

    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function all( ?int $contact_id = null ): array {
        if ( $contact_id !== null ) {
            $stmt = self::db()->prepare(
                'SELECT n.*, c.company_name FROM notes n LEFT JOIN contacts c ON n.contact_id = c.id WHERE n.contact_id = ? ORDER BY n.created_at DESC'
            );
            $stmt->execute( [ $contact_id ] );
        } else {
            $stmt = self::db()->query(
                'SELECT n.*, c.company_name FROM notes n LEFT JOIN contacts c ON n.contact_id = c.id ORDER BY n.created_at DESC'
            );
        }
        return $stmt->fetchAll();
    }

    public static function find( int $id ): ?array {
        $stmt = self::db()->prepare(
            'SELECT n.*, c.company_name FROM notes n LEFT JOIN contacts c ON n.contact_id = c.id WHERE n.id = ?'
        );
        $stmt->execute( [ $id ] );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function save( array $data ): int {
        $title      = $data['title'] ?? '';
        $body       = $data['body'] ?? '';
        $contact_id = ! empty( $data['contact_id'] ) ? (int) $data['contact_id'] : null;

        if ( ! empty( $data['id'] ) ) {
            $stmt = self::db()->prepare( 'UPDATE notes SET title = ?, body = ?, contact_id = ? WHERE id = ?' );
            $stmt->execute( [ $title, $body, $contact_id, (int) $data['id'] ] );
            return (int) $data['id'];
        }

        $stmt = self::db()->prepare( 'INSERT INTO notes (title, body, contact_id) VALUES (?, ?, ?)' );
        $stmt->execute( [ $title, $body, $contact_id ] );
        return (int) self::db()->lastInsertId();
    }

    public static function delete( int $id ): void {
        $stmt = self::db()->prepare( 'DELETE FROM notes WHERE id = ?' );
        $stmt->execute( [ $id ] );
    }
}
