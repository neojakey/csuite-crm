<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Contact.php';
require_once __DIR__ . '/../classes/AgentSession.php';
require_once __DIR__ . '/../classes/Task.php';

$pdo = db();

// Load settings
$stmt     = $pdo->query( 'SELECT setting_key, setting_value FROM settings' );
$settings = [];
foreach ( $stmt->fetchAll() as $row ) {
    $settings[ $row['setting_key'] ] = $row['setting_value'];
}

$sprint_week       = (int) ( $settings['sprint_week']        ?? 1 );
$sprint_total      = (int) ( $settings['sprint_total_weeks'] ?? 12 );
$checkpoint_date   = $settings['checkpoint_date']   ?? '';
$chk_inbound       = (int) ( $settings['checkpoint_inbound'] ?? 0 );
$chk_product       = (int) ( $settings['checkpoint_product'] ?? 0 );
$chk_energy        = (int) ( $settings['checkpoint_energy']  ?? 0 );

$contacts_by_status = Contact::count_by_status();
$total_contacts     = array_sum( $contacts_by_status );
$recent_sessions    = AgentSession::recent( 5 );
$open_tasks         = Task::all( true );

$page_title = __( 'dashboard.title' );
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/nav.php';
?>

<main class="flex-1 md:ml-56 p-6 pt-20 md:pt-6">
    <?php if ( $flash = get_flash( 'success' ) ) : ?>
    <div class="mb-4 px-4 py-3 bg-green-500/10 border border-green-500/30 rounded-md text-green-400 text-sm flash-message">
        <?= $flash ?>
    </div>
    <?php endif; ?>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-100"><?= __( 'dashboard.title' ) ?></h1>
    </div>

    <!-- Top row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        <!-- Sprint card -->
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-5">
            <p class="text-xs uppercase tracking-wider text-slate-500 mb-1"><?= __( 'dashboard.sprint_week' ) ?></p>
            <p class="text-4xl font-bold text-cyan-400"><?= $sprint_week ?><span class="text-lg text-slate-500"> / <?= $sprint_total ?></span></p>
            <div class="mt-3 bg-slate-700 rounded-full h-1.5">
                <div class="bg-cyan-500 h-1.5 rounded-full" style="width: <?= min( 100, round( $sprint_week / max( 1, $sprint_total ) * 100 ) ) ?>%"></div>
            </div>
        </div>

        <!-- Checkpoint card -->
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-5">
            <p class="text-xs uppercase tracking-wider text-slate-500 mb-1"><?= __( 'dashboard.checkpoint' ) ?></p>
            <?php if ( $checkpoint_date ) : ?>
            <p class="text-sm text-slate-300 mb-3"><?= htmlspecialchars( $checkpoint_date, ENT_QUOTES, 'UTF-8' ) ?></p>
            <?php endif; ?>
            <div class="space-y-2">
                <?php
                $criteria = [
                    'checkpoint_inbound' => [ 'label' => __( 'dashboard.criteria.inbound' ), 'value' => $chk_inbound ],
                    'checkpoint_product' => [ 'label' => __( 'dashboard.criteria.product' ), 'value' => $chk_product ],
                    'checkpoint_energy'  => [ 'label' => __( 'dashboard.criteria.energy' ),  'value' => $chk_energy ],
                ];
                foreach ( $criteria as $key => $c ) : ?>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-400"><?= $c['label'] ?></span>
                    <button class="checkpoint-toggle w-10 h-5 rounded-full transition-colors <?= $c['value'] ? 'bg-cyan-500' : 'bg-slate-600' ?>"
                            data-key="<?= $key ?>"
                            aria-label="Toggle <?= $c['label'] ?>">
                        <span class="block w-4 h-4 bg-white rounded-full shadow transition-transform mx-0.5 <?= $c['value'] ? 'translate-x-5' : 'translate-x-0' ?>"></span>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CRM summary -->
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-5">
            <p class="text-xs uppercase tracking-wider text-slate-500 mb-3"><?= __( 'dashboard.crm_summary' ) ?></p>
            <p class="text-3xl font-bold text-slate-100 mb-3"><?= $total_contacts ?></p>
            <div class="space-y-1">
                <?php
                $statuses = [ 'prospect', 'warm', 'active', 'customer', 'dormant', 'lost' ];
                foreach ( $statuses as $s ) :
                    $cnt = $contacts_by_status[ $s ] ?? 0;
                    if ( $cnt === 0 ) continue;
                ?>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400"><?= __( 'contacts.status.' . $s ) ?></span>
                    <span class="text-slate-300 font-medium"><?= $cnt ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Bottom row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- Recent sessions -->
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-slate-300"><?= __( 'dashboard.recent_sessions' ) ?></h2>
                <a href="<?= BASE_URL ?>index.php?page=agent" class="text-xs text-cyan-500 hover:text-cyan-400"><?= __( 'ui.view' ) ?></a>
            </div>
            <?php if ( empty( $recent_sessions ) ) : ?>
            <p class="text-sm text-slate-500"><?= __( 'dashboard.no_sessions' ) ?></p>
            <?php else : ?>
            <div class="space-y-2">
                <?php foreach ( $recent_sessions as $session ) : ?>
                <div class="flex items-start gap-3 py-2 border-b border-slate-700/50 last:border-0">
                    <span class="inline-block px-2 py-0.5 text-xs font-medium bg-cyan-500/20 text-cyan-400 rounded"><?= htmlspecialchars( $session['agent_role'], ENT_QUOTES, 'UTF-8' ) ?></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-300 truncate"><?= htmlspecialchars( $session['user_prompt'], ENT_QUOTES, 'UTF-8' ) ?></p>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars( $session['created_at'], ENT_QUOTES, 'UTF-8' ) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Open tasks -->
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-slate-300"><?= __( 'dashboard.open_tasks' ) ?></h2>
                <a href="<?= BASE_URL ?>index.php?page=tasks" class="text-xs text-cyan-500 hover:text-cyan-400"><?= __( 'ui.view' ) ?></a>
            </div>
            <?php if ( empty( $open_tasks ) ) : ?>
            <p class="text-sm text-slate-500"><?= __( 'dashboard.no_tasks' ) ?></p>
            <?php else : ?>
            <div class="space-y-2">
                <?php foreach ( array_slice( $open_tasks, 0, 5 ) as $task ) :
                    $priority_colours = [ 'high' => 'bg-red-500/20 text-red-400', 'medium' => 'bg-amber-500/20 text-amber-400', 'low' => 'bg-slate-600/50 text-slate-400' ];
                    $pc = $priority_colours[ $task['priority'] ] ?? 'bg-slate-600/50 text-slate-400';
                ?>
                <div class="flex items-center gap-3 py-2 border-b border-slate-700/50 last:border-0">
                    <span class="inline-block px-2 py-0.5 text-xs rounded <?= $pc ?>"><?= __( 'tasks.priority.' . $task['priority'] ) ?></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-300 truncate"><?= htmlspecialchars( $task['title'], ENT_QUOTES, 'UTF-8' ) ?></p>
                        <?php if ( $task['due_date'] ) : ?>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars( $task['due_date'], ENT_QUOTES, 'UTF-8' ) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick launch -->
    <div class="mt-4 bg-slate-800 border border-slate-700 rounded-lg p-5">
        <h2 class="text-sm font-semibold text-slate-300 mb-4"><?= __( 'dashboard.quick_launch' ) ?></h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ( [ 'CEO', 'CTO', 'CFO', 'CMO', 'CPO', 'COO' ] as $role ) : ?>
            <a href="<?= BASE_URL ?>index.php?page=agent&role=<?= $role ?>"
               class="bg-slate-700 hover:bg-slate-600 text-slate-100 px-4 py-2 rounded-md text-sm transition-colors">
                <?= __( 'agents.role.' . $role ) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
