<?php

require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header("Location: " . BASE_URL . "/admin/login.php");
        exit;
    }
}