<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Note.php';
require_once __DIR__ . '/../classes/Contact.php';

$action     = $_GET['action'] ?? 'list';
$id         = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
$contact_id = isset( $_GET['contact_id'] ) ? (int) $_GET['contact_id'] : 0;

// ── DELETE ────────────────────────────────────────────────────────────────────
if ( $action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( ! csrf_verify() ) {
        flash( 'error', __( 'flash.csrf' ) );
        redirect( BASE_URL . 'index.php?page=notes' );
    }
    Note::delete( $id );
    flash( 'success', __( 'flash.deleted' ) );
    redirect( BASE_URL . 'index.php?page=notes' );
}

// ── SAVE ──────────────────────────────────────────────────────────────────────
if ( in_array( $action, [ 'add', 'edit' ], true ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( ! csrf_verify() ) {
        flash( 'error', __( 'flash.csrf' ) );
        redirect( BASE_URL . 'index.php?page=notes&action=' . $action . ( $id ? '&id=' . $id : '' ) );
    }

    $body = trim( $_POST['body'] ?? '' );
    if ( $body === '' ) {
        flash( 'error', __( 'flash.required' ) );
        redirect( BASE_URL . 'index.php?page=notes&action=' . $action . ( $id ? '&id=' . $id : '' ) );
    }

    Note::save( [
        'id'         => $id ?: null,
        'title'      => trim( $_POST['title'] ?? '' ),
        'body'       => $body,
        'contact_id' => (int) ( $_POST['contact_id'] ?? 0 ) ?: null,
    ] );
    flash( 'success', __( 'flash.saved' ) );
    redirect( BASE_URL . 'index.php?page=notes' );
}

// ── DELETE CONFIRM ────────────────────────────────────────────────────────────
if ( $action === 'delete' && $id ) {
    $note = Note::find( $id );
    if ( ! $note ) { redirect( BASE_URL . 'index.php?page=notes' ); }

    $page_title = __( 'notes.delete' );
    require __DIR__ . '/../partials/header.php';
    require __DIR__ . '/../partials/nav.php';
    ?>
    <main class="flex-1 md:ml-56 p-6 pt-20 md:pt-6 max-w-md">
        <h1 class="text-2xl font-bold mb-6"><?= __( 'notes.delete' ) ?></h1>
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
            <p class="text-slate-300 mb-2"><?= __( 'notes.confirm_delete' ) ?></p>
            <p class="text-slate-100 font-medium mb-6"><?= htmlspecialchars( $note['title'] ?: mb_substr( $note['body'], 0, 60 ), ENT_QUOTES, 'UTF-8' ) ?></p>
            <form method="post" action="<?= BASE_URL ?>index.php?page=notes&action=delete&id=<?= $id ?>">
                <?= csrf_field() ?>
                <div class="flex gap-3">
                    <button type="submit" class="bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded-md text-sm"><?= __( 'ui.delete' ) ?></button>
                    <a href="<?= BASE_URL ?>index.php?page=notes" class="bg-slate-700 hover:bg-slate-600 text-slate-100 px-4 py-2 rounded-md text-sm"><?= __( 'ui.cancel' ) ?></a>
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
    $note           = ( $action === 'edit' && $id ) ? Note::find( $id ) : [];
    $note           = $note ?: [];
    $contacts_list  = Contact::all_for_select();
    $pre_contact    = $note['contact_id'] ?? $contact_id;
    $page_title     = $action === 'edit' ? __( 'notes.edit' ) : __( 'notes.add' );

    require __DIR__ . '/../partials/header.php';
    require __DIR__ . '/../partials/nav.php';
    ?>
    <main class="flex-1 md:ml-56 p-6 pt-20 md:pt-6 max-w-2xl">
        <div class="flex items-center gap-3 mb-6">
            <a href="<?= BASE_URL ?>index.php?page=notes" class="text-slate-400 hover:text-slate-100 text-sm">&larr; <?= __( 'ui.back' ) ?></a>
            <h1 class="text-2xl font-bold"><?= $page_title ?></h1>
        </div>
        <?php if ( $flash = get_flash( 'error' ) ) : ?><div class="mb-4 px-4 py-3 bg-red-500/10 border border-red-500/30 rounded-md text-red-400 text-sm flash-message"><?= $flash ?></div><?php endif; ?>
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
            <form method="post" action="<?= BASE_URL ?>index.php?page=notes&action=<?= $action ?><?= $id ? '&id=' . $id : '' ?>">
                <?= csrf_field() ?>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1"><?= __( 'notes.note_title' ) ?></label>
                        <input type="text" name="title" value="<?= htmlspecialchars( $note['title'] ?? '', ENT_QUOTES, 'UTF-8' ) ?>"
                               class="bg-slate-700 border border-slate-600 rounded-md px-3 py-2 text-sm text-slate-100 w-full focus:outline-none focus:border-cyan-500">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1"><?= __( 'notes.body' ) ?> <span class="text-red-400">*</span></label>
                        <textarea name="body" rows="8" required
                                  class="bg-slate-700 border border-slate-600 rounded-md px-3 py-2 text-sm text-slate-100 w-full focus:outline-none focus:border-cyan-500 resize-y"><?= htmlspecialchars( $note['body'] ?? '', ENT_QUOTES, 'UTF-8' ) ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1"><?= __( 'notes.linked_contact' ) ?></label>
                        <select name="contact_id" class="bg-slate-700 border border-slate-600 rounded-md px-3 py-2 text-sm text-slate-100 w-full focus:outline-none focus:border-cyan-500">
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
                    <button type="submit" class="bg-cyan-500 hover:bg-cyan-400 text-slate-900 font-medium px-4 py-2 rounded-md text-sm"><?= __( 'ui.save' ) ?></button>
                    <a href="<?= BASE_URL ?>index.php?page=notes" class="bg-slate-700 hover:bg-slate-600 text-slate-100 px-4 py-2 rounded-md text-sm"><?= __( 'ui.cancel' ) ?></a>
                </div>
            </form>
        </div>
    </main>
    <?php
    require __DIR__ . '/../partials/footer.php';
    return;
}

// ── LIST ──────────────────────────────────────────────────────────────────────
$notes      = Note::all();
$page_title = __( 'notes.title' );
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/nav.php';
?>
<main class="flex-1 md:ml-56 p-6 pt-20 md:pt-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold"><?= __( 'notes.title' ) ?></h1>
        <a href="<?= BASE_URL ?>index.php?page=notes&action=add"
           class="bg-cyan-500 hover:bg-cyan-400 text-slate-900 font-medium px-4 py-2 rounded-md text-sm"><?= __( 'notes.add' ) ?></a>
    </div>
    <?php if ( $flash = get_flash( 'success' ) ) : ?><div class="mb-4 px-4 py-3 bg-green-500/10 border border-green-500/30 rounded-md text-green-400 text-sm flash-message"><?= $flash ?></div><?php endif; ?>
    <?php if ( $flash = get_flash( 'error' ) ) : ?><div class="mb-4 px-4 py-3 bg-red-500/10 border border-red-500/30 rounded-md text-red-400 text-sm flash-message"><?= $flash ?></div><?php endif; ?>

    <?php if ( empty( $notes ) ) : ?>
    <div class="bg-slate-800 border border-slate-700 rounded-lg p-8 text-center text-slate-500"><?= __( 'notes.none' ) ?></div>
    <?php else : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ( $notes as $note ) : ?>
        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 flex flex-col">
            <div class="flex items-start justify-between mb-2">
                <h3 class="text-sm font-semibold text-slate-200"><?= htmlspecialchars( $note['title'] ?: '—', ENT_QUOTES, 'UTF-8' ) ?></h3>
                <div class="flex gap-2 ml-2 shrink-0">
                    <a href="<?= BASE_URL ?>index.php?page=notes&action=edit&id=<?= $note['id'] ?>" class="text-xs text-slate-500 hover:text-cyan-400"><?= __( 'ui.edit' ) ?></a>
                    <a href="<?= BASE_URL ?>index.php?page=notes&action=delete&id=<?= $note['id'] ?>" class="text-xs text-slate-500 hover:text-red-400"><?= __( 'ui.delete' ) ?></a>
                </div>
            </div>
            <p class="text-sm text-slate-400 flex-1 line-clamp-4"><?= nl2br( htmlspecialchars( $note['body'], ENT_QUOTES, 'UTF-8' ) ) ?></p>
            <?php if ( $note['company_name'] ) : ?>
            <p class="text-xs text-cyan-500 mt-2"><?= htmlspecialchars( $note['company_name'], ENT_QUOTES, 'UTF-8' ) ?></p>
            <?php endif; ?>
            <p class="text-xs text-slate-600 mt-1"><?= htmlspecialchars( $note['created_at'], ENT_QUOTES, 'UTF-8' ) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
