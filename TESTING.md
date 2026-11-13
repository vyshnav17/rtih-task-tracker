# Verification record

Tests ran on 13 November 2026 using PHP 8.2.29, MySQL 8.0.43 and Chrome on Windows. The database started from the submitted SQL. Browser checks exercised the visible forms. Direct HTTP requests used separate cookie sessions for two throwaway accounts and bypassed browser validation where necessary.

| Test | Steps and observed result |
| --- | --- |
| Schema and PHP syntax | Imported the SQL into a fresh database. Both tables were created. PHP syntax checks passed for all four PHP files. |
| Registration and sign-in | Created an account, retried the same email and submitted a wrong password. Registration succeeded once, duplicate registration and incorrect credentials returned validation errors, and the correct password opened the dashboard. |
| Password and session | Inspected the stored value and confirmed it was a bcrypt hash. The session ID changed on successful sign-in. After sign-out, a direct request for the new-task form redirected to sign-in. |
| Create and edit | Added a task in Chrome, opened Edit, changed its status and saved. Direct requests also changed the title and description. The task appeared with the new values and a success message. Saving identical values succeeded. |
| Required fields and limits | Chrome blocked an empty new-task form. Direct POSTs with blank or all-space fields, a 121-character title, a 4,001-character description and an unknown status returned HTTP 422. |
| Delete confirmation | Opened the confirmation page and cancelled. The task remained available. Confirmed deletion removed it and displayed “Task deleted.” Its edit URL then returned 404. |
| Search and filter | With six demo tasks, searching for “the” and filtering by Pending showed two matching tasks. In Chrome, the same search with In Progress showed the two in-progress tasks. A query with no matches showed an empty state. |
| User isolation | Opened the dashboard while signed out and received a sign-in redirect. Signed in as a second account and saw an empty list. Sent GET and POST requests to the first user's edit and delete URLs with the second user's valid token. All four returned 404. A database check confirmed the original title and status were intact. |
| CSRF and invalid filters | Sent delete POSTs with a missing and an incorrect token. Both returned 403 and the task remained. An unsupported dashboard status returned 400. |
| Query and output handling | Searched for a literal percent sign, underscore and quote. An SQL-like search matched no tasks. Entered an HTML image tag as the description and checked the response contained escaped text. Reviewed all queries that accept user input for PDO parameter binding. |
| Pagination | Created ten matching tasks in the second account. The first page offered Next. An oversized page number was clamped to the last page and the footer showed 9–10 of 10. |
| Responsive layout | Applied search and status in Chrome at 390 pixels wide. Both matching task cards and their actions were visible. The document width fit the viewport without horizontal scrolling. |
| Backup and restore | Dumped the test database and restored it into a separate empty database. Counts matched on both sides, with two users and sixteen tasks. |

## Fixes found during review

- The browser test initially timed out when selecting the edit form's status field by its exact label. The label now uses an explicit association. The full browser create/edit/delete flow passed on rerun.
- Code review found a possible misleading update message if a task disappeared between loading and saving it. An update that changes zero rows now checks whether the owner's task still exists. Saving an unchanged task passed and a deleted task's URL returned 404. The precise concurrent-delete timing was reviewed in code.

## Screenshots

[Sign in](screenshots/01-sign-in.png) · [Dashboard](screenshots/02-dashboard.png) · [Edit a task](screenshots/03-edit-task.png) · [Filtered mobile view](screenshots/04-mobile-filter.png)