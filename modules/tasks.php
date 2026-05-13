<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Task.php';
require_once __DIR__ . '/../classes/Contact.php';

$action     = $_GET['action'] ?? 'list';
$id         = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
$contact_id = isset( $_GET['contact_id'] ) ? (int) $_GET['contact_id'] : 0;
$show_all   = isset( $_GET['show_all'] );

// ── MARK DONE ─────────────────────────────────────────────────────────────────
if ( $action === 'done' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( ! csrf_verify() ) {
        flash( 'error', __( 'flash.csrf' ) );
        redirect( BASE_URL . 'index.php?page=tasks' );
    }
    Task::mark_done( $id );
    flash( 'success', __( 'flash.saved' ) );
    redirect( BASE_URL . 'index.php?page=tasks' );
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if ( $action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( ! csrf_verify() ) {
        flash( 'error', __( 'flash.csrf' ) );
        redirect( BASE_URL . 'index.php?page=tasks' );
    }
    Task::delete( $id );
    flash( 'success', __( 'flash.deleted' ) );
    redirect( BASE_URL . 'index.php?page=tasks' );
}

// ── SAVE ──────────────────────────────────────────────────────────────────────
if ( in_array( $action, [ 'add', 'edit' ], true ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( ! csrf_verify() ) {
        flash( 'error', __( 'flash.csrf' ) );
        redirect( BASE_URL . 'index.php?page=tasks&action=' . $action . ( $id ? '&id=' . $id : '' ) );
    }

    $title = trim( $_POST['title'] ?? '' );
    if ( $title === '' ) {
        flash( 'error', __( 'flash.required' ) );
        redirect( BASE_URL . 'index.php?page=tasks&action=' . $action . ( $id ? '&id=' . $id : '' ) );
    }

    Task::save( [
        'id'          => $id ?: null,
        'title'       => $title,
        'description' => trim( $_POST['description'] ?? '' ),
        'status'      => $_POST['status']   ?? 'todo',
        'priority'    => $_POST['priority'] ?? 'medium',
        'due_date'    => $_POST['due_date'] ?? '',
        'contact_id'  => (int) ( $_POST['contact_id'] ?? 0 ) ?: null,
    ] );
    flash( 'success', __( 'flash.saved' ) );
    redirect( BASE_URL . 'index.php?page=tasks' );
}

// ── DELETE CONFIRM ────────────────────────────────────────────────────────────
if ( $action === 'delete' && $id ) {
    $task = Task::find( $id );
    if ( ! $task ) { redirect( BASE_URL . 'index.php?page=tasks' ); }

    $page_title = __( 'tasks.delete' );
    require __DIR__ . '/../partials/header.php';
    require __DIR__ . '/../partials/nav.php';
    ?>
    <main class="flex-1 md:ml-56 p-6 pt-20 md:pt-6 max-w-md">
        <h1 class="text-2xl font-bold mb-6"><?= __( 'tasks.delete' ) ?></h1>
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-6">
            <p class="text-zinc-300 mb-2"><?= __( 'tasks.confirm_delete' ) ?></p>
            <p class="text-zinc-100 font-medium mb-6"><?= htmlspecialchars( $task['title'], ENT_QUOTES, 'UTF-8' ) ?></p>
            <form method="post" action="<?= BASE_URL ?>index.php?page=tasks&action=delete&id=<?= $id ?>">
                <?= csrf_field() ?>
                <div class="flex gap-3">
                    <button type="submit" class="bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded-md text-sm"><?= __( 'ui.delete' ) ?></button>
                    <a href="<?= BASE_URL ?>index.php?page=tasks" class="bg-zinc-800 hover:bg-zinc-700 text-zinc-100 px-4 py-2 rounded-md text-sm"><?= __( 'ui.cancel' ) ?></a>
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
    $task          = ( $action === 'edit' && $id ) ? Task::find( $id ) : [];
    $task          = $task ?: [];
    $contacts_list = Contact::all_for_select();
    $pre_contact   = $task['contact_id'] ?? $contact_id;
    $page_title    = $action === 'edit' ? __( 'tasks.edit' ) : __( 'tasks.add' );
    // Pre-fill title from agent save-as-task
    $prefill_title = $_GET['title'] ?? '';
    $prefill_desc  = $_GET['description'] ?? '';

    require __DIR__ . '/../partials/header.php';
    require __DIR__ . '/../partials/nav.php';
    ?>
    <main class="flex-1 md:ml-56 p-6 pt-20 md:pt-6 max-w-2xl">
        <div class="flex items-center gap-3 mb-6">
            <a href="<?= BASE_URL ?>index.php?page=tasks" class="text-zinc-400 hover:text-zinc-100 text-sm">&larr; <?= __( 'ui.back' ) ?></a>
            <h1 class="text-2xl font-bold"><?= $page_title ?></h1>
        </div>
        <?php if ( $flash = get_flash( 'error' ) ) : ?><div class="mb-4 px-4 py-3 bg-red-500/10 border border-red-500/30 rounded-md text-red-400 text-sm flash-message"><?= $flash ?></div><?php endif; ?>
        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-6">
            <form method="post" action="<?= BASE_URL ?>index.php?page=tasks&action=<?= $action ?><?= $id ? '&id=' . $id : '' ?>">
                <?= csrf_field() ?>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs text-zinc-400 mb-1"><?= __( 'tasks.task_title' ) ?> <span class="text-red-400">*</span></label>
                        <input type="text" name="title" required
                               value="<?= htmlspecialchars( $task['title'] ?? $prefill_title, ENT_QUOTES, 'UTF-8' ) ?>"
                               class="bg-zinc-800 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 w-full focus:outline-none focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs text-zinc-400 mb-1"><?= __( 'tasks.description' ) ?></label>
                        <textarea name="description" rows="4"
                                  class="bg-zinc-800 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 w-full focus:outline-none focus:border-orange-500 resize-y"><?= htmlspecialchars( $task['description'] ?? $prefill_desc, ENT_QUOTES, 'UTF-8' ) ?></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs text-zinc-400 mb-1"><?= __( 'tasks.status' ) ?></label>
                            <select name="status" class="bg-zinc-800 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 w-full focus:outline-none focus:border-orange-500">
                                <?php foreach ( [ 'todo', 'in_progress', 'done' ] as $st ) : ?>
                                <option value="<?= $st ?>" <?= ( $task['status'] ?? 'todo' ) === $st ? 'selected' : '' ?>><?= __( 'tasks.status.' . $st ) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-zinc-400 mb-1"><?= __( 'tasks.priority' ) ?></label>
                            <select name="priority" class="bg-zinc-800 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 w-full focus:outline-none focus:border-orange-500">
                                <?php foreach ( [ 'high', 'medium', 'low' ] as $pr ) : ?>
                                <option value="<?= $pr ?>" <?= ( $task['priority'] ?? 'medium' ) === $pr ? 'selected' : '' ?>><?= __( 'tasks.priority.' . $pr ) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-zinc-400 mb-1"><?= __( 'tasks.due_date' ) ?></label>
                            <input type="date" name="due_date"
                                   value="<?= htmlspecialchars( $task['due_date'] ?? '', ENT_QUOTES, 'UTF-8' ) ?>"
                                   class="bg-zinc-800 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 w-full focus:outline-none focus:border-orange-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-zinc-400 mb-1"><?= __( 'tasks.linked_contact' ) ?></label>
                        <select name="contact_id" class="bg-zinc-800 border border-zinc-700 rounded-md px-3 py-2 text-sm text-zinc-100 w-full focus:outline-none focus:border-orange-500">
                            <option value=""><?= __( 'ui.none' ) ?></option>
                            <?php foreach ( $contacts_list as $c ) : ?>
                            <option value="<?= $c['id'] ?>" <?= (int) $pre_contact === (int) $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars( $c['company_name'], ENT_QUOTES, 'UTF-8' ) ?><?= $c['contact_name'] ? ' — ' . htmlspecialchars( $c['contact_name'], ENT_QUOTES, 'UTF-8' ) : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-400 text-zinc-950 font-medium px-4 py-2 rounded-md text-sm"><?= __( 'ui.save' ) ?></button>
                    <a href="<?= BASE_URL ?>index.php?page=tasks" class="bg-zinc-800 hover:bg-zinc-700 text-zinc-100 px-4 py-2 rounded-md text-sm"><?= __( 'ui.cancel' ) ?></a>
                </div>
            </form>
        </div>
    </main>
    <?php
    require __DIR__ . '/../partials/footer.php';
    return;
}

// ── LIST ──────────────────────────────────────────────────────────────────────
$tasks      = Task::all( ! $show_all );
$page_title = __( 'tasks.title' );
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/nav.php';
?>
<main class="flex-1 md:ml-56 p-6 pt-20 md:pt-6">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <h1 class="text-2xl font-bold"><?= __( 'tasks.title' ) ?></h1>
            <a href="<?= BASE_URL ?>index.php?page=tasks<?= $show_all ? '' : '&show_all=1' ?>"
               class="text-sm text-orange-500 hover:text-orange-400">
                <?= $show_all ? __( 'tasks.show_open' ) : __( 'tasks.show_all' ) ?>
            </a>
        </div>
        <a href="<?= BASE_URL ?>index.php?page=tasks&action=add"
           class="bg-orange-500 hover:bg-orange-400 text-zinc-950 font-medium px-4 py-2 rounded-md text-sm"><?= __( 'tasks.add' ) ?></a>
    </div>
    <?php if ( $flash = get_flash( 'success' ) ) : ?><div class="mb-4 px-4 py-3 bg-green-500/10 border border-green-500/30 rounded-md text-green-400 text-sm flash-message"><?= $flash ?></div><?php endif; ?>
    <?php if ( $flash = get_flash( 'error' ) ) : ?><div class="mb-4 px-4 py-3 bg-red-500/10 border border-red-500/30 rounded-md text-red-400 text-sm flash-message"><?= $flash ?></div><?php endif; ?>

    <?php if ( empty( $tasks ) ) : ?>
    <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-8 text-center text-zinc-500"><?= __( 'tasks.none' ) ?></div>
    <?php else : ?>
    <div class="bg-zinc-900 border border-zinc-800 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-zinc-800/50 text-zinc-400 uppercase text-xs tracking-wider">
                    <th class="text-left px-4 py-3"><?= __( 'tasks.task_title' ) ?></th>
                    <th class="text-left px-4 py-3 hidden md:table-cell"><?= __( 'tasks.priority' ) ?></th>
                    <th class="text-left px-4 py-3 hidden md:table-cell"><?= __( 'tasks.status' ) ?></th>
                    <th class="text-left px-4 py-3 hidden lg:table-cell"><?= __( 'tasks.due_date' ) ?></th>
                    <th class="text-right px-4 py-3"><?= __( 'ui.actions' ) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $priority_colours = [
                    'high'   => 'bg-red-500/20 text-red-400',
                    'medium' => 'bg-amber-500/20 text-amber-400',
                    'low'    => 'bg-zinc-700/50 text-zinc-400',
                ];
                $status_colours = [
                    'todo'        => 'bg-zinc-700/50 text-zinc-400',
                    'in_progress' => 'bg-orange-500/20 text-orange-400',
                    'done'        => 'bg-green-500/20 text-green-400',
                ];
                foreach ( $tasks as $task ) :
                    $pc = $priority_colours[ $task['priority'] ] ?? 'bg-zinc-700/50 text-zinc-400';
                    $sc = $status_colours[ $task['status'] ]    ?? 'bg-zinc-700/50 text-zinc-400';
                    $is_done = $task['status'] === 'done';
                ?>
                <tr class="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition-colors <?= $is_done ? 'opacity-60' : '' ?>">
                    <td class="px-4 py-3">
                        <p class="text-zinc-200 <?= $is_done ? 'line-through' : '' ?>"><?= htmlspecialchars( $task['title'], ENT_QUOTES, 'UTF-8' ) ?></p>
                        <?php if ( $task['company_name'] ) : ?>
                        <p class="text-xs text-zinc-500"><?= htmlspecialchars( $task['company_name'], ENT_QUOTES, 'UTF-8' ) ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell"><span class="px-2 py-0.5 rounded text-xs <?= $pc ?>"><?= __( 'tasks.priority.' . $task['priority'] ) ?></span></td>
                    <td class="px-4 py-3 hidden md:table-cell"><span class="px-2 py-0.5 rounded text-xs <?= $sc ?>"><?= __( 'tasks.status.' . $task['status'] ) ?></span></td>
                    <td class="px-4 py-3 text-zinc-400 hidden lg:table-cell"><?= $task['due_date'] ? htmlspecialchars( $task['due_date'], ENT_QUOTES, 'UTF-8' ) : '—' ?></td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <?php if ( ! $is_done ) : ?>
                            <form method="post" action="<?= BASE_URL ?>index.php?page=tasks&action=done&id=<?= $task['id'] ?>" class="inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-xs text-zinc-400 hover:text-green-400 transition-colors"><?= __( 'tasks.mark_done' ) ?></button>
                            </form>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>index.php?page=tasks&action=edit&id=<?= $task['id'] ?>" class="text-xs text-zinc-400 hover:text-orange-400"><?= __( 'ui.edit' ) ?></a>
                            <a href="<?= BASE_URL ?>index.php?page=tasks&action=delete&id=<?= $task['id'] ?>" class="text-xs text-zinc-400 hover:text-red-400"><?= __( 'ui.delete' ) ?></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
