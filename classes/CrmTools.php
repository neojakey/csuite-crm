<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Contact.php';
require_once __DIR__ . '/Task.php';
require_once __DIR__ . '/AgentSession.php';

class CrmTools {

    private static array $read_only = ['get_contacts', 'get_contact', 'get_tasks', 'get_dashboard'];

    public static function label(string $name, array $input): string {
        return match ($name) {
            'get_contacts'   => 'Searching contacts…',
            'get_contact'    => 'Looking up contact…',
            'create_contact' => 'Creating contact ' . ($input['company_name'] ?? '') . '…',
            'update_contact' => 'Updating contact…',
            'delete_contact' => 'Deleting contact…',
            'get_tasks'      => 'Fetching tasks…',
            'create_task'    => 'Creating task: ' . ($input['title'] ?? '') . '…',
            'complete_task'  => 'Marking task done…',
            'create_note'    => 'Saving note…',
            'get_dashboard'  => 'Loading dashboard…',
            'run_agent'      => 'Running ' . strtoupper($input['role'] ?? '') . ' agent…',
            default          => $name . '…',
        };
    }

    public static function execute(string $name, array $input): array {
        $result = self::run($name, $input);
        if (! in_array($name, self::$read_only, true)) {
            self::log($name, $input, $result);
        }
        return $result;
    }

