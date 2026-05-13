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

// ── Tool definitions ──────────────────────────────────────────────────────────
$tools = [
    [
        'name'         => 'get_contacts',
        'description'  => 'List or search contacts. Returns up to 20 results.',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [
                'search' => [ 'type' => 'string', 'description' => 'Search term matching company name, contact name, or email' ],
                'status' => [ 'type' => 'string', 'description' => 'Filter by status: prospect, warm, active, customer, dormant, lost' ],
            ],
        ],
    ],
    [
        'name'         => 'get_contact',
        'description'  => 'Get full details for a specific contact by ID.',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [ 'id' => [ 'type' => 'integer' ] ],
            'required'   => [ 'id' ],
        ],
    ],
    [
        'name'         => 'create_contact',
        'description'  => 'Create a new contact record.',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [
                'company_name'   => [ 'type' => 'string' ],
                'contact_name'   => [ 'type' => 'string' ],
                'email'          => [ 'type' => 'string' ],
                'phone'          => [ 'type' => 'string' ],
                'website'        => [ 'type' => 'string' ],
                'source'         => [ 'type' => 'string' ],
                'status'         => [ 'type' => 'string' ],
                'pipeline_stage' => [ 'type' => 'string' ],
                'notes'          => [ 'type' => 'string' ],
            ],
            'required' => [ 'company_name' ],
        ],
    ],
    [
        'name'         => 'update_contact',
        'description'  => 'Update one or more fields on an existing contact.',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [
                'id'             => [ 'type' => 'integer', 'description' => 'Contact ID to update' ],
                'company_name'   => [ 'type' => 'string' ],
                'contact_name'   => [ 'type' => 'string' ],
                'email'          => [ 'type' => 'string' ],
                'phone'          => [ 'type' => 'string' ],
                'website'        => [ 'type' => 'string' ],
                'source'         => [ 'type' => 'string' ],
                'status'         => [ 'type' => 'string' ],
                'pipeline_stage' => [ 'type' => 'string' ],
                'notes'          => [ 'type' => 'string' ],
            ],
            'required' => [ 'id' ],
        ],
    ],
    [
        'name'         => 'delete_contact',
        'description'  => 'Permanently delete a contact and all their data. Only call this after explicit user confirmation.',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [ 'id' => [ 'type' => 'integer' ] ],
            'required'   => [ 'id' ],
        ],
    ],
    [
        'name'         => 'get_tasks',
        'description'  => 'List tasks, optionally filtered by contact.',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [
                'open_only'  => [ 'type' => 'boolean', 'description' => 'If true, return only incomplete tasks (default true)' ],
                'contact_id' => [ 'type' => 'integer', 'description' => 'Filter tasks for a specific contact' ],
            ],
        ],
    ],
    [
        'name'         => 'create_task',
        'description'  => 'Create a new task, optionally linked to a contact.',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [
                'title'       => [ 'type' => 'string' ],
                'description' => [ 'type' => 'string' ],
                'priority'    => [ 'type' => 'string', 'description' => 'low, medium, or high' ],
                'due_date'    => [ 'type' => 'string', 'description' => 'YYYY-MM-DD' ],
                'contact_id'  => [ 'type' => 'integer' ],
            ],
            'required' => [ 'title' ],
        ],
    ],
    [
        'name'         => 'complete_task',
        'description'  => 'Mark a task as done.',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [ 'id' => [ 'type' => 'integer' ] ],
            'required'   => [ 'id' ],
        ],
    ],
    [
        'name'         => 'create_note',
        'description'  => 'Create a note, optionally linked to a contact.',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [
                'body'       => [ 'type' => 'string' ],
                'title'      => [ 'type' => 'string' ],
                'contact_id' => [ 'type' => 'integer' ],
            ],
            'required' => [ 'body' ],
        ],
    ],
    [
        'name'         => 'get_dashboard',
        'description'  => 'Get a CRM summary: contact counts by status, open task count, recent agent sessions.',
        'input_schema' => [ 'type' => 'object', 'properties' => [] ],
    ],
    [
        'name'         => 'run_agent',
        'description'  => 'Run a C-suite AI agent session and return the output. Saves the session to history.',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [
                'role'   => [ 'type' => 'string', 'description' => 'CEO, CTO, CFO, CMO, CPO, or COO' ],
                'prompt' => [ 'type' => 'string', 'description' => 'The question or context for the agent' ],
            ],
            'required' => [ 'role', 'prompt' ],
        ],
    ],
];

