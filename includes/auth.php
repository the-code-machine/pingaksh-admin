<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

function currentUserRole(): string
{
    $role = $_SESSION['role'] ?? $_SESSION['admin_role'] ?? '';

    // Refresh role from DB if session has an empty or legacy role value
    if ($role === '' && !empty($_SESSION['admin_id'])) {
        require __DIR__ . '/../config/database.php';
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['admin_id']]);
        $dbRole = $stmt->fetchColumn();
        if ($dbRole) {
            $role = ($dbRole === 'admin') ? 'super_admin' : $dbRole;
            $_SESSION['role'] = $role;
            $_SESSION['admin_role'] = $role;
        }
    }

    if ($role === 'admin' || $role === '') {
        return 'super_admin';
    }

    return $role;
}

function requireLogin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }

    $role = currentUserRole();

    // Global Lock: If user is carbon_editor, ONLY allow URLs inside /admin/carbon/
    if ($role === 'carbon_editor') {
        $currentScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (strpos($currentScript, '/admin/carbon/') === false) {
            header('Location: ' . BASE_URL . '/admin/carbon/index.php');
            exit;
        }
    }
}

function requireRole(array $allowedRoles = []): void
{
    requireLogin();

    $userRole = currentUserRole();

    // Treat legacy 'admin' and 'super_admin' as equivalent
    if ($userRole === 'admin') {
        $userRole = 'super_admin';
    }

    if (!in_array($userRole, $allowedRoles, true)) {
        http_response_code(403);
        die('<div style="padding: 50px; font-family: sans-serif; text-align: center;">
                <h1 style="color: #972811;">403 Access Denied</h1>
                <p>Your account role (' . htmlspecialchars($userRole) . ') does not have permission to view this module.</p>
                <a href="' . BASE_URL . '/admin/carbon/index.php" style="color: #121C4D; font-weight: bold;">Return to Carbon Dashboard</a>
             </div>');
    }
}