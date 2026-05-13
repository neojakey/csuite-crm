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

// Toggle checkpoint criteria
if ( $action === 'toggle_checkpoint' ) {
    $key     = $input['key'] ?? '';
    $allowed = [ 'checkpoint_inbound', 'checkpoint_product', 'checkpoint_energy' ];

    if ( ! in_array( $key, $allowed, true ) ) {
        http_response_code( 400 );
        echo json_encode( [ 'error' => 'Invalid key' ] );
        exit;
    }

    $stmt    = $db->prepare( 'SELECT setting_value FROM settings WHERE setting_key = ?' );
    $stmt->execute( [ $key ] );
    $current = (int) $stmt->fetchColumn();
    $new_val = $current ? '0' : '1';

    $stmt = $db->prepare( 'UPDATE settings SET setting_value = ? WHERE setting_key = ?' );
    $stmt->execute( [ $new_val, $key ] );

    echo json_encode( [ 'success' => true, 'value' => (int) $new_val ] );
    exit;
}

// Test API connection
if ( $action === 'test_api' ) {
    $env_file = __DIR__ . '/../.env';
    $env      = file_exists( $env_file ) ? parse_ini_file( $env_file ) : [];
    $api_key  = $env['ANTHROPIC_API_KEY'] ?? '';

    if ( $api_key === '' ) {
        echo json_encode( [ 'success' => false ] );
        exit;
    }

    $payload = json_encode( [
        'model'      => 'claude-haiku-4-5-20251001',
        'max_tokens' => 10,
        'messages'   => [ [ 'role' => 'user', 'content' => 'ping' ] ],
    ] );

    $ch = curl_init( 'https://api.anthropic.com/v1/messages' );
    curl_setopt_array( $ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT => 15,
    ] );
    $response = curl_exec( $ch );
    curl_close( $ch );

    $result  = json_decode( $response, true );
    $success = isset( $result['content'][0]['text'] );

    echo json_encode( [ 'success' => $success ] );
    exit;
}

http_response_code( 400 );
echo json_encode( [ 'error' => 'Unknown action' ] );
