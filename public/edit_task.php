<?php
// file: public/edit_task.php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

require_login();

$errors  = [];
$taskId  = (int)($_GET['id'] ?? 0);
$userId  = (int)$_SESSION['user_id'];

if ($taskId <= 0) {
    set_flash('error', 'Invalid task ID.');
    header('Location: dashboard.php');
    exit;
}

// Fetch task with strict backend ownership verification (IDOR protection)
$stmt = $pdo->prepare('SELECT id, title, description, status FROM tasks WHERE id = :id AND user_id = :user_id LIMIT 1');
$stmt->execute([':id' => $taskId, ':user_id' => $userId]);
$task = $stmt->fetch();

if (!$task) {
    set_flash('error', 'Access denied: Task not found or you do not have permission to view or modify it.');
    header('Location: dashboard.php');
    exit;
}

$title       = $task['title'];
$description = $task['description'];
$status      = $task['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title       = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $status      = (string)($_POST['status'] ?? 'Pending');

    if ($title === '') {
        $errors[] = 'Task title is required and cannot be empty.';
    } elseif (strlen($title) > 150) {
        $errors[] = 'Task title must not exceed 150 characters.';
    }

    $allowedStatuses = ['Pending', 'In Progress', 'Completed'];
    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'Pending';
    }

    if (empty($errors)) {
        try {
            $update = $pdo->prepare('UPDATE tasks SET title = :title, description = :description, status = :status WHERE id = :id AND user_id = :user_id');
            $update->execute([
                ':title'       => $title,
                ':description' => $description !== '' ? $description : null,
                ':status'      => $status,
                ':id'          => $taskId,
                ':user_id'     => $userId
            ]);

            set_flash('success', 'Task updated successfully.');
            header('Location: dashboard.php');
            exit;
        } catch (PDOException $e) {
            error_log('Update Task Error: ' . $e->getMessage());
            $errors[] = 'Failed to update task. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task | TaskFlow</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 500px;">
        <h1 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Edit Task</h1>
        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.5rem;">Update your task details below</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <div><?= e($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="edit_task.php?id=<?= $taskId ?>" autocomplete="off" novalidate onsubmit="return validateTaskForm(this);">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label class="form-label" for="title">Task Title</label>
                <input type="text" name="title" id="title" class="form-control" value="<?= e($title) ?>" required>
                <div class="field-error" id="titleError">Task title is required.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description (Optional)</label>
                <textarea name="description" id="description" class="form-control" rows="4"><?= e($description ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="In Progress" <?= $status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Completed" <?= $status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Update Task</button>
                <a href="dashboard.php" class="btn" style="background: #e2e8f0; color: #334155; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; flex: 1;">Cancel</a>
            </div>
        </form>
    </div>
</div>
<script>
function validateTaskForm(form) {
    let valid = true;
    document.getElementById('titleError').style.display = 'none';

    if (form.title.value.trim() === '') {
        document.getElementById('titleError').style.display = 'block';
        valid = false;
    }
    return valid;
}
</script>
</body>
</html>