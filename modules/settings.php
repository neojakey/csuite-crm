<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$pdo = db();

// ── SAVE SETTINGS ─────────────────────────────────────────────────────────────
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['save_settings'] ) ) {
    if ( ! csrf_verify() ) {
        flash( 'error', __( 'flash.csrf' ) );
        redirect( BASE_URL . 'index.php?page=settings' );
    }

    $updates = [
        'sprint_week'        => max( 1, (int) ( $_POST['sprint_week'] ?? 1 ) ),
        'sprint_total_weeks' => max( 1, (int) ( $_POST['sprint_total_weeks'] ?? 12 ) ),
        'checkpoint_date'    => trim( $_POST['checkpoint_date'] ?? '' ),
    ];

    $stmt = $pdo->prepare( 'UPDATE settings SET setting_value = ? WHERE setting_key = ?' );
    foreach ( $updates as $key => $value ) {
        $stmt->execute( [ (string) $value, $key ] );
    }

    flash( 'success', __( 'flash.saved' ) );
    redirect( BASE_URL . 'index.php?page=settings' );
}

// ── CHANGE PASSWORD ────────────────────────────────────────────────────────────
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['change_password'] ) ) {
    if ( ! csrf_verify() ) {
        flash( 'error', __( 'flash.csrf' ) );
        redirect( BASE_URL . 'index.php?page=settings' );
    }

    $auth_file   = __DIR__ . '/../config/auth.php';
    $auth        = file_exists( $auth_file ) ? require $auth_file : [ 'password_hash' => '' ];
    $current_pw  = $_POST['password_current'] ?? '';
    $new_pw      = $_POST['password_new'] ?? '';
    $confirm_pw  = $_POST['password_confirm'] ?? '';

    if ( ! password_verify( $current_pw, $auth['password_hash'] ) ) {
        flash( 'pw_error', __( 'auth.error' ) );
    } elseif ( strlen( $new_pw ) < 8 ) {
        flash( 'pw_error', 'New password must be at least 8 characters.' );
    } elseif ( $new_pw !== $confirm_pw ) {
        flash( 'pw_error', 'Passwords do not match.' );
    } else {
        $hash     = password_hash( $new_pw, PASSWORD_BCRYPT, [ 'cost' => 12 ] );
        flash( 'pw_hash', $hash );
        flash( 'success', 'New hash generated. Paste it into config/auth.php.' );
    }
    redirect( BASE_URL . 'index.php?page=settings' );
}

// Load settings
$stmt     = $pdo->query( 'SELECT setting_key, setting_value FROM settings' );
$settings = [];
foreach ( $stmt->fetchAll() as $row ) {
    $settings[ $row['setting_key'] ] = $row['setting_value'];
}

