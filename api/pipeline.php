<?php
declare(strict_types=1);

session_set_cookie_params( [ 'lifetime' => 28800, 'httponly' => true, 'samesite' => 'Strict' ] );
session_start();

header( 'Content-Type: application/json' );

if ( empty( $_SESSION['authenticated'] ) ) {
    http_response_code( 401 );
    echo json_encode( [ 'error' => 'Unauthorised' ] );
    exit;
}

$input  = json_decode( file_get_contents( 'php://input' ), true ) ?? [];
$action = $input['action'] ?? '';

require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

$valid_stages = [ '', 'lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost' ];

if ( $action === 'move' ) {
    $contact_id = isset( $input['contact_id'] ) ? (int) $input['contact_id'] : 0;
    $stage      = $input['stage'] ?? '';

    if ( $contact_id <= 0 || ! in_array( $stage, $valid_stages, true ) ) {
        http_response_code( 400 );
        echo json_encode( [ 'error' => 'Invalid parameters' ] );
        exit;
    }

    $stmt = $db->prepare( 'UPDATE contacts SET pipeline_stage = ? WHERE id = ?' );
    $stmt->execute( [ $stage, $contact_id ] );
    echo json_encode( [ 'success' => true ] );
    exit;
}

http_response_code( 400 );
echo json_encode( [ 'error' => 'Unknown action' ] );
