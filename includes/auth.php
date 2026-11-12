<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool
    {
        return !empty($_SESSION['admin_id']);
    }
}

if (!function_exists('currentAdmin')) {
    function currentAdmin(): array
    {
        return [
            'id'   => $_SESSION['admin_id'] ?? null,
            'name' => $_SESSION['admin_name'] ?? 'Pingaksh Admin',
            'role' => currentUserRole(),
        ];
    }
}

if (!function_exists('currentUserRole')) {
    function currentUserRole(): string
    {
        global $pdo;

        $role = $_SESSION['role'] ?? $_SESSION['admin_role'] ?? '';

        // Always verify role from database if session role is missing or empty
        if (($role === '' || $role === null) && !empty($_SESSION['admin_id']) && isset($pdo)) {
            try {
                $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
                $stmt->execute([$_SESSION['admin_id']]);
                $dbRole = $stmt->fetchColumn();
                if ($dbRole) {
                    $role = ($dbRole === 'admin') ? 'super_admin' : $dbRole;
                    $_SESSION['role'] = $role;
                    $_SESSION['admin_role'] = $role;
                }
            } catch (Exception $e) {
                // Ignore DB lookup failure and fall back safely
            }
        }

        if ($role === 'admin' || $role === '' || $role === null) {
            return 'super_admin';
        }

        return (string)$role;
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin(): void
    {
        if (empty($_SESSION['admin_id'])) {
            header('Location: ' . BASE_URL . '/admin/login.php');
            exit;
        }

        $role = currentUserRole();

        // Strictly lock carbon_editor to /admin/carbon/* pages only
        if ($role === 'carbon_editor') {
            $currentScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            if (strpos($currentScript, '/admin/carbon/') === false) {
                header('Location: ' . BASE_URL . '/admin/carbon/index.php');
                exit;
            }
        }
    }
}

if (!function_exists('requireRole')) {
    function requireRole(array $allowedRoles = []): void
    {
        requireLogin();

        $userRole = currentUserRole();
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
}