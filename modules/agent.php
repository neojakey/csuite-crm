<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/AgentSession.php';
require_once __DIR__ . '/../classes/Contact.php';

// Agent modes keyed by role
$agent_modes = [
    'CEO' => [
        'strategy'    => __( 'agents.mode.ceo.strategy' ),
        'sprint'      => __( 'agents.mode.ceo.sprint' ),
        'linkedin'    => __( 'agents.mode.ceo.linkedin' ),
        'partnership' => __( 'agents.mode.ceo.partnership' ),
        'positioning' => __( 'agents.mode.ceo.positioning' ),
    ],
    'CTO' => [
        'architecture' => __( 'agents.mode.cto.architecture' ),
        'code'         => __( 'agents.mode.cto.code' ),
        'security'     => __( 'agents.mode.cto.security' ),
        'debt'         => __( 'agents.mode.cto.debt' ),
        'agentic'      => __( 'agents.mode.cto.agentic' ),
    ],
    'CFO' => [
        'forecast' => __( 'agents.mode.cfo.forecast' ),
        'pricing'  => __( 'agents.mode.cfo.pricing' ),
        'cost'     => __( 'agents.mode.cfo.cost' ),
        'runway'   => __( 'agents.mode.cfo.runway' ),
        'decision' => __( 'agents.mode.cfo.decision' ),
    ],
    'CMO' => [
        'linkedin'  => __( 'agents.mode.cmo.linkedin' ),
        'email'     => __( 'agents.mode.cmo.email' ),
        'calendar'  => __( 'agents.mode.cmo.calendar' ),
        'seo'       => __( 'agents.mode.cmo.seo' ),
        'messaging' => __( 'agents.mode.cmo.messaging' ),
    ],
    'CPO' => [
        'feature'    => __( 'agents.mode.cpo.feature' ),
        'roadmap'    => __( 'agents.mode.cpo.roadmap' ),
        'feedback'   => __( 'agents.mode.cpo.feedback' ),
        'competitor' => __( 'agents.mode.cpo.competitor' ),
        'onboarding' => __( 'agents.mode.cpo.onboarding' ),
    ],
    'COO' => [
        'process'    => __( 'agents.mode.coo.process' ),
        'workflow'   => __( 'agents.mode.coo.workflow' ),
        'risk'       => __( 'agents.mode.coo.risk' ),
        'automation' => __( 'agents.mode.coo.automation' ),
        'priorities' => __( 'agents.mode.coo.priorities' ),
    ],
];

$roles          = array_keys( $agent_modes );
$active_role    = in_array( $_GET['role'] ?? '', $roles, true ) ? $_GET['role'] : 'CEO';
$contacts_list  = Contact::all_for_select();

$db = Database::getInstance();
$key_stmt = $db->query( "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('anthropic_api_key','gemini_api_key','perplexity_api_key')" );
$db_keys = [];
foreach ( $key_stmt->fetchAll() as $row ) {
    $db_keys[ $row['setting_key'] ] = $row['setting_value'];
}
$anthropic_key = $db_keys['anthropic_api_key'] ?? '';
if ( $anthropic_key === '' ) {
    $env_file = __DIR__ . '/../.env';
    $env      = file_exists( $env_file ) ? parse_ini_file( $env_file ) : [];
    $anthropic_key = $env['ANTHROPIC_API_KEY'] ?? '';
}

$available_providers = [];
if ( $anthropic_key !== '' ) $available_providers['claude'] = 'Claude';
if ( ($db_keys['gemini_api_key'] ?? '') !== '' ) $available_providers['gemini'] = 'Gemini';
if ( ($db_keys['perplexity_api_key'] ?? '') !== '' ) $available_providers['perplexity'] = 'Perplexity';

// If no providers are configured at all, just default to claude so the UI doesn't completely break
if ( empty($available_providers) ) {
    $available_providers['claude'] = 'Claude';
}

// Pre-load sessions for each role for the history panel
$sessions_by_role = [];
foreach ( $roles as $r ) {
    $sessions_by_role[ $r ] = AgentSession::by_role( $r, 10 );
}

