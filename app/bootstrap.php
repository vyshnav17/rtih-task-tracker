<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
    'path' => '/',
]);
session_name('taskfolio_session');
session_start();
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
const STATUSES = ['Pending', 'In Progress', 'Completed'];
const PAGE_SIZE = 8;

function db(): PDO
{
    static $connection;
    if ($connection === null) {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_NAME') ?: 'rtih_task_tracker';
        $user = getenv('DB_USER');
        if ($user === false || $user === '') {
            throw new RuntimeException('Missing database configuration');
        }
        $connection = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
            $user, (string)getenv('DB_PASSWORD'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        $connection->exec("SET time_zone = '+00:00'");
    }
    return $connection;
}

function query(string $sql, array $params): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function field(array $source, string $key): string
{
    return is_string($source[$key] ?? null) ? $source[$key] : '';
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function go(string $page, string $message = ''): never
{
    if ($message !== '') {
        $_SESSION['notice'] = $message;
    }
    header('Location: index.php?page=' . rawurlencode($page), true, 303);
    exit;
}

function tokenField(): string
{
    return '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
}

function validateTask(string $title, string $description, string $status): array
{
    $errors = [];
    if ($title === '' || !mb_check_encoding($title, 'UTF-8') || mb_strlen($title) > 120) {
        $errors['title'] = 'Enter a title between 1 and 120 characters.';
    }
    if ($description === '' || !mb_check_encoding($description, 'UTF-8') || mb_strlen($description) > 4000) {
        $errors['description'] = 'Enter a description between 1 and 4,000 characters.';
    }
    if (!in_array($status, STATUSES, true)) {
        $errors['status'] = 'Choose a status from the list.';
    }
    return $errors;
}