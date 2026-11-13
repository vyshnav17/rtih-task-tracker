<?php
$headings = ['login' => 'Welcome back', 'register' => 'Create an account', 'dashboard' => 'My tasks',
    'new' => 'New task', 'edit' => 'Edit task', 'delete' => 'Delete task?', 'error' => 'Unable to continue'];
$home = isset($_SESSION['user_id']) ? 'dashboard' : 'login';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($headings[$page]) ?> · Taskfolio</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header><div class="bar">
<a class="brand" href="?page=<?= $home ?>"><span class="mark" aria-hidden="true">✓</span>Taskfolio</a>
<?php if (isset($_SESSION['user_id'])): ?>
<div class="account"><span><?= h($_SESSION['email']) ?></span>
<form method="post" action="?page=logout"><?= tokenField() ?><button class="quiet" type="submit">Sign out</button></form></div>
<?php endif; ?>
</div></header>
<main id="main" class="<?= $page === 'dashboard' ? 'workspace' : 'narrow' ?>">
<?php if ($notice !== ''): ?><div class="notice" role="status"><?= h($notice) ?></div><?php endif; ?>
<?php if ($page === 'dashboard'): require __DIR__ . '/dashboard.php'; ?>
<?php elseif ($page === 'error'): ?>
<section class="panel"><h1><?= h($headings[$page]) ?></h1><p><?= h($errorMessage) ?></p><a href="?page=<?= $home ?>">Return to <?= $home === 'dashboard' ? 'my tasks' : 'sign in' ?></a></section>
<?php elseif ($page === 'login' || $page === 'register'): ?>
<section class="panel auth"><h1><?= h($headings[$page]) ?></h1>
<p class="muted"><?= $page === 'login' ? 'Sign in to your task workspace.' : 'Start a private workspace for your tasks.' ?></p>
<form method="post" class="form-stack">
<?= tokenField() ?>
<label>Email address<input type="email" name="email" maxlength="254" autocomplete="email" required value="<?= h($email) ?>"></label>
<?php if (isset($errors['email'])): ?><p class="error" role="alert"><?= h($errors['email']) ?></p><?php endif; ?>
<label>Password<input type="password" name="password" required autocomplete="<?= $page === 'login' ? 'current-password' : 'new-password' ?>" <?= $page === 'register' ? 'minlength="8"' : '' ?>></label>
<?php if ($page === 'register'): ?><small>Use at least 8 characters, up to 72 bytes.</small><?php endif; ?>
<?php if (isset($errors['password'])): ?><p class="error" role="alert"><?= h($errors['password']) ?></p><?php endif; ?>
<button class="primary" type="submit"><?= $page === 'login' ? 'Sign in' : 'Create account' ?></button>
</form>
<p class="switch"><?= $page === 'login' ? 'New to Taskfolio? <a href="?page=register">Create an account</a>' : 'Already registered? <a href="?page=login">Sign in</a>' ?></p></section>
<?php else: ?>
<a href="?page=dashboard" class="back">← My tasks</a>
<section class="panel"><h1><?= h($headings[$page]) ?></h1>
<?php if ($page === 'delete'): ?>
<p>This will permanently delete <strong><?= h($title) ?></strong> from your workspace.</p>
<form method="post" class="actions"><?= tokenField() ?><button class="danger" type="submit">Delete task</button><a class="secondary" href="?page=dashboard">Cancel</a></form>
<?php else: ?>
<form method="post" class="form-stack"><?= tokenField() ?>
<label>Title<input name="title" required maxlength="120" value="<?= h($title) ?>"></label>
<?php if (isset($errors['title'])): ?><p class="error" role="alert"><?= h($errors['title']) ?></p><?php endif; ?>
<label>Description<textarea name="description" required maxlength="4000" rows="5"><?= h($description) ?></textarea></label>
<?php if (isset($errors['description'])): ?><p class="error" role="alert"><?= h($errors['description']) ?></p><?php endif; ?>
<div class="form-field"><label for="task-status">Status</label>
<select id="task-status" name="status" required>
<?php foreach (STATUSES as $option): ?><option <?= $status === $option ? 'selected' : '' ?>><?= h($option) ?></option><?php endforeach; ?>
</select></div>
<?php if (isset($errors['status'])): ?><p class="error" role="alert"><?= h($errors['status']) ?></p><?php endif; ?>
<div class="actions"><button class="primary" type="submit"><?= $page === 'new' ? 'Add task' : 'Save changes' ?></button><a class="secondary" href="?page=dashboard">Cancel</a></div>
</form>
<?php endif; ?></section>
<?php endif; ?>
</main></body></html>
