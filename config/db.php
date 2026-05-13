<?php
require_once __DIR__ . '/../classes/Database.php';

function db(): PDO {
    return Database::getInstance();
}
