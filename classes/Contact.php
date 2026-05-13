<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class Contact {

    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function all( array $filters = [], int $page = 1, int $per_page = 20 ): array {
        $where  = [];
        $params = [];

        if ( ! empty( $filters['status'] ) ) {
            $where[]  = 'status = ?';
            $params[] = $filters['status'];
        }

        if ( ! empty( $filters['search'] ) ) {
            $where[]  = '(company_name LIKE ? OR contact_name LIKE ? OR email LIKE ?)';
            $s        = '%' . $filters['search'] . '%';
            $params   = array_merge( $params, [ $s, $s, $s ] );
        }

        $sql = 'SELECT * FROM contacts';
        if ( $where ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where );
        }
        $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $params[] = $per_page;
        $params[] = ( $page - 1 ) * $per_page;

        $stmt = self::db()->prepare( $sql );
        $stmt->execute( $params );
        return $stmt->fetchAll();
    }

    public static function count( array $filters = [] ): int {
        $where  = [];
        $params = [];

        if ( ! empty( $filters['status'] ) ) {
            $where[]  = 'status = ?';
            $params[] = $filters['status'];
        }

        if ( ! empty( $filters['search'] ) ) {
            $where[]  = '(company_name LIKE ? OR contact_name LIKE ? OR email LIKE ?)';
            $s        = '%' . $filters['search'] . '%';
            $params   = array_merge( $params, [ $s, $s, $s ] );
        }

        $sql = 'SELECT COUNT(*) FROM contacts';
        if ( $where ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where );
        }

        $stmt = self::db()->prepare( $sql );
        $stmt->execute( $params );
        return (int) $stmt->fetchColumn();
    }

    public static function find( int $id ): ?array {
        $stmt = self::db()->prepare( 'SELECT * FROM contacts WHERE id = ?' );
        $stmt->execute( [ $id ] );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function save( array $data ): int {
        $allowed = [ 'company_name', 'contact_name', 'email', 'phone', 'website', 'source', 'status', 'pipeline_stage', 'notes' ];
        $fields  = array_intersect_key( $data, array_flip( $allowed ) );

        if ( ! empty( $data['id'] ) ) {
            $sets   = implode( ', ', array_map( fn( $k ) => "{$k} = ?", array_keys( $fields ) ) );
            $values = array_values( $fields );
            $values[] = (int) $data['id'];
            $stmt   = self::db()->prepare( "UPDATE contacts SET {$sets} WHERE id = ?" );
            $stmt->execute( $values );
            return (int) $data['id'];
        }

        $cols         = implode( ', ', array_keys( $fields ) );
        $placeholders = implode( ', ', array_fill( 0, count( $fields ), '?' ) );
        $stmt         = self::db()->prepare( "INSERT INTO contacts ({$cols}) VALUES ({$placeholders})" );
        $stmt->execute( array_values( $fields ) );
        return (int) self::db()->lastInsertId();
    }

    public static function delete( int $id ): void {
        $stmt = self::db()->prepare( 'DELETE FROM contacts WHERE id = ?' );
        $stmt->execute( [ $id ] );
    }

    public static function count_by_status(): array {
        $stmt   = self::db()->query( 'SELECT status, COUNT(*) as cnt FROM contacts GROUP BY status' );
        $result = [];
        foreach ( $stmt->fetchAll() as $row ) {
            $result[ $row['status'] ] = (int) $row['cnt'];
        }
        return $result;
    }

    public static function all_for_select(): array {
        $stmt = self::db()->query( 'SELECT id, company_name, contact_name FROM contacts ORDER BY company_name ASC' );
        return $stmt->fetchAll();
    }
}