    public static function definitions(): array {
        return [
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
    }

    private static function run(string $name, array $input): array {
        $db = Database::getInstance();

        switch ($name) {

            case 'get_contacts': {
                $filters = [];
                if (! empty($input['search'])) $filters['search'] = $input['search'];
                if (! empty($input['status']))  $filters['status'] = $input['status'];
                $contacts = Contact::all($filters);
                return [ 'contacts' => $contacts, 'count' => count($contacts) ];
            }

            case 'get_contact': {
                $contact = Contact::find((int) ($input['id'] ?? 0));
                return $contact ? [ 'contact' => $contact ] : [ 'error' => 'Contact not found' ];
            }

            case 'create_contact': {
                if (empty($input['company_name'])) return [ 'error' => 'company_name is required' ];
                $allowed = [ 'company_name', 'contact_name', 'email', 'phone', 'website', 'source', 'status', 'pipeline_stage', 'notes' ];
                $data    = array_intersect_key($input, array_flip($allowed));
                $id      = Contact::save($data);
                return [ 'success' => true, 'id' => $id, 'message' => "Contact created (ID: {$id})" ];
            }

            case 'update_contact': {
                $id = (int) ($input['id'] ?? 0);
                if (! $id) return [ 'error' => 'id is required' ];
                if (! Contact::find($id)) return [ 'error' => 'Contact not found' ];
                $allowed    = [ 'company_name', 'contact_name', 'email', 'phone', 'website', 'source', 'status', 'pipeline_stage', 'notes' ];
                $data       = array_intersect_key($input, array_flip($allowed));
                $data['id'] = $id;
                Contact::save($data);
                return [ 'success' => true, 'message' => "Contact {$id} updated" ];
            }

            case 'delete_contact': {
                $id = (int) ($input['id'] ?? 0);
                if (! $id) return [ 'error' => 'id is required' ];
                Contact::delete($id);
                return [ 'success' => true, 'message' => "Contact {$id} deleted" ];
            }

            case 'get_tasks': {
                $open_only = $input['open_only'] ?? true;
                $tasks     = Task::all((bool) $open_only);
                if (! empty($input['contact_id'])) {
                    $cid   = (int) $input['contact_id'];
                    $tasks = array_values(array_filter($tasks, fn($t) => (int) $t['contact_id'] === $cid));
                }
                return [ 'tasks' => $tasks, 'count' => count($tasks) ];
            }

            case 'create_task': {
                if (empty($input['title'])) return [ 'error' => 'title is required' ];
                $id = Task::save([
                    'title'       => $input['title'],
                    'description' => $input['description'] ?? '',
                    'priority'    => $input['priority']    ?? 'medium',
                    'due_date'    => $input['due_date']    ?? null,
                    'contact_id'  => $input['contact_id']  ?? null,
                ]);
                return [ 'success' => true, 'id' => $id, 'message' => "Task created (ID: {$id})" ];
            }

            case 'complete_task': {
                $id = (int) ($input['id'] ?? 0);
                if (! $id) return [ 'error' => 'id is required' ];
                Task::mark_done($id);
                return [ 'success' => true, 'message' => "Task {$id} marked as done" ];
            }

            case 'create_note': {
                if (empty($input['body'])) return [ 'error' => 'body is required' ];
                $stmt = $db->prepare('INSERT INTO notes (title, body, contact_id) VALUES (?, ?, ?)');
                $stmt->execute([
                    $input['title']      ?? null,
                    $input['body'],
                    ! empty($input['contact_id']) ? (int) $input['contact_id'] : null,
                ]);
                $id = (int) $db->lastInsertId();
                return [ 'success' => true, 'id' => $id, 'message' => "Note created (ID: {$id})" ];
            }

            case 'get_dashboard': {
                $by_status  = Contact::count_by_status();
                $open_tasks = Task::open_count();
                $sessions   = AgentSession::recent(3);
                return [
                    'contacts_by_status' => $by_status,
                    'total_contacts'     => array_sum($by_status),
                    'open_tasks'         => $open_tasks,
                    'recent_sessions'    => $sessions,
                ];
            }

            case 'run_agent': {
                $role  = strtoupper($input['role'] ?? '');
                $valid = [ 'CEO', 'CTO', 'CFO', 'CMO', 'CPO', 'COO' ];
                if (! in_array($role, $valid, true)) return [ 'error' => 'Invalid role. Must be one of: ' . implode(', ', $valid) ];

                $prompt = trim($input['prompt'] ?? '');
                if ($prompt === '') return [ 'error' => 'prompt is required' ];

                $stmt_k = $db->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
                $stmt_k->execute([ 'anthropic_api_key' ]);
                $key = (string) $stmt_k->fetchColumn();
                if ($key === '') {
                    $env = file_exists(dirname(__DIR__) . '/.env') ? parse_ini_file(dirname(__DIR__) . '/.env') : [];
                    $key = $env['ANTHROPIC_API_KEY'] ?? '';
                }
                if ($key === '') return [ 'error' => 'No API key available for agent' ];

                $company_file = dirname(__DIR__) . '/config/company.php';
                $company_data = file_exists($company_file) ? require $company_file : [];
                $agent_system = "You are the {$role} of this company.";
                foreach ($company_data as $k => $v) {
                    $agent_system .= "\n- {$k}: {$v}";
                }

                $payload = json_encode([
                    'model'      => 'claude-sonnet-4-6',
                    'max_tokens' => 1000,
                    'system'     => $agent_system,
                    'messages'   => [ [ 'role' => 'user', 'content' => $prompt ] ],
                ]);

                $ch = curl_init('https://api.anthropic.com/v1/messages');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $payload,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'x-api-key: ' . $key,
                        'anthropic-version: 2023-06-01',
                    ],
                    CURLOPT_TIMEOUT => 90,
                ]);
                $resp   = curl_exec($ch);
                curl_close($ch);
                $decoded = json_decode($resp, true);
                $output  = $decoded['content'][0]['text'] ?? '';

                if ($output === '') {
                    return [ 'error' => $decoded['error']['message'] ?? 'Agent call failed' ];
                }

                AgentSession::save($role, 'chat', $prompt, $output, null, 'claude');
                return [ 'role' => $role, 'output' => $output ];
            }

            default:
                return [ 'error' => "Unknown tool: {$name}" ];
        }
    }

    private static function log(string $name, array $input, array $result): void {
        try {
            Database::getInstance()
                ->prepare('INSERT INTO assistant_actions (tool_name, tool_input, tool_result, success) VALUES (?, ?, ?, ?)')
                ->execute([
                    $name,
                    json_encode($input),
                    json_encode($result),
                    empty($result['error']) ? 1 : 0,
                ]);
        } catch (\Throwable) {
            // fail silently — table may not exist on older installs
        }
    }
}
