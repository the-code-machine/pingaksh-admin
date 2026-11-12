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
// Add this below your existing requireLogin() function in auth.php
function requireRole($allowedRoles = []) {
    requireLogin(); // Ensure they are logged in first
    
    // Default to super_admin if session role is not set
    $userRole = $_SESSION['role'] ?? 'super_admin'; 
    
    if (!in_array($userRole, $allowedRoles, true)) {
        http_response_code(403);
        die('<div style="padding: 50px; font-family: sans-serif; text-align: center;">
                <h1 style="color: #972811;">403 Access Denied</h1>
                <p>Your account role ('.htmlspecialchars($userRole).') does not have permission to view this module.</p>
                <a href="'.BASE_URL.'/admin/carbon/index.php" style="color: #121C4D;">Return to Carbon Dashboard</a>
             </div>');
    }
}