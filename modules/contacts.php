<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Contact.php';
require_once __DIR__ . '/../classes/Note.php';
require_once __DIR__ . '/../classes/Task.php';
require_once __DIR__ . '/../classes/AgentSession.php';

$action = $_GET['action'] ?? 'list';
$id     = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

// ── DELETE ────────────────────────────────────────────────────────────────────
if ( $action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( ! csrf_verify() ) {
        flash( 'error', __( 'flash.csrf' ) );
        redirect( BASE_URL . 'index.php?page=contacts' );
    }
    Contact::delete( $id );
    flash( 'success', __( 'flash.deleted' ) );
    redirect( BASE_URL . 'index.php?page=contacts' );
}

// ── SAVE (add / edit) ─────────────────────────────────────────────────────────
if ( in_array( $action, [ 'add', 'edit' ], true ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( ! csrf_verify() ) {
        flash( 'error', __( 'flash.csrf' ) );
        redirect( BASE_URL . 'index.php?page=contacts&action=' . $action . ( $id ? '&id=' . $id : '' ) );
    }

    $data = [
        'id'             => $id ?: null,
        'company_name'   => trim( $_POST['company_name']   ?? '' ),
        'contact_name'   => trim( $_POST['contact_name']   ?? '' ),
        'email'          => trim( $_POST['email']          ?? '' ),
        'phone'          => trim( $_POST['phone']          ?? '' ),
        'website'        => trim( $_POST['website']        ?? '' ),
        'source'         => $_POST['source']         ?? '',
        'status'         => $_POST['status']         ?? 'prospect',
        'pipeline_stage' => trim( $_POST['pipeline_stage'] ?? '' ),
        'notes'          => trim( $_POST['notes']          ?? '' ),
    ];

    if ( $data['company_name'] === '' ) {
        flash( 'error', __( 'flash.required' ) );
        redirect( BASE_URL . 'index.php?page=contacts&action=' . $action . ( $id ? '&id=' . $id : '' ) );
    }

    $saved_id = Contact::save( $data );
    flash( 'success', __( 'flash.saved' ) );
    redirect( BASE_URL . 'index.php?page=contacts&action=view&id=' . $saved_id );
}

// ── VIEW ──────────────────────────────────────────────────────────────────────
if ( $action === 'view' && $id ) {
    $contact = Contact::find( $id );
    if ( ! $contact ) {
        flash( 'error', __( 'flash.error' ) );
        redirect( BASE_URL . 'index.php?page=contacts' );
    }
    $linked_notes    = Note::all( $id );
    $linked_tasks    = Task::open_for_contact( $id );
    $linked_sessions = AgentSession::by_contact( $id );

    $page_title = htmlspecialchars( $contact['company_name'], ENT_QUOTES, 'UTF-8' );
    require __DIR__ . '/../partials/header.php';
    require __DIR__ . '/../partials/nav.php';
    ?>
    <main class="flex-1 md:ml-56 p-6 pt-20 md:pt-6">
        <div class="flex items-center gap-3 mb-6">
            <a href="<?= BASE_URL ?>index.php?page=contacts" class="text-zinc-400 hover:text-zinc-100 text-sm">&larr; <?= __( 'ui.back' ) ?></a>
            <h1 class="text-2xl font-bold"><?= htmlspecialchars( $contact['company_name'], ENT_QUOTES, 'UTF-8' ) ?></h1>
        </div>
        <?php if ( $flash = get_flash( 'success' ) ) : ?><div class="mb-4 px-4 py-3 bg-green-500/10 border border-green-500/30 rounded-md text-green-400 text-sm flash-message"><?= $flash ?></div><?php endif; ?>
        <?php if ( $flash = get_flash( 'error' ) ) : ?><div class="mb-4 px-4 py-3 bg-red-500/10 border border-red-500/30 rounded-md text-red-400 text-sm flash-message"><?= $flash ?></div><?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <div class="lg:col-span-2 bg-zinc-900 border border-zinc-800 rounded-lg p-5">
                <?php
                $fields = [
                    'contact_name'   => __( 'contacts.contact_name' ),
                    'email'          => __( 'contacts.email' ),
                    'phone'          => __( 'contacts.phone' ),
                    'website'        => __( 'contacts.website' ),
                    'source'         => __( 'contacts.source' ),
                    'pipeline_stage' => __( 'contacts.pipeline_stage' ),
                    'notes'          => __( 'contacts.notes' ),
                    'created_at'     => __( 'contacts.created' ),
                ];
                foreach ( $fields as $field => $label ) :
                    if ( empty( $contact[ $field ] ) ) continue;
                ?>
                <div class="flex py-2 border-b border-zinc-800/50 last:border-0">
                    <span class="w-36 text-xs text-zinc-500 uppercase tracking-wide pt-0.5 shrink-0"><?= $label ?></span>
                    <span class="text-sm text-zinc-300"><?= htmlspecialchars( $contact[ $field ], ENT_QUOTES, 'UTF-8' ) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-5 space-y-3">
                <?php
                $status_colours = [
                    'prospect' => 'bg-zinc-500/20 text-zinc-300',
                    'warm'     => 'bg-amber-500/20 text-amber-400',
                    'active'   => 'bg-orange-500/20 text-orange-400',
                    'customer' => 'bg-green-500/20 text-green-400',
                    'dormant'  => 'bg-zinc-700/30 text-zinc-500',
                    'lost'     => 'bg-red-500/20 text-red-400',
                ];
                $sc = $status_colours[ $contact['status'] ] ?? 'bg-zinc-500/20 text-zinc-300';
                ?>
                <span class="inline-block px-3 py-1 rounded-full text-sm font-medium <?= $sc ?>"><?= __( 'contacts.status.' . $contact['status'] ) ?></span>
                <div class="flex flex-col gap-2 pt-2">
                    <a href="<?= BASE_URL ?>index.php?page=contacts&action=edit&id=<?= $contact['id'] ?>"
                       class="bg-zinc-800 hover:bg-zinc-700 text-zinc-100 px-4 py-2 rounded-md text-sm text-center transition-colors"><?= __( 'ui.edit' ) ?></a>
                    <a href="<?= BASE_URL ?>index.php?page=contacts&action=delete&id=<?= $contact['id'] ?>"
                       class="bg-red-600/20 hover:bg-red-600/40 text-red-400 px-4 py-2 rounded-md text-sm text-center transition-colors"><?= __( 'ui.delete' ) ?></a>
                </div>
            </div>
        </div>

        <!-- Linked notes -->
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-5 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-zinc-300"><?= __( 'notes.title' ) ?></h2>
                <a href="<?= BASE_URL ?>index.php?page=notes&action=add&contact_id=<?= $contact['id'] ?>" class="text-xs text-orange-500 hover:text-orange-400"><?= __( 'notes.add' ) ?></a>
            </div>
            <?php if ( empty( $linked_notes ) ) : ?>
            <p class="text-sm text-zinc-500"><?= __( 'notes.none' ) ?></p>
            <?php else : ?>
            <div class="space-y-2">
                <?php foreach ( $linked_notes as $note ) : ?>
                <div class="py-2 border-b border-zinc-800/50 last:border-0">
                    <div class="flex justify-between">
                        <p class="text-sm font-medium text-zinc-300"><?= htmlspecialchars( $note['title'] ?: '—', ENT_QUOTES, 'UTF-8' ) ?></p>
                        <a href="<?= BASE_URL ?>index.php?page=notes&action=edit&id=<?= $note['id'] ?>" class="text-xs text-zinc-500 hover:text-orange-400"><?= __( 'ui.edit' ) ?></a>
                    </div>
                    <p class="text-sm text-zinc-400 mt-1"><?= nl2br( htmlspecialchars( $note['body'], ENT_QUOTES, 'UTF-8' ) ) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Linked tasks -->
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-5 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-zinc-300"><?= __( 'tasks.title' ) ?></h2>
                <a href="<?= BASE_URL ?>index.php?page=tasks&action=add&contact_id=<?= $contact['id'] ?>" class="text-xs text-orange-500 hover:text-orange-400"><?= __( 'tasks.add' ) ?></a>
            </div>
            <?php if ( empty( $linked_tasks ) ) : ?>
            <p class="text-sm text-zinc-500"><?= __( 'tasks.none' ) ?></p>
            <?php else : ?>
            <div class="space-y-2">
                <?php foreach ( $linked_tasks as $task ) : ?>
                <div class="flex items-center gap-3 py-2 border-b border-zinc-800/50 last:border-0">
                    <span class="text-xs px-2 py-0.5 rounded bg-zinc-700/50 text-zinc-400"><?= __( 'tasks.priority.' . $task['priority'] ) ?></span>
                    <span class="flex-1 text-sm text-zinc-300"><?= htmlspecialchars( $task['title'], ENT_QUOTES, 'UTF-8' ) ?></span>
                    <?php if ( $task['due_date'] ) : ?><span class="text-xs text-zinc-500"><?= htmlspecialchars( $task['due_date'], ENT_QUOTES, 'UTF-8' ) ?></span><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Linked agent sessions -->
        <?php if ( ! empty( $linked_sessions ) ) : ?>
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-5">
            <h2 class="text-sm font-semibold text-zinc-300 mb-3"><?= __( 'agents.history' ) ?></h2>
            <div class="space-y-2">
                <?php foreach ( $linked_sessions as $s ) : ?>
                <div class="py-2 border-b border-zinc-800/50 last:border-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs px-2 py-0.5 bg-orange-500/20 text-orange-400 rounded"><?= htmlspecialchars( $s['agent_role'], ENT_QUOTES, 'UTF-8' ) ?></span>
                        <span class="text-xs text-zinc-500"><?= htmlspecialchars( $s['created_at'], ENT_QUOTES, 'UTF-8' ) ?></span>
                    </div>
                    <p class="text-sm text-zinc-400 truncate"><?= htmlspecialchars( $s['user_prompt'], ENT_QUOTES, 'UTF-8' ) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>
    <?php
    require __DIR__ . '/../partials/footer.php';
    return;
}

// ── DELETE CONFIRM ────────────────────────────────────────────────────────────
if ( $action === 'delete' && $id ) {
    $contact = Contact::find( $id );
    if ( ! $contact ) {
        redirect( BASE_URL . 'index.php?page=contacts' );
    }
    $page_title = __( 'contacts.delete' );
    require __DIR__ . '/../partials/header.php';
    require __DIR__ . '/../partials/nav.php';
    ?>
    <main class="flex-1 md:ml-56 p-6 pt-20 md:pt-6 max-w-lg">
        <h1 class="text-2xl font-bold mb-6"><?= __( 'contacts.delete' ) ?></h1>
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-6">
            <p class="text-zinc-300 mb-2"><?= __( 'contacts.confirm_delete' ) ?></p>
            <p class="text-sm text-amber-400 mb-6"><?= __( 'contacts.gdpr_notice' ) ?></p>
            <p class="text-zinc-100 font-medium mb-6"><?= htmlspecialchars( $contact['company_name'], ENT_QUOTES, 'UTF-8' ) ?></p>
            <form method="post" action="<?= BASE_URL ?>index.php?page=contacts&action=delete&id=<?= $id ?>">
                <?= csrf_field() ?>
                <div class="flex gap-3">
                    <button type="submit" class="bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded-md text-sm"><?= __( 'ui.delete' ) ?></button>
                    <a href="<?= BASE_URL ?>index.php?page=contacts" class="bg-zinc-800 hover:bg-zinc-700 text-zinc-100 px-4 py-2 rounded-md text-sm"><?= __( 'ui.cancel' ) ?></a>
                </div>
            </form>
        </div>
    </main>
    <?php
    require __DIR__ . '/../partials/footer.php';
    return;
}

// ── ADD / EDIT FORM ───────────────────────────────────────────────────────────
if ( in_array( $action, [ 'add', 'edit' ], true ) ) {
    $contact    = ( $action === 'edit' && $id ) ? Contact::find( $id ) : [];
    $contact    = $contact ?: [];
    $page_title = $action === 'edit' ? __( 'contacts.edit' ) : __( 'contacts.add' );

    require __DIR__ . '/../partials/header.php';
    require __DIR__ . '/../partials/nav.php';
    ?>
    <main class="flex-1 md:ml-56 p-6 pt-20 md:pt-6 max-w-2xl">
        <div class="flex items-center gap-3 mb-6">
            <a href="<?= BASE_URL ?>index.php?page=contacts" class="text-zinc-400 hover:text-zinc-100 text-sm">&larr; <?= __( 'ui.back' ) ?></a>
            <h1 class="text-2xl font-bold"><?= $page_title ?></h1>
        </div>
        <?php if ( $flash = get_flash( 'error' ) ) : ?><div class="mb-4 px-4 py-3 bg-red-500/10 border border-red-500/30 rounded-md text-red-400 text-sm flash-message"><?= $flash ?></div><?php endif; ?>
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-6">
            <form method="post" action="<?= BASE_URL ?>index.php?page=contacts&action=<?= $action ?><?= $id ? '&id=' . $id : '' ?>">
                <?= csrf_field() ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs text-zinc-400 mb-1"><?= __( 'contacts.company_name' ) ?> <span class="text-red-400">*</span></label>
                        <input type="text" name="company_name" required value="<?= htmlspecialchars( $contact['company_name'] ?? '', ENT_QUOTES, 'UTF-8' ) ?>"
                               class="bg-zinc-800 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 w-full focus:outline-none focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs text-zinc-400 mb-1"><?= __( 'contacts.contact_name' ) ?></label>
                        <input type="text" name="contact_name" value="<?= htmlspecialchars( $contact['contact_name'] ?? '', ENT_QUOTES, 'UTF-8' ) ?>"
                               class="bg-zinc-800 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 w-full focus:outline-none focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs text-zinc-400 mb-1"><?= __( 'contacts.email' ) ?></label>
                        <input type="email" name="email" value="<?= htmlspecialchars( $contact['email'] ?? '', ENT_QUOTES, 'UTF-8' ) ?>"
                               class="bg-zinc-800 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 w-full focus:outline-none focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs text-zinc-400 mb-1"><?= __( 'contacts.phone' ) ?></label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars( $contact['phone'] ?? '', ENT_QUOTES, 'UTF-8' ) ?>"
                               class="bg-zinc-800 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 w-full focus:outline-none focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs text-zinc-400 mb-1"><?= __( 'contacts.website' ) ?></label>
                        <input type="url" name="website" value="<?= htmlspecialchars( $contact['website'] ?? '', ENT_QUOTES, 'UTF-8' ) ?>"
                               class="bg-zinc-800 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 w-full focus:outline-none focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs text-zinc-400 mb-1"><?= __( 'contacts.source' ) ?></label>
                        <select name="source" class="bg-zinc-800 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 w-full focus:outline-none focus:border-orange-500">
                            <option value=""><?= __( 'ui.none' ) ?></option>
                            <?php foreach ( [ 'linkedin', 'referral', 'outbound', 'inbound', 'event', 'other' ] as $src ) : ?>
                            <option value="<?= $src ?>" <?= ( $contact['source'] ?? '' ) === $src ? 'selected' : '' ?>><?= __( 'contacts.source.' . $src ) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-zinc-400 mb-1"><?= __( 'contacts.status' ) ?></label>
                        <select name="status" class="bg-zinc-800 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 w-full focus:outline-none focus:border-orange-500">
                            <?php foreach ( [ 'prospect', 'warm', 'active', 'customer', 'dormant', 'lost' ] as $st ) : ?>
                            <option value="<?= $st ?>" <?= ( $contact['status'] ?? 'prospect' ) === $st ? 'selected' : '' ?>><?= __( 'contacts.status.' . $st ) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-zinc-400 mb-1"><?= __( 'contacts.pipeline_stage' ) ?></label>
                        <input type="text" name="pipeline_stage" value="<?= htmlspecialchars( $contact['pipeline_stage'] ?? '', ENT_QUOTES, 'UTF-8' ) ?>"
                               class="bg-zinc-800 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 w-full focus:outline-none focus:border-orange-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-zinc-400 mb-1"><?= __( 'contacts.notes' ) ?></label>
                        <textarea name="notes" rows="4"
                                  class="bg-zinc-800 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 w-full focus:outline-none focus:border-orange-500 resize-y"><?= htmlspecialchars( $contact['notes'] ?? '', ENT_QUOTES, 'UTF-8' ) ?></textarea>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-400 text-zinc-950 font-medium px-4 py-2 rounded-md text-sm"><?= __( 'ui.save' ) ?></button>
                    <a href="<?= BASE_URL ?>index.php?page=contacts" class="bg-zinc-800 hover:bg-zinc-700 text-zinc-100 px-4 py-2 rounded-md text-sm"><?= __( 'ui.cancel' ) ?></a>
                </div>
            </form>
        </div>
    </main>
    <?php
    require __DIR__ . '/../partials/footer.php';
    return;
}

// ── LIST ──────────────────────────────────────────────────────────────────────
$per_page   = 20;
$page_num   = max( 1, (int) ( $_GET['p'] ?? 1 ) );
$filters    = [
    'status' => $_GET['status'] ?? '',
    'search' => trim( $_GET['search'] ?? '' ),
];
$total      = Contact::count( $filters );
$contacts   = Contact::all( $filters, $page_num, $per_page );
$total_pages = (int) ceil( $total / $per_page );

$page_title = __( 'contacts.title' );
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/nav.php';
?>
<main class="flex-1 md:ml-56 p-6 pt-20 md:pt-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold"><?= __( 'contacts.title' ) ?></h1>
        <a href="<?= BASE_URL ?>index.php?page=contacts&action=add"
           class="bg-orange-500 hover:bg-orange-400 text-zinc-950 font-medium px-4 py-2 rounded-md text-sm transition-colors"><?= __( 'contacts.add' ) ?></a>
    </div>
    <?php if ( $flash = get_flash( 'success' ) ) : ?><div class="mb-4 px-4 py-3 bg-green-500/10 border border-green-500/30 rounded-md text-green-400 text-sm flash-message"><?= $flash ?></div><?php endif; ?>
    <?php if ( $flash = get_flash( 'error' ) ) : ?><div class="mb-4 px-4 py-3 bg-red-500/10 border border-red-500/30 rounded-md text-red-400 text-sm flash-message"><?= $flash ?></div><?php endif; ?>

    <!-- Filters -->
    <form method="get" action="<?= BASE_URL ?>index.php" class="flex flex-wrap gap-3 mb-4">
        <input type="hidden" name="page" value="contacts">
        <input type="text" name="search" placeholder="<?= __( 'ui.search' ) ?>..." value="<?= htmlspecialchars( $filters['search'], ENT_QUOTES, 'UTF-8' ) ?>"
               class="bg-zinc-900 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 focus:outline-none focus:border-orange-500 w-48">
        <select name="status" class="bg-zinc-900 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 focus:outline-none focus:border-orange-500">
            <option value=""><?= __( 'ui.all' ) ?></option>
            <?php foreach ( [ 'prospect', 'warm', 'active', 'customer', 'dormant', 'lost' ] as $st ) : ?>
            <option value="<?= $st ?>" <?= $filters['status'] === $st ? 'selected' : '' ?>><?= __( 'contacts.status.' . $st ) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-zinc-800 hover:bg-zinc-700 text-zinc-100 px-4 py-2 rounded-md text-sm"><?= __( 'ui.filter' ) ?></button>
        <?php if ( $filters['search'] || $filters['status'] ) : ?>
        <a href="<?= BASE_URL ?>index.php?page=contacts" class="text-zinc-400 hover:text-zinc-100 px-3 py-2 text-sm">&times; <?= __( 'ui.all' ) ?></a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-zinc-800/50 text-zinc-400 uppercase text-xs tracking-wider">
                    <th class="text-left px-4 py-3"><?= __( 'contacts.company_name' ) ?></th>
                    <th class="text-left px-4 py-3 hidden md:table-cell"><?= __( 'contacts.contact_name' ) ?></th>
                    <th class="text-left px-4 py-3 hidden lg:table-cell"><?= __( 'contacts.email' ) ?></th>
                    <th class="text-left px-4 py-3"><?= __( 'contacts.status' ) ?></th>
                    <th class="text-right px-4 py-3"><?= __( 'ui.actions' ) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $contacts ) ) : ?>
                <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500"><?= __( 'contacts.none' ) ?></td></tr>
                <?php else :
                $status_colours = [
                    'prospect' => 'bg-zinc-500/20 text-zinc-300',
                    'warm'     => 'bg-amber-500/20 text-amber-400',
                    'active'   => 'bg-orange-500/20 text-orange-400',
                    'customer' => 'bg-green-500/20 text-green-400',
                    'dormant'  => 'bg-zinc-700/30 text-zinc-500',
                    'lost'     => 'bg-red-500/20 text-red-400',
                ];
                foreach ( $contacts as $c ) :
                    $sc = $status_colours[ $c['status'] ] ?? 'bg-zinc-500/20 text-zinc-300';
                ?>
                <tr class="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition-colors">
                    <td class="px-4 py-3">
                        <a href="<?= BASE_URL ?>index.php?page=contacts&action=view&id=<?= $c['id'] ?>" class="text-orange-400 hover:text-orange-300 font-medium">
                            <?= htmlspecialchars( $c['company_name'], ENT_QUOTES, 'UTF-8' ) ?>
                        </a>
                    </td>
                    <td class="px-4 py-3 text-zinc-300 hidden md:table-cell"><?= htmlspecialchars( $c['contact_name'] ?? '', ENT_QUOTES, 'UTF-8' ) ?></td>
                    <td class="px-4 py-3 text-zinc-400 hidden lg:table-cell"><?= htmlspecialchars( $c['email'] ?? '', ENT_QUOTES, 'UTF-8' ) ?></td>
                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs <?= $sc ?>"><?= __( 'contacts.status.' . $c['status'] ) ?></span></td>
                    <td class="px-4 py-3 text-right">
                        <a href="<?= BASE_URL ?>index.php?page=contacts&action=edit&id=<?= $c['id'] ?>" class="text-zinc-400 hover:text-orange-400 text-xs mr-3"><?= __( 'ui.edit' ) ?></a>
                        <a href="<?= BASE_URL ?>index.php?page=contacts&action=delete&id=<?= $c['id'] ?>" class="text-zinc-400 hover:text-red-400 text-xs"><?= __( 'ui.delete' ) ?></a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ( $total_pages > 1 ) : ?>
    <div class="flex items-center justify-between mt-4 text-sm text-zinc-400">
        <span><?= __( 'ui.page' ) ?> <?= $page_num ?> / <?= $total_pages ?></span>
        <div class="flex gap-2">
            <?php if ( $page_num > 1 ) : ?>
            <a href="?page=contacts&p=<?= $page_num - 1 ?>&status=<?= urlencode( $filters['status'] ) ?>&search=<?= urlencode( $filters['search'] ) ?>"
               class="bg-zinc-800 hover:bg-zinc-700 px-3 py-1 rounded-md"><?= __( 'ui.previous' ) ?></a>
            <?php endif; ?>
            <?php if ( $page_num < $total_pages ) : ?>
            <a href="?page=contacts&p=<?= $page_num + 1 ?>&status=<?= urlencode( $filters['status'] ) ?>&search=<?= urlencode( $filters['search'] ) ?>"
               class="bg-zinc-800 hover:bg-zinc-700 px-3 py-1 rounded-md"><?= __( 'ui.next' ) ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
