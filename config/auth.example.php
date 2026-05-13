<?php
/**
 * csuite-crm — Authentication Configuration
 *
 * Copy this file to config/auth.php.
 * Generate a bcrypt hash with:
 *   php -r "echo password_hash('your-password', PASSWORD_BCRYPT, ['cost' => 12]);"
 * Paste the result as the value below.
 *
 * config/auth.php is gitignored — your hash never enters version control.
 */
return [
    'password_hash' => '$2y$12$replacethiswithyourrealbcrypthashgeneratedviaphpcli00000',
];
