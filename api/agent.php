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

$input    = json_decode( file_get_contents( 'php://input' ), true ) ?? [];
$role     = $input['role']     ?? '';
$mode     = $input['mode']     ?? '';
$prompt   = trim( $input['prompt'] ?? '' );
$provider = in_array( $input['provider'] ?? '', [ 'claude', 'gemini', 'perplexity' ], true )
    ? $input['provider']
    : 'claude';

$allowed_roles = [ 'CEO', 'CTO', 'CFO', 'CMO', 'CPO', 'COO' ];
if ( ! in_array( $role, $allowed_roles, true ) ) {
    http_response_code( 400 );
    echo json_encode( [ 'error' => 'Invalid role' ] );
    exit;
}

if ( $prompt === '' ) {
    http_response_code( 400 );
    echo json_encode( [ 'error' => 'Prompt is required' ] );
    exit;
}

// Load API keys from DB (fallback to .env for Anthropic)
require_once __DIR__ . '/../classes/Database.php';

$db       = Database::getInstance();
$key_stmt = $db->query( "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('anthropic_api_key','gemini_api_key','perplexity_api_key')" );
$db_keys  = [];
foreach ( $key_stmt->fetchAll() as $row ) {
    $db_keys[ $row['setting_key'] ] = $row['setting_value'];
}

$anthropic_key  = $db_keys['anthropic_api_key'] ?? '';
$gemini_key     = $db_keys['gemini_api_key']    ?? '';
$perplexity_key = $db_keys['perplexity_api_key'] ?? '';

// Fall back to .env for Anthropic if DB key is empty
if ( $anthropic_key === '' ) {
    $env_file      = __DIR__ . '/../.env';
    $env           = file_exists( $env_file ) ? parse_ini_file( $env_file ) : [];
    $anthropic_key = $env['ANTHROPIC_API_KEY'] ?? '';
}

// Load company context
$company_file = __DIR__ . '/../config/company.php';
$company      = file_exists( $company_file ) ? require $company_file : [];
$context      = "COMPANY CONTEXT (provided by the operator):\n";
foreach ( $company as $key => $value ) {
    $label    = ucwords( str_replace( '_', ' ', $key ) );
    $context .= '- ' . $label . ': ' . htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ) . "\n";
}

// Language
$lang_names = [ 'en' => 'English', 'es' => 'Spanish' ];
$lang_name  = $lang_names[ $_SESSION['lang'] ?? 'en' ] ?? 'English';

// System prompts
$system_prompts = [
    'CEO' => 'You are an experienced CEO and strategic advisor. You have been given the company context above. Think at the level of vision, market positioning, competitive strategy, and long-term direction. Be direct and specific — name actual strategic moves, not generic frameworks. Output a clear recommendation or deliverable, then a section labelled "Strategic rationale" with your reasoning. Plain language, no buzzwords.',

    'CTO' => 'You are an experienced CTO and software architect. You have been given the company context above including the tech stack. Think about architecture quality, security, scalability, technical debt, and engineering decisions. Give concrete, actionable guidance — name specific patterns, flag specific risks, suggest specific implementations. Output a technical recommendation or code review, then a section labelled "Technical rationale".',

    'CFO' => 'You are an experienced CFO and financial strategist. You have been given the company context above including revenue model and MRR. Think about unit economics, runway, pricing strategy, cost structure, and the financial logic behind decisions. Be specific — use the numbers provided, model scenarios, flag risks. Output a financial analysis or recommendation, then a section labelled "Financial rationale". You provide analysis and options, not regulated financial advice.',

    'CMO' => 'You are an experienced CMO and B2B marketing strategist. You have been given the company context above including target audience, channels, and competitors. Think about messaging, content strategy, demand generation, and brand positioning. Produce specific, usable output — not generic advice. Output a ready-to-use deliverable (post draft, campaign brief, content calendar, or messaging framework), then a section labelled "Marketing rationale". Plain language, no buzzwords.',

    'CPO' => 'You are an experienced CPO and product strategist. You have been given the company context above including key features and current challenges. Think about product-market fit, feature prioritisation, user retention, and the product roadmap. Be specific — describe features as user stories or specifications. Flag implementation complexity honestly. Output a product recommendation or feature specification, then a section labelled "Product rationale".',

    'COO' => 'You are an experienced COO and operational leader. You have been given the company context above including team size, key processes, and current priorities. Think about process design, operational efficiency, systems, automations, and execution risk. Give concrete, implementable recommendations — not theory. Output an operational plan or process design, then a section labelled "Operational rationale". Plain language.',
];

