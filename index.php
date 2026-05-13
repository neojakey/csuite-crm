<?php
declare(strict_types=1);

// Session configuration — must happen before session_start()
session_set_cookie_params([
    'lifetime' => 28800,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

// Load app config (defines APP_NAME, VERSION, BASE_URL)
require_once __DIR__ . '/config/app.php';

// ── i18n helper ───────────────────────────────────────────────────────────────
function __( string $key, array $replace = [] ): string {
    static $strings = [];
    if ( empty( $strings ) ) {
        $lang = $_SESSION['lang'] ?? 'en';
        $file = __DIR__ . '/lang/' . $lang . '.php';
        if ( ! file_exists( $file ) ) {
            $file = __DIR__ . '/lang/en.php';
        }
        $strings = require $file;
    }
    $value = $strings[ $key ] ?? $key;
    foreach ( $replace as $placeholder => $replacement ) {
        $value = str_replace( ':' . $placeholder, $replacement, $value );
    }
    return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

// ── CSRF helpers ──────────────────────────────────────────────────────────────
function csrf_token(): string {
    if ( empty( $_SESSION['csrf_token'] ) ) {
        $_SESSION['csrf_token'] = bin2hex( random_bytes( 32 ) );
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? '';
    if ( empty( $_SESSION['csrf_token'] ) || $token === '' ) {
        return false;
    }
    return hash_equals( $_SESSION['csrf_token'], $token );
}

// ── Flash helpers ─────────────────────────────────────────────────────────────
function flash( string $key, string $message ): void {
    $_SESSION['flash'][ $key ] = $message;
}

function get_flash( string $key ): string {
    $message = $_SESSION['flash'][ $key ] ?? '';
    unset( $_SESSION['flash'][ $key ] );
    return $message;
}

// ── Redirect helper ───────────────────────────────────────────────────────────
function redirect( string $url ): never {
    header( 'Location: ' . $url );
    exit;
}

// ── HTML escape helper ────────────────────────────────────────────────────────
function e( string $value ): string {
    return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

// ── Routing ───────────────────────────────────────────────────────────────────
$page = $_GET['page'] ?? 'dashboard';
$page = preg_replace( '/[^a-z_]/', '', strtolower( $page ) ) ?? 'dashboard';

// Logout
if ( $page === 'logout' ) {
    session_destroy();
    header( 'Location: ' . BASE_URL . 'index.php?page=login' );
    exit;
}

// Auth gate — login is the only public page
if ( $page !== 'login' && empty( $_SESSION['authenticated'] ) ) {
    redirect( BASE_URL . 'index.php?page=login' );
}

// Redirect authenticated users away from login
if ( $page === 'login' && ! empty( $_SESSION['authenticated'] ) ) {
    redirect( BASE_URL . 'index.php?page=dashboard' );
}

// Allowlist
$allowed_pages = [ 'login', 'dashboard', 'contacts', 'pipeline', 'agent', 'notes', 'tasks', 'settings' ];
if ( ! in_array( $page, $allowed_pages, true ) ) {
    $page = 'dashboard';
}

require __DIR__ . '/modules/' . $page . '.php';
