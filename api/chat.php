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

$input   = json_decode( file_get_contents( 'php://input' ), true ) ?? [];
$action  = $input['action'] ?? 'chat';

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Contact.php';
require_once __DIR__ . '/../classes/Task.php';
require_once __DIR__ . '/../classes/AgentSession.php';
require_once __DIR__ . '/../classes/CrmTools.php';

if ( $action === 'clear' ) {
    $_SESSION['chat_history'] = [];
    echo json_encode( [ 'success' => true ] );
    exit;
}

$message = trim( $input['message'] ?? '' );
if ( $message === '' ) {
    echo json_encode( [ 'error' => 'Empty message.' ] );
    exit;
}

// Load API key
$db      = Database::getInstance();
$stmt    = $db->prepare( 'SELECT setting_value FROM settings WHERE setting_key = ?' );
$stmt->execute( [ 'anthropic_api_key' ] );
$api_key = (string) $stmt->fetchColumn();

if ( $api_key === '' ) {
    $env      = file_exists( __DIR__ . '/../.env' ) ? parse_ini_file( __DIR__ . '/../.env' ) : [];
    $api_key  = $env['ANTHROPIC_API_KEY'] ?? '';
}

if ( $api_key === '' ) {
    echo json_encode( [ 'error' => 'No Anthropic API key configured. Add one in Settings → API Keys.' ] );
    exit;
}

// ── Conversation history ──────────────────────────────────────────────────────
if ( ! isset( $_SESSION['chat_history'] ) ) {
    $_SESSION['chat_history'] = [];
}
$history = &$_SESSION['chat_history'];

$history[] = [ 'role' => 'user', 'content' => $message ];

// Trim history: keep the last 40 messages, always starting on a clean user text turn
if ( count( $history ) > 40 ) {
    $history = array_slice( $history, -40 );
    while ( ! empty( $history ) && ! is_string( $history[0]['content'] ) ) {
        array_shift( $history );
    }
}

// ── System prompt ─────────────────────────────────────────────────────────────
$company_file = __DIR__ . '/../config/company.php';
$company      = file_exists( $company_file ) ? require $company_file : [];
$company_ctx  = '';
if ( ! empty( $company ) ) {
    $lines       = array_map( fn( $k, $v ) => "- {$k}: {$v}", array_keys( $company ), $company );
    $company_ctx = "\n\nOperator company context:\n" . implode( "\n", $lines );
}

$system = <<<SYSTEM
You are an intelligent CRM assistant embedded in csuite-crm, a self-hosted CRM for B2B sales and founder-led teams. You have direct control over the CRM and can perform any action on behalf of the user using the tools provided.{$company_ctx}

Current date: DATE_PLACEHOLDER

## What you can do

**Contacts** — list, search, view, create, update, delete contacts.
- Fields: company_name (required), contact_name, email, phone, website, source, status, pipeline_stage, notes
- Valid status values: prospect, warm, active, customer, dormant, lost
- Valid source values: linkedin, referral, outbound, inbound, event, other
- Valid pipeline_stage values: lead, qualified, proposal, negotiation, won, lost

**Tasks** — list open tasks, create tasks, mark tasks done.
- Fields: title (required), description, priority (low/medium/high), due_date (YYYY-MM-DD), contact_id

**Notes** — create notes, optionally linked to a contact.
- Fields: body (required), title, contact_id

**AI Agents** — run a C-suite agent session (CEO, CTO, CFO, CMO, CPO, COO) with a prompt.

**Dashboard** — get a summary of CRM state.

## Behaviour rules
- Be concise. Confirm what you did, not what you plan to do.
- Always use tools to perform actions — never just describe them.
- If a required field is missing, ask for it before calling the tool.
- For destructive actions (delete contact), ask the user to confirm before calling the tool.
- You may chain multiple tool calls to fulfil a single request.
- When listing results, summarise them in a readable way — don't dump raw data.
SYSTEM;

$system = str_replace( 'DATE_PLACEHOLDER', date( 'Y-m-d' ), $system );

$tools = CrmTools::definitions();

// ── Agentic loop ──────────────────────────────────────────────────────────────
$max_iterations = 6;
$iteration      = 0;
$reply_text     = '';

while ( $iteration < $max_iterations ) {
    $iteration++;

    $payload = json_encode( [
        'model'      => 'claude-sonnet-4-6',
        'max_tokens' => 1024,
        'system'     => $system,
        'tools'      => $tools,
        'messages'   => $history,
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
        CURLOPT_TIMEOUT => 90,
    ] );

    $response = curl_exec( $ch );
    $curl_err = curl_error( $ch );
    curl_close( $ch );

    if ( $curl_err ) {
        echo json_encode( [ 'error' => 'Network error contacting Anthropic API.' ] );
        exit;
    }

    $result      = json_decode( $response, true );
    $stop_reason = $result['stop_reason'] ?? '';
    $content     = $result['content']     ?? [];

    if ( isset( $result['error'] ) ) {
        echo json_encode( [ 'error' => $result['error']['message'] ?? 'API error' ] );
        exit;
    }

    // Append assistant turn to history
    $history[] = [ 'role' => 'assistant', 'content' => $content ];

    $tool_uses = array_values( array_filter( $content, fn( $b ) => $b['type'] === 'tool_use' ) );

    if ( empty( $tool_uses ) || $stop_reason === 'end_turn' ) {
        foreach ( $content as $block ) {
            if ( $block['type'] === 'text' ) {
                $reply_text = $block['text'];
                break;
            }
        }
        break;
    }

    // Execute tools and collect results
    $tool_results = [];
    foreach ( $tool_uses as $tu ) {
        $result_data    = CrmTools::execute( $tu['name'], $tu['input'] ?? [] );
        $tool_results[] = [
            'type'        => 'tool_result',
            'tool_use_id' => $tu['id'],
            'content'     => json_encode( $result_data ),
        ];
    }

    $history[] = [ 'role' => 'user', 'content' => $tool_results ];
}

echo json_encode( [ 'success' => true, 'reply' => $reply_text ] );
