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

// Save API Keys
if ( $action === 'save_api_keys' ) {
    $anthropic  = $input['anthropic_api_key'] ?? '';
    $gemini     = $input['gemini_api_key'] ?? '';
    $perplexity = $input['perplexity_api_key'] ?? '';

    $stmt = $db->prepare( 'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)' );
    $stmt->execute( [ 'anthropic_api_key', $anthropic ] );
    $stmt->execute( [ 'gemini_api_key', $gemini ] );
    $stmt->execute( [ 'perplexity_api_key', $perplexity ] );

    echo json_encode( [ 'success' => true ] );
    exit;
}

// Test Anthropic connection
if ( $action === 'test_api' || $action === 'test_anthropic' ) {
    $stmt    = $db->prepare( 'SELECT setting_value FROM settings WHERE setting_key = ?' );
    $stmt->execute( [ 'anthropic_api_key' ] );
    $api_key = (string) $stmt->fetchColumn();

    // Fallback to .env
    if ( $api_key === '' ) {
        $env_file = __DIR__ . '/../.env';
        $env      = file_exists( $env_file ) ? parse_ini_file( $env_file ) : [];
        $api_key  = $env['ANTHROPIC_API_KEY'] ?? '';
    }

    if ( $api_key === '' ) {
        echo json_encode( [ 'success' => false, 'error' => 'No key set' ] );
        exit;
    }

    $payload = json_encode( [
        'model'      => 'claude-sonnet-4-20250514',
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
    $error   = $result['error']['message'] ?? 'Unknown error';

    if ($success) {
        echo json_encode( [ 'success' => true ] );
    } else {
        echo json_encode( [ 'success' => false, 'error' => $error ] );
    }
    exit;
}

// Test Gemini connection
if ( $action === 'test_gemini' ) {
    $stmt    = $db->prepare( 'SELECT setting_value FROM settings WHERE setting_key = ?' );
    $stmt->execute( [ 'gemini_api_key' ] );
    $api_key = (string) $stmt->fetchColumn();

    if ( $api_key === '' ) {
        echo json_encode( [ 'success' => false, 'error' => 'No key set' ] );
        exit;
    }

    $payload = json_encode( [
        'contents'         => [ [ 'role' => 'user', 'parts' => [ [ 'text' => 'ping' ] ] ] ],
        'generationConfig' => [ 'maxOutputTokens' => 10 ],
    ] );

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . urlencode( $api_key );
    $ch  = curl_init( $url );
    curl_setopt_array( $ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [ 'Content-Type: application/json' ],
        CURLOPT_TIMEOUT        => 15,
    ] );
    $response = curl_exec( $ch );
    curl_close( $ch );

    $result  = json_decode( $response, true );
    $success = isset( $result['candidates'][0]['content']['parts'][0]['text'] );
    $error   = $result['error']['message'] ?? 'Unknown error';

    if ($success) {
        echo json_encode( [ 'success' => true ] );
    } else {
        echo json_encode( [ 'success' => false, 'error' => $error ] );
    }
    exit;
}

// Test Perplexity connection
if ( $action === 'test_perplexity' ) {
    $stmt    = $db->prepare( 'SELECT setting_value FROM settings WHERE setting_key = ?' );
    $stmt->execute( [ 'perplexity_api_key' ] );
    $api_key = (string) $stmt->fetchColumn();

    if ( $api_key === '' ) {
        echo json_encode( [ 'success' => false, 'error' => 'No key set' ] );
        exit;
    }

    $payload = json_encode( [
        'model'    => 'sonar-pro',
        'messages' => [ [ 'role' => 'user', 'content' => 'ping' ] ],
        'max_tokens' => 10,
    ] );

    $ch = curl_init( 'https://api.perplexity.ai/chat/completions' );
    curl_setopt_array( $ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
        ],
        CURLOPT_TIMEOUT        => 15,
    ] );
    $response = curl_exec( $ch );
    curl_close( $ch );

    $result  = json_decode( $response, true );
    $success = isset( $result['choices'][0]['message']['content'] );
    $error   = $result['error']['message'] ?? 'Unknown error';

    if ($success) {
        echo json_encode( [ 'success' => true ] );
    } else {
        echo json_encode( [ 'success' => false, 'error' => $error ] );
    }
    exit;
}

http_response_code( 400 );
echo json_encode( [ 'error' => 'Unknown action' ] );
