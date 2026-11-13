# Completion note

| Level | Completed features |
| --- | --- |
| 1 · Basic task management | Create, list and edit tasks, confirm or cancel deletion, and show a message after each successful change. MySQL stores the title, description, status, owner and creation timestamp. |
| 2 · Status, search and validation | Change among Pending, In Progress and Completed. Combine title search with a status filter. Browser and PHP validation check required fields, and server errors return the form with the user's task text. |
| 3 · Login and authorization | Register, sign in and sign out. Password hashing and verification, session-based ownership checks, PDO prepared statements, escaped output and CSRF tokens protect the task workflow. |

The optional dashboard summary and pagination are also implemented. The summary shows the total task count and the three status counts.

**Scope and limitations.** Each user has a private workspace. Collaboration, password recovery, email verification and sign-in rate limiting are outside this local assignment. Two open edit forms for the same task use the last successful save. Dates display in UTC.

**AI assistance and review.** OpenAI Codex generated the initial implementation, helped review the code and ran the tests. A browser test found that the status selector's label included its option text. The form was changed to link a separate “Status” label to the selector by ID. The browser then located it by that exact label and completed the edit. The test results describe a second review change covering unchanged updates and deleted tasks.