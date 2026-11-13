const dialog = document.getElementById('status-dialog');

if (typeof dialog.showModal === 'function') {
    for (const badge of document.querySelectorAll('a[data-task-id]')) {
        badge.setAttribute('aria-haspopup', 'dialog');
        badge.addEventListener('click', (event) => {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            dialog.querySelector('[name="task_id"]').value = badge.dataset.taskId;
            dialog.querySelector('#status-task').textContent = badge.closest('li').querySelector('h3').textContent;
            for (const choice of dialog.querySelectorAll('[name="status"]')) {
                choice.setAttribute('aria-current', String(choice.value === badge.dataset.status));
            }
            dialog.showModal();
            dialog.querySelector('[aria-current="true"]').focus();
        });
    }
}
