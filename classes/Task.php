<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class Task {

    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function all( bool $open_only = true ): array {
        if ( $open_only ) {
            $stmt = self::db()->query(
                "SELECT t.*, c.company_name FROM tasks t
                 LEFT JOIN contacts c ON t.contact_id = c.id
                 WHERE t.status != 'done'
                 ORDER BY FIELD(t.priority,'high','medium','low'), t.due_date ASC, t.created_at DESC"
            );
        } else {
            $stmt = self::db()->query(
                "SELECT t.*, c.company_name FROM tasks t
                 LEFT JOIN contacts c ON t.contact_id = c.id
                 ORDER BY FIELD(t.priority,'high','medium','low'), t.due_date ASC, t.created_at DESC"
            );
        }
        return $stmt->fetchAll();
    }

    public static function open_for_contact( int $contact_id ): array {
        $stmt = self::db()->prepare(
            "SELECT * FROM tasks WHERE contact_id = ? ORDER BY FIELD(priority,'high','medium','low'), due_date ASC"
        );
        $stmt->execute( [ $contact_id ] );
        return $stmt->fetchAll();
    }

    public static function find( int $id ): ?array {
        $stmt = self::db()->prepare(
            'SELECT t.*, c.company_name FROM tasks t LEFT JOIN contacts c ON t.contact_id = c.id WHERE t.id = ?'
        );
        $stmt->execute( [ $id ] );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function save( array $data ): int {
        $title      = $data['title'] ?? '';
        $desc       = $data['description'] ?? '';
        $status     = $data['status'] ?? 'todo';
        $priority   = $data['priority'] ?? 'medium';
        $due_date   = ! empty( $data['due_date'] ) ? $data['due_date'] : null;
        $contact_id = ! empty( $data['contact_id'] ) ? (int) $data['contact_id'] : null;
        $session_id = ! empty( $data['agent_session_id'] ) ? (int) $data['agent_session_id'] : null;

        if ( ! empty( $data['id'] ) ) {
            $stmt = self::db()->prepare(
                'UPDATE tasks SET title = ?, description = ?, status = ?, priority = ?, due_date = ?, contact_id = ?, agent_session_id = ? WHERE id = ?'
            );
            $stmt->execute( [ $title, $desc, $status, $priority, $due_date, $contact_id, $session_id, (int) $data['id'] ] );
            return (int) $data['id'];
        }

        $stmt = self::db()->prepare(
            'INSERT INTO tasks (title, description, status, priority, due_date, contact_id, agent_session_id) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute( [ $title, $desc, $status, $priority, $due_date, $contact_id, $session_id ] );
        return (int) self::db()->lastInsertId();
    }

    public static function mark_done( int $id ): void {
        $stmt = self::db()->prepare( "UPDATE tasks SET status = 'done' WHERE id = ?" );
        $stmt->execute( [ $id ] );
    }

    public static function delete( int $id ): void {
        $stmt = self::db()->prepare( 'DELETE FROM tasks WHERE id = ?' );
        $stmt->execute( [ $id ] );
    }

    public static function open_count(): int {
        $stmt = self::db()->query( "SELECT COUNT(*) FROM tasks WHERE status != 'done'" );
        return (int) $stmt->fetchColumn();
    }
}
