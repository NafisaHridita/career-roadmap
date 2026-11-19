<?php
// Shared login guard. Include it at the top of a page, then call requireRole().
if (session_status() === PHP_SESSION_NONE) session_start();

// [ashasuborna] Login and role guard; this file was empty before (#35)
function requireRole(string $role, string $loginPath = '../login.php'): void
{
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== $role) {
        header('Location: ' . $loginPath);
        exit();
    }
}
?>
