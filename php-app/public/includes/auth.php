<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function currentAdminId(): ?int
{
    $id = $_SESSION['admin_id'] ?? null;
    return is_int($id) && $id > 0 ? $id : null;
}

function loginAdmin(int $adminId): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $adminId;
}

function logoutAdmin(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function requireAdminAuth(): void
{
    if (currentAdminId() !== null) {
        return;
    }

    header('Location: /login.php?error=' . urlencode('Faça login para acessar o painel.'));
    exit;
}

function redirectIfAuthenticated(string $path = '/dashboard.php'): void
{
    if (currentAdminId() !== null) {
        header('Location: ' . $path);
        exit;
    }
}
