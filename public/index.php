<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$page = array_key_exists('page', $_GET) ? field($_GET, 'page') : 'dashboard';
$post = $_SERVER['REQUEST_METHOD'] === 'POST';
$owner = $_SESSION['user_id'] ?? null;
$notice = $_SESSION['notice'] ?? '';
unset($_SESSION['notice']);
$errors = [];
$email = $title = $description = '';
$status = 'Pending';
$errorMessage = '';

function failPage(int $code, string $message): never
{
    http_response_code($code);
    $page = 'error';
    $errorMessage = $message;
    $notice = '';
    require __DIR__ . '/../app/layout.php';
    exit;
}

try {
    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        failPage(405, 'Use a link or form to open this page.');
    }
    if (!in_array($page, ['dashboard', 'login', 'register', 'logout', 'new', 'edit', 'delete'], true)) {
        failPage(404, 'This page could not be found.');
    }
    $authPage = in_array($page, ['login', 'register'], true);
    if (!$authPage && !$owner) {
        go('login');
    }
    if ($authPage && $owner) {
        go('dashboard');
    }
    if ($post && !hash_equals($_SESSION['csrf'], field($_POST, 'csrf'))) {
        failPage(403, 'Your form has expired. Reopen it and try again.');
    }
    if ($page === 'logout') {
        if (!$post) {
            header('Allow: POST');
            failPage(405, 'Use the sign out button to end your session.');
        }
        $_SESSION = [];
        session_destroy();
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $cookie['path'], $cookie['domain'], $cookie['secure'], true);
        go('login');
    }
    if ($authPage && $post) {
        $email = strtolower(trim(field($_POST, 'email')));
        $password = field($_POST, 'password');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if ($page === 'register' && (mb_strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0"))) {
            $errors['password'] = 'Use at least 8 characters and at most 72 bytes.';
        }
        if (!$errors && $page === 'register') {
            try {
                query('INSERT INTO users (email, password_hash) VALUES (?, ?)',
                    [$email, password_hash($password, PASSWORD_DEFAULT)]);
                go('login', 'Account created. Sign in to open your workspace.');
            } catch (PDOException $e) {
                if (($e->errorInfo[1] ?? 0) !== 1062) {
                    throw $e;
                }
                $errors['email'] = 'This address is already registered. Sign in to continue.';
            }
        }
        if (!$errors && $page === 'login') {
            $user = query('SELECT id, email, password_hash FROM users WHERE email = ?', [$email])->fetch();
            if ($user && strlen($password) <= 72 && !str_contains($password, "\0") && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION = ['user_id' => (int)$user['id'], 'email' => $user['email'], 'csrf' => bin2hex(random_bytes(32))];
                go('dashboard');
            }
            $errors['password'] = 'The email or password is incorrect.';
        }
    }

    if (in_array($page, ['edit', 'delete'], true)) {
        $id = filter_var(field($_GET, 'id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $task = $id ? query('SELECT title, description, status FROM tasks WHERE id = ? AND user_id = ?', [$id, $owner])->fetch() : false;
        if (!$task) {
            failPage(404, 'This task could not be found in your workspace.');
        }
        ['title' => $title, 'description' => $description, 'status' => $status] = $task;
    }
    if ($page === 'delete' && $post) {
        $removed = query('DELETE FROM tasks WHERE id = ? AND user_id = ?', [$id, $owner]);
        go('dashboard', $removed->rowCount() ? 'Task deleted.' : 'This task has already been deleted.');
    }
    if (in_array($page, ['new', 'edit'], true) && $post) {
        $title = trim(field($_POST, 'title'));
        $description = trim(field($_POST, 'description'));
        $status = field($_POST, 'status');
        $errors = validateTask($title, $description, $status);
        if (!$errors && $page === 'edit') {
            $updated = query('UPDATE tasks SET title = ?, description = ?, status = ? WHERE id = ? AND user_id = ?',
                [$title, $description, $status, $id, $owner]);
            // MySQL also reports zero changed rows when the submitted values match.
            if (!$updated->rowCount() && !query('SELECT id FROM tasks WHERE id = ? AND user_id = ?', [$id, $owner])->fetchColumn()) {
                failPage(404, 'This task was deleted before your changes could be saved.');
            }
            go('dashboard', 'Task updated.');
        }
        if (!$errors) {
            query('INSERT INTO tasks (user_id, title, description, status) VALUES (?, ?, ?, ?)',
                [$owner, $title, $description, $status]);
            go('dashboard', 'Task added.');
        }
    }
    if ($page === 'dashboard') {
        $search = trim(field($_GET, 'q'));
        $filter = field($_GET, 'status');
        if (mb_strlen($search) > 120 || ($filter !== '' && !in_array($filter, STATUSES, true))) {
            failPage(400, 'Check the search term and status, then try again.');
        }
        if ($post) {
            $id = filter_var(field($_POST, 'task_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $status = field($_POST, 'status');
            if (!$id || !in_array($status, STATUSES, true)) {
                failPage(422, 'Choose a task and a status from the list.');
            }
            $updated = query('UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?', [$status, $id, $owner]);
            if (!$updated->rowCount() && !query('SELECT id FROM tasks WHERE id = ? AND user_id = ?', [$id, $owner])->fetchColumn()) {
                failPage(404, 'This task could not be found in your workspace.');
            }
            $_SESSION['notice'] = 'Task status saved as ' . $status . '.';
            $view = ['page' => 'dashboard', 'q' => $search, 'status' => $filter, 'p' => field($_GET, 'p')];
            header('Location: index.php?' . http_build_query(array_filter($view, 'strlen')), true, 303);
            exit;
        }
        $counts = array_fill_keys(STATUSES, 0);
        foreach (query('SELECT status, COUNT(*) AS n FROM tasks WHERE user_id = ? GROUP BY status', [$owner]) as $row) {
            $counts[$row['status']] = (int)$row['n'];
        }
        $total = array_sum($counts);
        $where = ' WHERE user_id = ?';
        $params = [$owner];
        if ($search !== '') {
            $where .= ' AND LOCATE(?, title) > 0';
            $params[] = $search;
        }
        if ($filter !== '') {
            $where .= ' AND status = ?';
            $params[] = $filter;
        }
        $matches = (int)query('SELECT COUNT(*) FROM tasks' . $where, $params)->fetchColumn();
        $pages = max(1, (int)ceil($matches / PAGE_SIZE));
        $current = max(1, min($pages, (int)field($_GET, 'p')));
        $offset = ($current - 1) * PAGE_SIZE;
        // LIMIT and OFFSET are integers derived above. All submitted values are bound.
        $tasks = query('SELECT id, title, description, status, created_at FROM tasks' . $where
            . ' ORDER BY created_at DESC, id DESC LIMIT ' . PAGE_SIZE . ' OFFSET ' . $offset, $params)->fetchAll();
    }
    if ($errors) {
        http_response_code(422);
    }
    require __DIR__ . '/../app/layout.php';
} catch (Throwable $e) {
    error_log('Taskfolio request failed (' . get_class($e) . ').');
    failPage(500, 'The workspace is temporarily unavailable. Check the database setup and try again.');
}
