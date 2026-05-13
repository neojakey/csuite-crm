<?php
declare(strict_types=1);

session_set_cookie_params( [ 'lifetime' => 28800, 'httponly' => true, 'samesite' => 'Strict' ] );
session_start();

if ( empty( $_SESSION['authenticated'] ) ) {
    header( 'Content-Type: text/event-stream' );
    echo 'data: ' . json_encode( [ 'type' => 'error', 'message' => 'Unauthorised' ] ) . "\n\n";
    exit;
}

$input   = json_decode( file_get_contents( 'php://input' ), true ) ?? [];
$message = trim( $input['message'] ?? '' );

if ( $message === '' ) {
    header( 'Content-Type: text/event-stream' );
    echo 'data: ' . json_encode( [ 'type' => 'error', 'message' => 'Empty message.' ] ) . "\n\n";
    exit;
}

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Contact.php';
require_once __DIR__ . '/../classes/Task.php';
require_once __DIR__ . '/../classes/AgentSession.php';
require_once __DIR__ . '/../classes/CrmTools.php';

// Load API key
$db   = Database::getInstance();
$stmt = $db->prepare( 'SELECT setting_value FROM settings WHERE setting_key = ?' );
$stmt->execute( [ 'anthropic_api_key' ] );
$api_key = (string) $stmt->fetchColumn();

if ( $api_key === '' ) {
    $env     = file_exists( __DIR__ . '/../.env' ) ? parse_ini_file( __DIR__ . '/../.env' ) : [];
    $api_key = $env['ANTHROPIC_API_KEY'] ?? '';
}

// Conversation history
if ( ! isset( $_SESSION['chat_history'] ) ) {
    $_SESSION['chat_history'] = [];
}
$history   = &$_SESSION['chat_history'];
$history[] = [ 'role' => 'user', 'content' => $message ];

if ( count( $history ) > 40 ) {
    $history = array_slice( $history, -40 );
    while ( ! empty( $history ) && ! is_string( $history[0]['content'] ) ) {
        array_shift( $history );
    }
}

// System prompt
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

// ── Start SSE output ──────────────────────────────────────────────────────────
header( 'Content-Type: text/event-stream' );
header( 'Cache-Control: no-cache' );
header( 'X-Accel-Buffering: no' );
while ( ob_get_level() ) ob_end_flush();
ob_implicit_flush( true );

if ( $api_key === '' ) {
    echo 'data: ' . json_encode( [ 'type' => 'error', 'message' => 'No Anthropic API key configured. Add one in Settings → API Keys.' ] ) . "\n\n";
    exit;
}

// ── Agentic streaming loop ────────────────────────────────────────────────────
$max_iterations = 6;

