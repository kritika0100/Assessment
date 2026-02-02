<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

/* Helpers */

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/* CSRF */

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_verify(?string $token): bool {
    return isset($_SESSION['csrf'])
        && is_string($token)
        && hash_equals($_SESSION['csrf'], $token);
}

/* Session user */

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(string $role = ''): void {
    $u = current_user();
    if (!$u) {
        header('Location: index.php');
        exit;
    }

    if ($role && ($u['role'] ?? '') !== $role) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function is_post(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/* Flash messages */

function flash_set(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array {
    if (!isset($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

/* Auth */

function password_hash_str(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}

function password_verify_str(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/* Database helpers */

function db_one(PDO $pdo, string $sql, array $params = []): ?array {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row ?: null;
}

function db_all(PDO $pdo, string $sql, array $params = []): array {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function db_exec(PDO $pdo, string $sql, array $params = []): int {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}