$system = $context . "\n\n" . $system_prompts[ $role ];
$system .= "\n\nRespond entirely in {$lang_name}. Do not switch languages mid-response.";

$mode_label   = $mode !== '' ? "[Mode: {$mode}]\n\n" : '';
$user_message = $mode_label . $prompt;

// ── Route to provider ─────────────────────────────────────────────────────────

if ( $provider === 'gemini' ) {

    if ( $gemini_key === '' ) {
        http_response_code( 500 );
        echo json_encode( [ 'error' => 'Gemini API key not configured. Add it in Settings → API Keys.' ] );
        exit;
    }

    $payload = json_encode( [
        'system_instruction' => [ 'parts' => [ [ 'text' => $system ] ] ],
        'contents'           => [ [ 'role' => 'user', 'parts' => [ [ 'text' => $user_message ] ] ] ],
        'generationConfig'   => [ 'maxOutputTokens' => 2000 ],
    ] );

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . urlencode( $gemini_key );
    $ch  = curl_init( $url );
    curl_setopt_array( $ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [ 'Content-Type: application/json' ],
        CURLOPT_TIMEOUT        => 90,
    ] );

    $response  = curl_exec( $ch );
    $curl_err  = curl_error( $ch );
    curl_close( $ch );

    if ( $curl_err ) {
        http_response_code( 502 );
        echo json_encode( [ 'error' => 'Network error contacting Gemini API.' ] );
        exit;
    }

    $result = json_decode( $response, true );
    $output = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

    if ( $output === '' ) {
        $api_err = $result['error']['message'] ?? 'Unknown Gemini API error';
        http_response_code( 502 );
        echo json_encode( [ 'error' => $api_err ] );
        exit;
    }

} elseif ( $provider === 'perplexity' ) {

    if ( $perplexity_key === '' ) {
        http_response_code( 500 );
        echo json_encode( [ 'error' => 'Perplexity API key not configured. Add it in Settings → API Keys.' ] );
        exit;
    }

    $payload = json_encode( [
        'model'      => 'sonar-pro',
        'messages'   => [
            [ 'role' => 'system', 'content' => $system ],
            [ 'role' => 'user', 'content' => $user_message ]
        ],
        'max_tokens' => 2000,
    ] );

    $ch = curl_init( 'https://api.perplexity.ai/chat/completions' );
    curl_setopt_array( $ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $perplexity_key,
        ],
        CURLOPT_TIMEOUT        => 90,
    ] );

    $response  = curl_exec( $ch );
    $curl_err  = curl_error( $ch );
    curl_close( $ch );

    if ( $curl_err ) {
        http_response_code( 502 );
        echo json_encode( [ 'error' => 'Network error contacting Perplexity API.' ] );
        exit;
    }

    $result = json_decode( $response, true );
    $output = $result['choices'][0]['message']['content'] ?? '';

    if ( $output === '' ) {
        $api_err = $result['error']['message'] ?? 'Unknown Perplexity API error';
        http_response_code( 502 );
        echo json_encode( [ 'error' => $api_err ] );
        exit;
    }

} else {

    if ( $anthropic_key === '' ) {
        http_response_code( 500 );
        echo json_encode( [ 'error' => 'Anthropic API key not configured. Add it in Settings → API Keys.' ] );
        exit;
    }

    $payload = json_encode( [
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 2000,
        'system'     => $system,
        'messages'   => [ [ 'role' => 'user', 'content' => $user_message ] ],
    ] );

    $ch = curl_init( 'https://api.anthropic.com/v1/messages' );
    curl_setopt_array( $ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $anthropic_key,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT        => 90,
    ] );

    $response  = curl_exec( $ch );
    $curl_err  = curl_error( $ch );
    curl_close( $ch );

    if ( $curl_err ) {
        http_response_code( 502 );
        echo json_encode( [ 'error' => 'Network error contacting Anthropic API.' ] );
        exit;
    }

    $result = json_decode( $response, true );
    $output = $result['content'][0]['text'] ?? '';

    if ( $output === '' ) {
        $api_err = $result['error']['message'] ?? 'Unknown API error';
        http_response_code( 502 );
        echo json_encode( [ 'error' => $api_err ] );
        exit;
    }
}

// Save session
require_once __DIR__ . '/../classes/AgentSession.php';

$contact_id = isset( $input['contact_id'] ) && (int) $input['contact_id'] > 0
    ? (int) $input['contact_id']
    : null;

$session_id = AgentSession::save( $role, $mode, $prompt, $output, $contact_id, $provider );

echo json_encode( [ 'success' => true, 'output' => $output, 'session_id' => $session_id, 'provider' => $provider ] );
