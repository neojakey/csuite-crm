<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Database.php';

$canonical_stages = ['lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];

$db   = Database::getInstance();
$stmt = $db->query( 'SELECT id, company_name, contact_name, status, pipeline_stage FROM contacts ORDER BY company_name ASC' );
$all  = $stmt->fetchAll();

$grouped    = array_fill_keys( $canonical_stages, [] );
$unassigned = [];

foreach ( $all as $contact ) {
    $s = $contact['pipeline_stage'] ?? '';
    if ( in_array( $s, $canonical_stages, true ) ) {
        $grouped[ $s ][] = $contact;
    } else {
        $unassigned[] = $contact;
    }
}

$stage_meta = [
    'lead'        => [ 'dot' => 'bg-slate-400',  'label' => 'text-slate-300' ],
    'qualified'   => [ 'dot' => 'bg-blue-400',   'label' => 'text-blue-400' ],
    'proposal'    => [ 'dot' => 'bg-amber-400',  'label' => 'text-amber-400' ],
    'negotiation' => [ 'dot' => 'bg-purple-400', 'label' => 'text-purple-400' ],
    'won'         => [ 'dot' => 'bg-green-400',  'label' => 'text-green-400' ],
    'lost'        => [ 'dot' => 'bg-red-400',    'label' => 'text-red-400' ],
];

$status_colors = [
    'prospect' => 'bg-slate-600/50 text-slate-400',
    'warm'     => 'bg-amber-500/20 text-amber-400',
    'active'   => 'bg-cyan-500/20 text-cyan-400',
    'customer' => 'bg-green-500/20 text-green-400',
    'dormant'  => 'bg-slate-700/50 text-slate-500',
    'lost'     => 'bg-red-500/20 text-red-400',
];

$page_title = __( 'pipeline.title' );
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/nav.php';
?>

<style>
.pipeline-drop-zone.drag-over {
    background-color: rgba(51, 65, 85, 0.4);
    outline: 1px dashed #475569;
    border-radius: 0.5rem;
}
</style>

<main class="flex-1 md:ml-56 p-6 pt-20 md:pt-6 overflow-hidden">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-100"><?= __( 'pipeline.title' ) ?></h1>
    </div>

    <div class="flex gap-4 overflow-x-auto pb-6 -mx-6 px-6">

        <?php if ( ! empty( $unassigned ) ) : ?>
        <!-- Unassigned column -->
        <div class="pipeline-column flex-none w-60" data-stage="">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wide"><?= __( 'pipeline.stage.unassigned' ) ?></h2>
                <span class="pipeline-count text-xs text-slate-500 ml-1"><?= count( $unassigned ) ?></span>
            </div>
            <div class="pipeline-drop-zone min-h-20 space-y-2 rounded-lg p-1" data-stage="">
                <?php foreach ( $unassigned as $c ) :
                    $sc = $status_colors[ $c['status'] ] ?? 'bg-slate-600/50 text-slate-400';
                ?>
                <div class="pipeline-card bg-slate-800 border border-slate-700 rounded-lg p-3 cursor-grab select-none hover:border-slate-600 transition-colors"
                     draggable="true"
                     data-id="<?= (int) $c['id'] ?>">
                    <p class="text-sm font-medium text-slate-100 truncate"><?= e( $c['company_name'] ) ?></p>
                    <?php if ( $c['contact_name'] ) : ?>
                    <p class="text-xs text-slate-400 truncate mt-0.5"><?= e( $c['contact_name'] ) ?></p>
                    <?php endif; ?>
                    <div class="flex items-center justify-between mt-2">
                        <span class="inline-block px-1.5 py-0.5 text-[10px] rounded <?= $sc ?>"><?= e( $c['status'] ) ?></span>
                        <a href="<?= e( BASE_URL . 'index.php?page=contacts&action=view&id=' . (int) $c['id'] ) ?>" class="text-[10px] text-cyan-500 hover:text-cyan-400">View →</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php foreach ( $canonical_stages as $stage ) :
            $meta     = $stage_meta[ $stage ];
            $contacts = $grouped[ $stage ];
        ?>
        <div class="pipeline-column flex-none w-60" data-stage="<?= $stage ?>">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2 h-2 rounded-full <?= $meta['dot'] ?>"></span>
                <h2 class="text-xs font-semibold <?= $meta['label'] ?> uppercase tracking-wide"><?= __( 'pipeline.stage.' . $stage ) ?></h2>
                <span class="pipeline-count text-xs text-slate-500 ml-1"><?= count( $contacts ) ?></span>
            </div>
            <div class="pipeline-drop-zone min-h-20 space-y-2 rounded-lg p-1" data-stage="<?= $stage ?>">
                <?php foreach ( $contacts as $c ) :
                    $sc = $status_colors[ $c['status'] ] ?? 'bg-slate-600/50 text-slate-400';
                ?>
                <div class="pipeline-card bg-slate-800 border border-slate-700 rounded-lg p-3 cursor-grab select-none hover:border-slate-600 transition-colors"
                     draggable="true"
                     data-id="<?= (int) $c['id'] ?>">
                    <p class="text-sm font-medium text-slate-100 truncate"><?= e( $c['company_name'] ) ?></p>
                    <?php if ( $c['contact_name'] ) : ?>
                    <p class="text-xs text-slate-400 truncate mt-0.5"><?= e( $c['contact_name'] ) ?></p>
                    <?php endif; ?>
                    <div class="flex items-center justify-between mt-2">
                        <span class="inline-block px-1.5 py-0.5 text-[10px] rounded <?= $sc ?>"><?= e( $c['status'] ) ?></span>
                        <a href="<?= e( BASE_URL . 'index.php?page=contacts&action=view&id=' . (int) $c['id'] ) ?>" class="text-[10px] text-cyan-500 hover:text-cyan-400">View →</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</main>

<script>
(function () {
    var dragged    = null;
    var originZone = null;

    document.querySelectorAll('.pipeline-card').forEach(function (card) {
        card.addEventListener('dragstart', function (e) {
            dragged    = this;
            originZone = this.closest('.pipeline-drop-zone');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.id);
            setTimeout(function () { dragged.classList.add('opacity-40'); }, 0);
        });
        card.addEventListener('dragend', function () {
            this.classList.remove('opacity-40');
        });
    });

    function updateCount(zone) {
        var col = zone.closest('.pipeline-column');
        if (!col) return;
        var el = col.querySelector('.pipeline-count');
        if (el) el.textContent = zone.querySelectorAll('.pipeline-card').length;
    }

    document.querySelectorAll('.pipeline-drop-zone').forEach(function (zone) {
        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            this.classList.add('drag-over');
        });

        zone.addEventListener('dragleave', function (e) {
            if (!this.contains(e.relatedTarget)) {
                this.classList.remove('drag-over');
            }
        });

        zone.addEventListener('drop', async function (e) {
            e.preventDefault();
            this.classList.remove('drag-over');

            if (!dragged || this === originZone) return;

            var contactId  = parseInt(dragged.dataset.id, 10);
            var newStage   = this.dataset.stage;
            var prevZone   = originZone;

            // Optimistic update
            this.appendChild(dragged);
            updateCount(prevZone);
            updateCount(this);

            try {
                var res  = await fetch(window.CSUITE.baseUrl + 'api/pipeline.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ action: 'move', contact_id: contactId, stage: newStage }),
                });
                var data = await res.json();
                if (!data.success) throw new Error();
            } catch (_) {
                prevZone.appendChild(dragged);
                updateCount(this);
                updateCount(prevZone);
            }

            dragged    = null;
            originZone = null;
        });
    });
})();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
