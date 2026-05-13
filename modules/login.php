<?php
declare(strict_types=1);

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( ! csrf_verify() ) {
        flash( 'error', __( 'flash.csrf' ) );
    } else {
        $password   = $_POST['password'] ?? '';
        $auth_file  = __DIR__ . '/../config/auth.php';
        $auth       = file_exists( $auth_file ) ? require $auth_file : [ 'password_hash' => '' ];

        if ( password_verify( $password, $auth['password_hash'] ) ) {
            session_regenerate_id( true );
            $_SESSION['authenticated'] = true;
            $_SESSION['lang']          = $_SESSION['lang'] ?? 'en';
            redirect( BASE_URL . 'index.php?page=dashboard' );
        } else {
            flash( 'auth_error', __( 'auth.error' ) );
        }
    }
}

$error = get_flash( 'auth_error' );
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars( $_SESSION['lang'] ?? 'en', ENT_QUOTES, 'UTF-8' ) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __( 'auth.login' ) ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/output.css">
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center">
<div class="w-full max-w-sm">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold"><span class="text-cyan-400">csuite</span><span class="text-slate-400">-crm</span></h1>
    </div>
    <div class="bg-slate-800 border border-slate-700 rounded-lg p-8">
        <?php if ( $error ) : ?>
        <div class="mb-4 px-4 py-3 bg-red-500/10 border border-red-500/30 rounded-md text-red-400 text-sm">
            <?= $error ?>
        </div>
        <?php endif; ?>
        <form method="post" action="<?= BASE_URL ?>index.php?page=login">
            <?= csrf_field() ?>
            <div class="mb-6">
                <label for="password" class="block text-sm text-slate-400 mb-2"><?= __( 'auth.password' ) ?></label>
                <input type="password"
                       id="password"
                       name="password"
                       autocomplete="current-password"
                       required
                       class="bg-slate-700 border border-slate-600 rounded-md px-3 py-2 text-sm text-slate-100 w-full focus:outline-none focus:border-cyan-500">
            </div>
            <button type="submit"
                    class="w-full bg-cyan-500 hover:bg-cyan-400 text-slate-900 font-medium px-4 py-2 rounded-md text-sm transition-colors">
                <?= __( 'auth.login' ) ?>
            </button>
        </form>
    </div>
</div>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