// ── Tool execution ────────────────────────────────────────────────────────────
function execute_tool( string $name, array $input ): array {
    $db = Database::getInstance();

    switch ( $name ) {

        case 'get_contacts': {
            $filters = [];
            if ( ! empty( $input['search'] ) ) $filters['search'] = $input['search'];
            if ( ! empty( $input['status'] ) )  $filters['status'] = $input['status'];
            $contacts = Contact::all( $filters );
            return [ 'contacts' => $contacts, 'count' => count( $contacts ) ];
        }

        case 'get_contact': {
            $contact = Contact::find( (int) ( $input['id'] ?? 0 ) );
            return $contact ? [ 'contact' => $contact ] : [ 'error' => 'Contact not found' ];
        }

        case 'create_contact': {
            if ( empty( $input['company_name'] ) ) return [ 'error' => 'company_name is required' ];
            $allowed = [ 'company_name', 'contact_name', 'email', 'phone', 'website', 'source', 'status', 'pipeline_stage', 'notes' ];
            $data    = array_intersect_key( $input, array_flip( $allowed ) );
            $id      = Contact::save( $data );
            return [ 'success' => true, 'id' => $id, 'message' => "Contact created (ID: {$id})" ];
        }

        case 'update_contact': {
            $id = (int) ( $input['id'] ?? 0 );
            if ( ! $id ) return [ 'error' => 'id is required' ];
            if ( ! Contact::find( $id ) ) return [ 'error' => 'Contact not found' ];
            $allowed = [ 'company_name', 'contact_name', 'email', 'phone', 'website', 'source', 'status', 'pipeline_stage', 'notes' ];
            $data    = array_intersect_key( $input, array_flip( $allowed ) );
            $data['id'] = $id;
            Contact::save( $data );
            return [ 'success' => true, 'message' => "Contact {$id} updated" ];
        }

        case 'delete_contact': {
            $id = (int) ( $input['id'] ?? 0 );
            if ( ! $id ) return [ 'error' => 'id is required' ];
            Contact::delete( $id );
            return [ 'success' => true, 'message' => "Contact {$id} deleted" ];
        }

        case 'get_tasks': {
            $open_only = $input['open_only'] ?? true;
            $tasks     = Task::all( (bool) $open_only );
            if ( ! empty( $input['contact_id'] ) ) {
                $cid   = (int) $input['contact_id'];
                $tasks = array_values( array_filter( $tasks, fn( $t ) => (int) $t['contact_id'] === $cid ) );
            }
            return [ 'tasks' => $tasks, 'count' => count( $tasks ) ];
        }

        case 'create_task': {
            if ( empty( $input['title'] ) ) return [ 'error' => 'title is required' ];
            $id = Task::save( [
                'title'       => $input['title'],
                'description' => $input['description'] ?? '',
                'priority'    => $input['priority']    ?? 'medium',
                'due_date'    => $input['due_date']    ?? null,
                'contact_id'  => $input['contact_id']  ?? null,
            ] );
            return [ 'success' => true, 'id' => $id, 'message' => "Task created (ID: {$id})" ];
        }

        case 'complete_task': {
            $id = (int) ( $input['id'] ?? 0 );
            if ( ! $id ) return [ 'error' => 'id is required' ];
            Task::mark_done( $id );
            return [ 'success' => true, 'message' => "Task {$id} marked as done" ];
        }

        case 'create_note': {
            if ( empty( $input['body'] ) ) return [ 'error' => 'body is required' ];
            $stmt = $db->prepare( 'INSERT INTO notes (title, body, contact_id) VALUES (?, ?, ?)' );
            $stmt->execute( [
                $input['title']      ?? null,
                $input['body'],
                ! empty( $input['contact_id'] ) ? (int) $input['contact_id'] : null,
            ] );
            $id = (int) $db->lastInsertId();
            return [ 'success' => true, 'id' => $id, 'message' => "Note created (ID: {$id})" ];
        }

        case 'get_dashboard': {
            $by_status   = Contact::count_by_status();
            $open_tasks  = Task::open_count();
            $sessions    = AgentSession::recent( 3 );
            return [
                'contacts_by_status' => $by_status,
                'total_contacts'     => array_sum( $by_status ),
                'open_tasks'         => $open_tasks,
                'recent_sessions'    => $sessions,
            ];
        }

        case 'run_agent': {
            $role  = strtoupper( $input['role'] ?? '' );
            $valid = [ 'CEO', 'CTO', 'CFO', 'CMO', 'CPO', 'COO' ];
            if ( ! in_array( $role, $valid, true ) ) return [ 'error' => 'Invalid role. Must be one of: ' . implode( ', ', $valid ) ];

            $prompt = trim( $input['prompt'] ?? '' );
            if ( $prompt === '' ) return [ 'error' => 'prompt is required' ];

            // Fetch API key
            $stmt_k  = $db->prepare( 'SELECT setting_value FROM settings WHERE setting_key = ?' );
            $stmt_k->execute( [ 'anthropic_api_key' ] );
            $key     = (string) $stmt_k->fetchColumn();
            if ( $key === '' ) {
                $env = file_exists( __DIR__ . '/../.env' ) ? parse_ini_file( __DIR__ . '/../.env' ) : [];
                $key = $env['ANTHROPIC_API_KEY'] ?? '';
            }
            if ( $key === '' ) return [ 'error' => 'No API key available for agent' ];

            $company_file = __DIR__ . '/../config/company.php';
            $company_data = file_exists( $company_file ) ? require $company_file : [];
            $agent_system = "You are the {$role} of this company.";
            foreach ( $company_data as $k => $v ) {
                $agent_system .= "\n- {$k}: {$v}";
            }

            $payload = json_encode( [
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => 1000,
                'system'     => $agent_system,
                'messages'   => [ [ 'role' => 'user', 'content' => $prompt ] ],
            ] );

            $ch = curl_init( 'https://api.anthropic.com/v1/messages' );
            curl_setopt_array( $ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'x-api-key: ' . $key,
                    'anthropic-version: 2023-06-01',
                ],
                CURLOPT_TIMEOUT => 90,
            ] );
            $resp   = curl_exec( $ch );
            curl_close( $ch );
            $result = json_decode( $resp, true );
            $output = $result['content'][0]['text'] ?? '';

            if ( $output === '' ) {
                return [ 'error' => $result['error']['message'] ?? 'Agent call failed' ];
            }

            AgentSession::save( $role, 'chat', $prompt, $output, null, 'claude' );
            return [ 'role' => $role, 'output' => $output ];
        }

        default:
            return [ 'error' => "Unknown tool: {$name}" ];
    }
}

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
        $result_data    = execute_tool( $tu['name'], $tu['input'] ?? [] );
        $tool_results[] = [
            'type'        => 'tool_result',
            'tool_use_id' => $tu['id'],
            'content'     => json_encode( $result_data ),
        ];
    }

    $history[] = [ 'role' => 'user', 'content' => $tool_results ];
}

echo json_encode( [ 'success' => true, 'reply' => $reply_text ] );
