<?php
declare(strict_types=1);

session_set_cookie_params( [ 'lifetime' => 28800, 'httponly' => true, 'samesite' => 'Strict' ] );
session_start();

$allowed = [ 'en', 'es' ];
$lang    = $_POST['lang'] ?? 'en';

if ( in_array( $lang, $allowed, true ) ) {
    $_SESSION['lang'] = $lang;
}

$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header( 'Location: ' . $redirect );
exit;