$page_title = __( 'agents.title' );
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/nav.php';
?>
<main class="flex-1 md:ml-56 p-6 pt-20 md:pt-6">
    <h1 class="text-2xl font-bold mb-6"><?= __( 'agents.title' ) ?></h1>

    <!-- GDPR notice -->
    <div class="mb-4 px-4 py-3 bg-amber-500/10 border border-amber-500/30 rounded-md text-amber-400 text-sm">
        <?= __( 'agents.gdpr_notice' ) ?>
    </div>

    <!-- Role tabs -->
    <div class="flex flex-wrap gap-1 mb-6 border-b border-slate-700 pb-0">
        <?php if ( count($available_providers) > 1 ) : ?>
        <button class="agent-tab px-4 py-2 text-sm font-medium rounded-t-md transition-colors -mb-px border border-transparent <?= 'Boardroom' === $active_role ? 'bg-slate-800 border-slate-700 border-b-slate-800 text-cyan-400' : 'text-slate-400 hover:text-slate-100' ?>" data-role="Boardroom">
            Boardroom
        </button>
        <?php endif; ?>
        <?php foreach ( $roles as $role ) : ?>
        <button class="agent-tab px-4 py-2 text-sm font-medium rounded-t-md transition-colors -mb-px border border-transparent
                <?= $role === $active_role ? 'bg-slate-800 border-slate-700 border-b-slate-800 text-cyan-400' : 'text-slate-400 hover:text-slate-100' ?>"
                data-role="<?= $role ?>">
            <?= __( 'agents.role.' . $role ) ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Agent panels -->
    <?php foreach ( $roles as $role ) : ?>
    <div class="agent-panel <?= $role !== $active_role ? 'hidden' : '' ?>" data-role="<?= $role ?>">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- Input column -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Mode chips -->
                <div class="flex flex-wrap gap-2">
                    <?php foreach ( $agent_modes[ $role ] as $mode_key => $mode_label ) : ?>
                    <button class="mode-chip px-3 py-1 text-xs rounded-full border border-slate-600 text-slate-400 hover:border-cyan-500 hover:text-cyan-400 transition-colors"
                            data-mode="<?= $mode_key ?>"
                            data-role="<?= $role ?>">
                        <?= $mode_label ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- Prompt textarea -->
                <textarea id="agent-prompt-<?= $role ?>"
                          class="agent-prompt bg-slate-800 border border-slate-600 rounded-md px-3 py-2 text-sm text-slate-100 w-full focus:outline-none focus:border-cyan-500 resize-y h-36"
                          data-role="<?= $role ?>"
                          placeholder="<?= __( 'agents.placeholder' ) ?>"></textarea>

                <!-- Options row -->
                <div class="flex items-center gap-3">
                    <select class="agent-provider bg-slate-800 border border-slate-600 rounded-md px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-cyan-500" data-role="<?= $role ?>">
                        <?php foreach ( $available_providers as $pv_key => $pv_label ) : ?>
                        <option value="<?= $pv_key ?>"><?= $pv_label ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select class="agent-contact bg-slate-800 border border-slate-600 rounded-md px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-cyan-500"
                            data-role="<?= $role ?>">
                        <option value=""><?= __( 'ui.none' ) ?> (<?= __( 'contacts.title' ) ?>)</option>
                        <?php foreach ( $contacts_list as $c ) : ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars( $c['company_name'], ENT_QUOTES, 'UTF-8' ) ?><?= $c['contact_name'] ? ' — ' . htmlspecialchars( $c['contact_name'], ENT_QUOTES, 'UTF-8' ) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="run-agent-btn bg-cyan-500 hover:bg-cyan-400 text-slate-900 font-medium px-4 py-2 rounded-md text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            data-role="<?= $role ?>">
                        <?= __( 'agents.run' ) ?>
                    </button>
                </div>

                <!-- Loading state -->
                <div class="agent-loading hidden items-center gap-2 text-slate-400 text-sm" data-role="<?= $role ?>">
                    <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <?= __( 'agents.thinking' ) ?>
                </div>

                <!-- Error state -->
                <div class="agent-error hidden px-4 py-3 bg-red-500/10 border border-red-500/30 rounded-md text-red-400 text-sm" data-role="<?= $role ?>"></div>

                <!-- Output -->
                <div class="agent-output-wrap bg-slate-800 border border-slate-700 rounded-lg" data-role="<?= $role ?>">
                    <div class="flex items-center justify-between px-4 py-2 border-b border-slate-700">
                        <span class="text-xs text-slate-500 uppercase tracking-wide">Output</span>
                        <div class="flex gap-2">
                            <button class="copy-output-btn hidden text-xs text-slate-400 hover:text-cyan-400 transition-colors" data-role="<?= $role ?>"><?= __( 'agents.copy' ) ?></button>
                            <button class="save-task-btn hidden text-xs text-slate-400 hover:text-cyan-400 transition-colors" data-role="<?= $role ?>"><?= __( 'agents.save_task' ) ?></button>
                        </div>
                    </div>
                    <div class="agent-output px-4 py-3 text-sm text-slate-400 whitespace-pre-wrap min-h-[6rem]" data-role="<?= $role ?>"><?= __( 'agents.output_empty' ) ?></div>
                </div>
            </div>

            <!-- History column -->
            <div class="bg-slate-800 border border-slate-700 rounded-lg">
                <button class="history-toggle w-full flex items-center justify-between px-4 py-3 text-sm text-slate-400 hover:text-slate-100"
                        data-role="<?= $role ?>">
                    <span><?= __( 'agents.history' ) ?></span>
                    <svg class="w-4 h-4 transition-transform history-chevron" data-role="<?= $role ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="history-panel" data-role="<?= $role ?>">
                    <?php if ( empty( $sessions_by_role[ $role ] ) ) : ?>
                    <p class="px-4 pb-4 text-sm text-slate-500"><?= __( 'agents.no_history' ) ?></p>
                    <?php else : ?>
                    <div class="border-t border-slate-700 max-h-96 overflow-y-auto">
                        <?php foreach ( $sessions_by_role[ $role ] as $session ) : ?>
                        <div class="px-4 py-3 border-b border-slate-700/50 last:border-0 cursor-pointer hover:bg-slate-700/30 history-item"
                             data-prompt="<?= htmlspecialchars( $session['user_prompt'], ENT_QUOTES, 'UTF-8' ) ?>"
                             data-output="<?= htmlspecialchars( $session['agent_output'] ?? '', ENT_QUOTES, 'UTF-8' ) ?>"
                             data-role="<?= $role ?>">
                            <p class="text-xs text-slate-500 mb-1"><?= htmlspecialchars( $session['created_at'], ENT_QUOTES, 'UTF-8' ) ?><?= $session['company_name'] ? ' · ' . htmlspecialchars( $session['company_name'], ENT_QUOTES, 'UTF-8' ) : '' ?></p>
                            <p class="text-sm text-slate-300 truncate"><?= htmlspecialchars( $session['user_prompt'], ENT_QUOTES, 'UTF-8' ) ?></p>
                            <?php if ( $session['mode'] ) : ?>
                            <p class="text-xs text-cyan-500 mt-0.5"><?= htmlspecialchars( $session['mode'], ENT_QUOTES, 'UTF-8' ) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
    <?php endforeach; ?>

    <?php if ( count($available_providers) > 1 ) : ?>
    <!-- Boardroom Panel -->
    <div class="agent-panel <?= 'Boardroom' !== $active_role ? 'hidden' : '' ?>" data-role="Boardroom">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 space-y-4">
                <textarea id="agent-prompt-Boardroom"
                          class="agent-prompt bg-slate-800 border border-slate-600 rounded-md px-3 py-2 text-sm text-slate-100 w-full focus:outline-none focus:border-cyan-500 resize-y h-36"
                          data-role="Boardroom"
                          placeholder="State a problem for the Boardroom to debate..."></textarea>

                <div class="flex items-center gap-3">
                    <select class="agent-role bg-slate-800 border border-slate-600 rounded-md px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-cyan-500" data-role="Boardroom">
                        <?php foreach ( $roles as $r ) : ?>
                        <option value="<?= $r ?>"><?= __( 'agents.role.' . $r ) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button id="run-boardroom-btn" class="bg-cyan-500 hover:bg-cyan-400 text-slate-900 font-medium px-4 py-2 rounded-md text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Run Debate
                    </button>
                </div>

                <!-- Loading State -->
                <div id="boardroom-loading" class="hidden items-center gap-2 text-slate-400 text-sm">
                    <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span>Gathering perspectives...</span>
                </div>

                <!-- Error State -->
                <div id="boardroom-error" class="hidden px-4 py-3 bg-red-500/10 border border-red-500/30 rounded-md text-red-400 text-sm"></div>

                <!-- Boardroom Output Area -->
                <div id="boardroom-outputs" class="space-y-4 hidden">
                    <!-- Dynamic blocks -->
                </div>
            </div>
            
            <!-- Info column -->
            <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 h-fit">
                <h3 class="text-sm font-semibold text-slate-300 mb-2">Provider Debate</h3>
                <p class="text-xs text-slate-400 mb-4">Your prompt is sent to <?= implode(', ', $available_providers) ?> concurrently. Once all respond, Claude synthesizes their inputs into the optimal final answer.</p>
                <div id="boardroom-status-list" class="space-y-2 text-xs text-slate-500">
                    <?php foreach ( $available_providers as $pv_key => $pv_label ) : ?>
                    <div data-provider="<?= $pv_key ?>"><?= $pv_label ?>: Waiting</div>
                    <?php endforeach; ?>
                    <div data-provider="synthesis" class="font-semibold mt-2">Synthesis: Waiting</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<!-- Pass base URL and initial role to JS -->
<script>
    window.CSUITE = {
        baseUrl:    <?= json_encode( BASE_URL ) ?>,
        activeRole: <?= json_encode( $active_role ) ?>,
        availableProviders: <?= json_encode(array_keys($available_providers)) ?>
    };
</script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
