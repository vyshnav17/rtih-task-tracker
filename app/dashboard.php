<?php
$badge = ['Pending' => 'pending', 'In Progress' => 'progress', 'Completed' => 'done'];
$symbol = ['Pending' => '○', 'In Progress' => '◐', 'Completed' => '✓'];
function pageLink(int $number, string $search, string $filter): string
{
    return '?' . h(http_build_query(['q' => $search, 'status' => $filter, 'p' => $number]));
}
?>
<div class="heading"><h1>My tasks</h1><a class="primary" href="?page=new"><b aria-hidden="true">+</b> New task</a></div>
<section class="stats" aria-label="All my tasks by status">
<?php foreach (STATUSES as $s): ?>
<div class="stat <?= $badge[$s] ?>"><span class="stat-icon" aria-hidden="true"><?= $symbol[$s] ?></span><span class="stat-name"><?= h($s) ?></span><strong><?= $counts[$s] ?></strong></div>
<?php endforeach; ?>
</section>
<section class="list" aria-label="Task list">
<div class="list-title"><h2>Task list</h2><span><?= $total ?> total</span></div>
<form method="get" class="filters">
<label class="search"><span class="sr">Search by title</span><input type="search" name="q" maxlength="120" placeholder="Search by title" value="<?= h($search) ?>"></label>
<label><span class="sr">Filter by status</span><select name="status"><option value="">All statuses</option>
<?php foreach (STATUSES as $s): ?><option <?= $filter === $s ? 'selected' : '' ?>><?= h($s) ?></option><?php endforeach; ?>
</select></label><button class="secondary" type="submit">Apply</button>
<?php if ($search !== '' || $filter !== ''): ?><a class="clear" href="?page=dashboard">Clear</a><?php endif; ?>
</form>
<?php if (!$tasks): ?>
<div class="empty"><h3><?= $total ? 'No matching tasks' : 'Your task list is empty' ?></h3>
<p><?= $total ? 'Try another title or clear your filters.' : 'Add your first task to get started.' ?></p></div>
<?php else: ?>
<div class="row labels" aria-hidden="true"><span>Task</span><span>Status</span><span>Created (UTC)</span><span>Actions</span></div>
<ul class="tasks">
<?php foreach ($tasks as $task): ?>
<li class="row"><div class="details"><h3><?= h($task['title']) ?></h3><p><?= h($task['description']) ?></p></div>
<div><a class="badge <?= $badge[$task['status']] ?>" href="?page=edit&amp;id=<?= (int)$task['id'] ?>"
    data-task-id="<?= (int)$task['id'] ?>" data-status="<?= h($task['status']) ?>"
    aria-label="<?= h($task['status'] . ' — change status for ' . $task['title']) ?>">
    <i aria-hidden="true"></i><?= h($task['status']) ?></a></div>
<time datetime="<?= h(str_replace(' ', 'T', $task['created_at']) . 'Z') ?>"><?= h(gmdate('d M Y', strtotime($task['created_at'] . ' UTC'))) ?></time>
<div class="row-actions"><a href="?page=edit&amp;id=<?= (int)$task['id'] ?>" aria-label="Edit <?= h($task['title']) ?>">Edit</a><a href="?page=delete&amp;id=<?= (int)$task['id'] ?>" aria-label="Delete <?= h($task['title']) ?>">Delete</a></div>
</li>
<?php endforeach; ?></ul>
<nav class="pager" aria-label="Task pages"><span><?= $offset + 1 ?>–<?= min($offset + PAGE_SIZE, $matches) ?> of <?= $matches ?> <?= $matches === 1 ? 'task' : 'tasks' ?></span>
<?php if ($current > 1): ?><a href="<?= pageLink($current - 1, $search, $filter) ?>">← Previous</a><?php endif; ?>
<?php if ($current < $pages): ?><a href="<?= pageLink($current + 1, $search, $filter) ?>">Next →</a><?php endif; ?></nav>
<?php endif; ?></section>
<?php if ($tasks): ?>
<dialog class="status-dialog" id="status-dialog" aria-labelledby="status-heading" aria-describedby="status-task">
    <h2 id="status-heading">Change status</h2>
    <p id="status-task" class="muted"></p>
    <form method="post">
        <?= tokenField() ?>
        <input type="hidden" name="task_id">
        <?php foreach (STATUSES as $s): ?>
        <button type="submit" name="status" value="<?= h($s) ?>" class="status-choice <?= $badge[$s] ?>">
            <i aria-hidden="true"></i><?= h($s) ?><span aria-hidden="true" class="check">✓</span>
        </button>
        <?php endforeach; ?>
    </form>
    <form method="dialog"><button class="quiet">Cancel</button></form>
</dialog>
<script src="status.js" defer></script>
<?php endif; ?>