$page_title = __( 'settings.title' );
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/nav.php';
?>
<main class="flex-1 md:ml-56 p-6 pt-20 md:pt-6 max-w-2xl">
    <h1 class="text-2xl font-bold mb-6"><?= __( 'settings.title' ) ?></h1>

    <?php if ( $flash = get_flash( 'success' ) ) : ?><div class="mb-4 px-4 py-3 bg-green-500/10 border border-green-500/30 rounded-md text-green-400 text-sm flash-message"><?= $flash ?></div><?php endif; ?>
    <?php if ( $flash = get_flash( 'error' ) ) : ?><div class="mb-4 px-4 py-3 bg-red-500/10 border border-red-500/30 rounded-md text-red-400 text-sm flash-message"><?= $flash ?></div><?php endif; ?>
    <?php if ( $hash = get_flash( 'pw_hash' ) ) : ?>
    <div class="mb-4 px-4 py-3 bg-slate-700 border border-slate-600 rounded-md">
        <p class="text-xs text-slate-400 mb-1">Paste this into <code>config/auth.php</code> as the value of <code>password_hash</code>:</p>
        <code class="text-xs text-cyan-400 break-all"><?= htmlspecialchars( $hash, ENT_QUOTES, 'UTF-8' ) ?></code>
    </div>
    <?php endif; ?>

    <!-- Sprint settings -->
    <div class="bg-slate-800 border border-slate-700 rounded-lg p-6 mb-4">
        <h2 class="text-sm font-semibold text-slate-300 mb-4"><?= __( 'dashboard.sprint_week' ) ?></h2>
        <form method="post" action="<?= BASE_URL ?>index.php?page=settings">
            <?= csrf_field() ?>
            <input type="hidden" name="save_settings" value="1">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs text-slate-400 mb-1"><?= __( 'settings.sprint_week' ) ?></label>
                    <input type="number" name="sprint_week" min="1" max="52"
                           value="<?= (int) ( $settings['sprint_week'] ?? 1 ) ?>"
                           class="bg-slate-700 border border-slate-600 rounded-md px-3 py-2 text-sm text-slate-100 w-full focus:outline-none focus:border-cyan-500">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1"><?= __( 'settings.sprint_total' ) ?></label>
                    <input type="number" name="sprint_total_weeks" min="1" max="52"
                           value="<?= (int) ( $settings['sprint_total_weeks'] ?? 12 ) ?>"
                           class="bg-slate-700 border border-slate-600 rounded-md px-3 py-2 text-sm text-slate-100 w-full focus:outline-none focus:border-cyan-500">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-xs text-slate-400 mb-1"><?= __( 'settings.checkpoint_date' ) ?></label>
                <input type="text" name="checkpoint_date"
                       value="<?= htmlspecialchars( $settings['checkpoint_date'] ?? '', ENT_QUOTES, 'UTF-8' ) ?>"
                       placeholder="e.g. 30 June 2026"
                       class="bg-slate-700 border border-slate-600 rounded-md px-3 py-2 text-sm text-slate-100 w-full focus:outline-none focus:border-cyan-500">
            </div>
            <button type="submit" class="bg-cyan-500 hover:bg-cyan-400 text-slate-900 font-medium px-4 py-2 rounded-md text-sm"><?= __( 'ui.save' ) ?></button>
        </form>
    </div>

    <!-- API test -->
    <div class="bg-slate-800 border border-slate-700 rounded-lg p-6 mb-4">
        <h2 class="text-sm font-semibold text-slate-300 mb-4"><?= __( 'settings.api_test' ) ?></h2>
        <div class="flex items-center gap-3">
            <button id="api-test-btn" class="bg-slate-700 hover:bg-slate-600 text-slate-100 px-4 py-2 rounded-md text-sm"><?= __( 'settings.api_test' ) ?></button>
            <span id="api-test-result" class="text-sm hidden"></span>
        </div>
    </div>

    <!-- Language -->
    <div class="bg-slate-800 border border-slate-700 rounded-lg p-6 mb-4">
        <h2 class="text-sm font-semibold text-slate-300 mb-3"><?= __( 'settings.language' ) ?></h2>
        <div class="flex gap-2">
            <?php foreach ( [ 'en' => 'English', 'es' => 'Español' ] as $code => $label ) : ?>
            <form method="post" action="<?= BASE_URL ?>api/lang.php">
                <input type="hidden" name="lang" value="<?= $code ?>">
                <button type="submit"
                        class="px-4 py-2 rounded-md text-sm transition-colors <?= ( $_SESSION['lang'] ?? 'en' ) === $code ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/50' : 'bg-slate-700 hover:bg-slate-600 text-slate-300 border border-slate-600' ?>">
                    <?= $label ?>
                </button>
            </form>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Password change -->
    <div class="bg-slate-800 border border-slate-700 rounded-lg p-6">
        <h2 class="text-sm font-semibold text-slate-300 mb-4"><?= __( 'settings.password_change' ) ?></h2>
        <?php if ( $pw_err = get_flash( 'pw_error' ) ) : ?>
        <div class="mb-4 px-4 py-3 bg-red-500/10 border border-red-500/30 rounded-md text-red-400 text-sm"><?= htmlspecialchars( $pw_err, ENT_QUOTES, 'UTF-8' ) ?></div>
        <?php endif; ?>
        <p class="text-xs text-slate-500 mb-4">Generates a new bcrypt hash. You must paste it into <code>config/auth.php</code> manually.</p>
        <form method="post" action="<?= BASE_URL ?>index.php?page=settings">
            <?= csrf_field() ?>
            <input type="hidden" name="change_password" value="1">
            <div class="space-y-4">
                <div>
                    <label class="block text-xs text-slate-400 mb-1"><?= __( 'settings.password_current' ) ?></label>
                    <input type="password" name="password_current" required autocomplete="current-password"
                           class="bg-slate-700 border border-slate-600 rounded-md px-3 py-2 text-sm text-slate-100 w-full focus:outline-none focus:border-cyan-500">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1"><?= __( 'settings.password_new' ) ?></label>
                    <input type="password" name="password_new" required minlength="8" autocomplete="new-password"
                           class="bg-slate-700 border border-slate-600 rounded-md px-3 py-2 text-sm text-slate-100 w-full focus:outline-none focus:border-cyan-500">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1"><?= __( 'settings.password_confirm' ) ?></label>
                    <input type="password" name="password_confirm" required autocomplete="new-password"
                           class="bg-slate-700 border border-slate-600 rounded-md px-3 py-2 text-sm text-slate-100 w-full focus:outline-none focus:border-cyan-500">
                </div>
            </div>
            <button type="submit" class="mt-4 bg-slate-700 hover:bg-slate-600 text-slate-100 px-4 py-2 rounded-md text-sm"><?= __( 'settings.password_change' ) ?></button>
        </form>
    </div>
</main>

<script>
document.getElementById('api-test-btn').addEventListener('click', async function () {
    const btn    = this;
    const result = document.getElementById('api-test-result');
    btn.disabled = true;
    btn.textContent = '<?= __( 'ui.loading' ) ?>';
    result.className = 'text-sm hidden';

    try {
        const res  = await fetch((window.CSUITE?.baseUrl || '/') + 'api/settings.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ action: 'test_api' }),
        });
        const data = await res.json();
        result.textContent  = data.success ? '<?= __( 'settings.api_test_ok' ) ?>' : '<?= __( 'settings.api_test_fail' ) ?>';
        result.className    = 'text-sm ' + (data.success ? 'text-green-400' : 'text-red-400');
        result.classList.remove('hidden');
    } catch (e) {
        result.textContent = '<?= __( 'settings.api_test_fail' ) ?>';
        result.className   = 'text-sm text-red-400';
        result.classList.remove('hidden');
    } finally {
        btn.disabled    = false;
        btn.textContent = '<?= __( 'settings.api_test' ) ?>';
    }
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
