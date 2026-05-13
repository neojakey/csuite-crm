<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class AgentSession {

    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function save(
        string $role,
        string $mode,
        string $prompt,
        string $output,
        ?int $contact_id = null,
        string $provider = 'claude'
    ): int {
        $stmt = self::db()->prepare(
            'INSERT INTO agent_sessions (agent_role, mode, provider, user_prompt, agent_output, contact_id) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute( [ $role, $mode, $provider, $prompt, $output, $contact_id ] );
        return (int) self::db()->lastInsertId();
    }

    public static function recent( int $limit = 5 ): array {
        $stmt = self::db()->prepare(
            'SELECT s.*, c.company_name FROM agent_sessions s LEFT JOIN contacts c ON s.contact_id = c.id ORDER BY s.created_at DESC LIMIT ?'
        );
        $stmt->execute( [ $limit ] );
        return $stmt->fetchAll();
    }

    public static function by_role( string $role, int $limit = 10 ): array {
        $stmt = self::db()->prepare(
            'SELECT s.*, c.company_name FROM agent_sessions s LEFT JOIN contacts c ON s.contact_id = c.id WHERE s.agent_role = ? ORDER BY s.created_at DESC LIMIT ?'
        );
        $stmt->execute( [ $role, $limit ] );
        return $stmt->fetchAll();
    }

    public static function find( int $id ): ?array {
        $stmt = self::db()->prepare(
            'SELECT s.*, c.company_name FROM agent_sessions s LEFT JOIN contacts c ON s.contact_id = c.id WHERE s.id = ?'
        );
        $stmt->execute( [ $id ] );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function by_contact( int $contact_id ): array {
        $stmt = self::db()->prepare(
            'SELECT * FROM agent_sessions WHERE contact_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute( [ $contact_id ] );
        return $stmt->fetchAll();
    }
}
