<?php
/**
 * Authentication Helper Functions
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is manager
 */
function isManager(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'manager';
}

/**
 * Check if user is supervisor
 */
function isSupervisor(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'supervisor';
}

/**
 * Check if user has staff role (admin, manager, or supervisor)
 */
function isStaff(): bool
{
    return isAdmin() || isManager() || isSupervisor();
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Require admin - redirect if not admin
 */
function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

/**
 * Require staff (admin, manager, or supervisor)
 */
function requireStaff(): void
{
    requireLogin();
    if (!isStaff()) {
        header('Location: dashboard.php');
        exit();
    }
}

/**
 * Get current user data
 */
function getCurrentUser(): ?array
{
    global $pdo;
    if (!isLoggedIn())
        return null;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Get role display info
 */
function getRoleInfo(string $role): array
{
    $roles = [
        'admin' => ['label' => 'Admin', 'icon' => '👑', 'color' => 'emerald'],
        'manager' => ['label' => 'Manager', 'icon' => '💼', 'color' => 'purple'],
        'supervisor' => ['label' => 'Supervisor', 'icon' => '🔰', 'color' => 'blue'],
        'user' => ['label' => 'User', 'icon' => '👤', 'color' => 'amber']
    ];
    return $roles[$role] ?? $roles['user'];
}

/**
 * Sanitize output
 */
function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Format date to Thai Buddhist Era (พ.ศ.)
 */
function thaiDate(string $datetime, bool $showTime = false): string
{
    $thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $timestamp = strtotime($datetime);
    $day = date('j', $timestamp);
    $month = $thaiMonths[(int) date('n', $timestamp)];
    $year = date('Y', $timestamp) + 543;
    $result = "$day $month $year";
    if ($showTime) {
        $result .= ' ' . date('H:i', $timestamp);
    }
    return $result;
}
