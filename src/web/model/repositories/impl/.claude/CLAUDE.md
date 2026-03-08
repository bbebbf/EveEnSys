# Repository Implementation Rules

## 1. Always free database results and statements

Every `mysqli_result` must be explicitly freed and every `mysqli_stmt` must be explicitly closed after use.

- After a SELECT via prepared statement: call `$result->free()` then `$stmt->close()`
- After a SELECT via `$this->db->query()`: call `$result->free()`
- After a DML statement (INSERT, UPDATE, DELETE): call `$stmt->close()`
- In loops that reuse the same `$stmt` (e.g. GUID generation): free `$result` each iteration, close `$stmt` once after the loop (including all exit paths)
- When reading `insert_id` after an INSERT: read `$this->db->insert_id` before calling `$stmt->close()`
