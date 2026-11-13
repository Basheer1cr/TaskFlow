<?php
// file: public/dashboard.php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

require_login();

$userId   = (int)$_SESSION['user_id'];
$statuses = allowed_statuses();

// Handle POST Actions: Update Task Status & Delete Task
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_write_permission();

    $action = (string)($_POST['action'] ?? '');
    $taskId = (int)($_POST['task_id'] ?? 0);

    if ($action === 'update_status') {
        $newStatus = (string)($_POST['status'] ?? '');
        if ($taskId <= 0 || !in_array($newStatus, $statuses, true)) {
            set_flash('error', 'Invalid task or status value.');
        } else {
            try {
                // Backend ownership enforcement (user_id check)
                $stmt = $pdo->prepare('UPDATE tasks SET status = :status WHERE id = :id AND user_id = :user_id');
                $stmt->execute([
                    ':status'  => $newStatus,
                    ':id'      => $taskId,
                    ':user_id' => $userId,
                ]);
                if ($stmt->rowCount() > 0) {
                    set_flash('success', 'Task status updated successfully.');
                } else {
                    set_flash('error', 'Task not found, unauthorized, or status unchanged.');
                }
            } catch (PDOException $e) {
                error_log('Status Update Error: ' . $e->getMessage());
                set_flash('error', 'Unable to update task status.');
            }
        }
        header('Location: dashboard.php');
        exit;
    }

    if ($action === 'delete') {
        if ($taskId <= 0) {
            set_flash('error', 'Invalid task selected.');
        } else {
            try {
                // Backend ownership enforcement (user_id check)
                $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id AND user_id = :user_id');
                $stmt->execute([
                    ':id'      => $taskId,
                    ':user_id' => $userId,
                ]);
                if ($stmt->rowCount() > 0) {
                    set_flash('success', 'Task deleted successfully.');
                } else {
                    set_flash('error', 'Unauthorized action or task not found.');
                }
            } catch (PDOException $e) {
                error_log('Delete Task Error: ' . $e->getMessage());
                set_flash('error', 'Unable to delete task.');
            }
        }
        header('Location: dashboard.php');
        exit;
    }
}

// Handle GET Search by Title & Filter by Status (Level 2)
$search       = trim((string)($_GET['search'] ?? ''));
$filterStatus = (string)($_GET['status'] ?? '');

$sql    = 'SELECT id, title, description, status, created_at FROM tasks WHERE user_id = :user_id';
$params = [':user_id' => $userId];

if ($search !== '') {
    $sql .= ' AND title LIKE :search';
    $params[':search'] = '%' . $search . '%';
}

if ($filterStatus !== '' && in_array($filterStatus, $statuses, true)) {
    $sql .= ' AND status = :status';
    $params[':status'] = $filterStatus;
}

$sql .= ' ORDER BY created_at DESC, id DESC';

$tasks = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Dashboard Fetch Error: ' . $e->getMessage());
}

$flash = get_flash();

function badge_class(string $status): string
{
    return match ($status) {
        'Completed'   => 'badge-completed',
        'In Progress' => 'badge-progress',
        default       => 'badge-pending',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | TaskFlow</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="dashboard.php" class="navbar-brand">TaskFlow</a>
    <div class="navbar-nav">
        <a href="dashboard.php" class="nav-link active">My Tasks</a>
        <a href="create_task.php" class="nav-link">+ New Task</a>
        <form method="POST" action="logout.php" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="btn btn-logout">Logout</button>
        </form>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <div>
            <h1>Welcome back, <?= e((string)$_SESSION['name']) ?>!</h1>
            <p>Manage and track your tasks (Role: <?= e((string)$_SESSION['role']) ?>)</p>
        </div>
        <div style="color: #64748b; font-size: 0.9rem;">
            <?= e(date('l, d F Y')) ?>
        </div>
    </div>

    <!-- Flash Message Display Banner -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" style="margin-bottom: 1.5rem;">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- Level 2: Search by Title & Filter by Status -->
    <div class="card">
        <form method="GET" action="dashboard.php" class="filter-grid">
            <div>
                <label class="form-label" for="search">Search by Title</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Search task title..." value="<?= e($search) ?>">
            </div>
            <div>
                <label class="form-label" for="status">Filter by Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $st): ?>
                        <option value="<?= e($st) ?>" <?= $filterStatus === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="dashboard.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Level 1, 2 & 3: Task List Table -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 style="font-size: 1.15rem; font-weight: 700;">Saved Tasks (<?= count($tasks) ?>)</h2>
            <a href="create_task.php" class="btn btn-primary" style="padding: 0.5rem 0.95rem; font-size: 0.85rem;">+ Add Task</a>
        </div>

        <div class="table-responsive">
            <table class="task-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title & Description</th>
                        <th>Status</th>
                        <th>Change Status</th>
                        <th>Created At</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748b; padding: 2rem;">
                            No tasks found matching your criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td style="color: #64748b; font-size: 0.85rem;">#<?= (int)$task['id'] ?></td>
                            <td>
                                <div class="task-title"><?= e($task['title']) ?></div>
                                <?php if (!empty($task['description'])): ?>
                                    <div class="task-desc"><?= nl2br(e($task['description'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= badge_class($task['status']) ?>">
                                    <?= e($task['status']) ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="dashboard.php">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                                    <select name="status" class="status-select" aria-label="Change status" onchange="this.form.submit()">
                                        <?php foreach ($statuses as $st): ?>
                                            <option value="<?= e($st) ?>" <?= $task['status'] === $st ? 'selected' : '' ?>>
                                                <?= e($st) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td style="font-size: 0.85rem; color: #64748b; white-space: nowrap;">
                                <?= e($task['created_at']) ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="edit_task.php?id=<?= (int)$task['id'] ?>" class="btn btn-edit-outline">Edit</a>
                                <form method="POST" action="dashboard.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this task?');">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                                    <button type="submit" class="btn btn-danger-outline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>