<!DOCTYPE html>
<html lang="<?= htmlspecialchars( $_SESSION['lang'] ?? 'en', ENT_QUOTES, 'UTF-8' ) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset( $page_title ) ? htmlspecialchars( $page_title, ENT_QUOTES, 'UTF-8' ) . ' — ' : '' ?><?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/output.css">
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen flex">