for ( $iteration = 0; $iteration < $max_iterations; $iteration++ ) {

    $round = [
        'sse_buf'     => '',
        'blocks'      => [],
        'stop_reason' => '',
    ];

    $write_fn = function ( $ch, $chunk ) use ( &$round ): int {
        $round['sse_buf'] .= $chunk;

        while ( ( $nl = strpos( $round['sse_buf'], "\n" ) ) !== false ) {
            $line            = rtrim( substr( $round['sse_buf'], 0, $nl ), "\r" );
            $round['sse_buf'] = substr( $round['sse_buf'], $nl + 1 );

            if ( ! str_starts_with( $line, 'data: ' ) ) continue;
            $data = substr( $line, 6 );
            if ( $data === '[DONE]' ) continue;

            $ev = json_decode( $data, true );
            if ( ! $ev || ! isset( $ev['type'] ) ) continue;

            switch ( $ev['type'] ) {

                case 'content_block_start':
                    $block = $ev['content_block'];
                    if ( $block['type'] === 'tool_use' ) {
                        $block['input_raw'] = '';
                    }
                    $round['blocks'][ $ev['index'] ] = $block;
                    break;

                case 'content_block_delta':
                    $idx   = $ev['index'];
                    $delta = $ev['delta'];
                    if ( $delta['type'] === 'text_delta' ) {
                        $round['blocks'][$idx]['text'] = ( $round['blocks'][$idx]['text'] ?? '' ) . $delta['text'];
                        echo 'data: ' . json_encode( [ 'type' => 'token', 'text' => $delta['text'] ] ) . "\n\n";
                        flush();
                    } elseif ( $delta['type'] === 'input_json_delta' ) {
                        $round['blocks'][$idx]['input_raw'] = ( $round['blocks'][$idx]['input_raw'] ?? '' ) . $delta['partial_json'];
                    }
                    break;

                case 'content_block_stop':
                    $idx = $ev['index'];
                    if ( isset( $round['blocks'][$idx] ) && $round['blocks'][$idx]['type'] === 'tool_use' ) {
                        $round['blocks'][$idx]['input'] = json_decode(
                            $round['blocks'][$idx]['input_raw'] ?? '{}', true
                        ) ?? [];
                    }
                    break;

                case 'message_delta':
                    if ( ! empty( $ev['delta']['stop_reason'] ) ) {
                        $round['stop_reason'] = $ev['delta']['stop_reason'];
                    }
                    break;
            }
        }

        return strlen( $chunk );
    };

    $payload = json_encode( [
        'model'      => 'claude-sonnet-4-6',
        'max_tokens' => 1024,
        'stream'     => true,
        'system'     => $system,
        'tools'      => CrmTools::definitions(),
        'messages'   => $history,
    ] );

    $ch = curl_init( 'https://api.anthropic.com/v1/messages' );
    curl_setopt_array( $ch, [
        CURLOPT_POST          => true,
        CURLOPT_POSTFIELDS    => $payload,
        CURLOPT_HTTPHEADER    => [
            'Content-Type: application/json',
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_WRITEFUNCTION => $write_fn,
        CURLOPT_TIMEOUT       => 90,
    ] );

    curl_exec( $ch );
    $curl_err = curl_error( $ch );
    curl_close( $ch );

    if ( $curl_err ) {
        echo 'data: ' . json_encode( [ 'type' => 'error', 'message' => 'Network error contacting Anthropic API.' ] ) . "\n\n";
        exit;
    }

    // Reconstruct content array for history
    $content = [];
    ksort( $round['blocks'] );
    foreach ( $round['blocks'] as $block ) {
        if ( $block['type'] === 'tool_use' ) {
            $content[] = [
                'type'  => 'tool_use',
                'id'    => $block['id'],
                'name'  => $block['name'],
                'input' => $block['input'] ?? [],
            ];
        } else {
            $content[] = [ 'type' => 'text', 'text' => $block['text'] ?? '' ];
        }
    }

    $history[] = [ 'role' => 'assistant', 'content' => $content ];

    $tool_uses = array_values( array_filter( $content, fn( $b ) => $b['type'] === 'tool_use' ) );

    if ( empty( $tool_uses ) || $round['stop_reason'] === 'end_turn' ) {
        echo 'data: ' . json_encode( [ 'type' => 'done' ] ) . "\n\n";
        exit;
    }

    // Execute tools and stream labels
    $tool_results = [];
    foreach ( $tool_uses as $tu ) {
        echo 'data: ' . json_encode( [
            'type'  => 'tool',
            'name'  => $tu['name'],
            'label' => CrmTools::label( $tu['name'], $tu['input'] ),
        ] ) . "\n\n";
        flush();

        $result_data    = CrmTools::execute( $tu['name'], $tu['input'] );
        $tool_results[] = [
            'type'        => 'tool_result',
            'tool_use_id' => $tu['id'],
            'content'     => json_encode( $result_data ),
        ];
    }

    $history[] = [ 'role' => 'user', 'content' => $tool_results ];
}

echo 'data: ' . json_encode( [ 'type' => 'done' ] ) . "\n\n";
