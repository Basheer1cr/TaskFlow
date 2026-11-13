<?php
// file: public/create_task.php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

require_login();

$userId      = (int)$_SESSION['user_id'];
$statuses    = allowed_statuses();
$errors      = [];
$title       = '';
$description = '';
$status      = 'Pending';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_write_permission();

    $title       = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $status      = (string)($_POST['status'] ?? 'Pending');

    // Backend validation (Level 2)
    if ($title === '') {
        $errors[] = 'Task title is required and cannot be empty.';
    } elseif (strlen($title) > 150) {
        $errors[] = 'Task title cannot exceed 150 characters.';
    }

    if (!in_array($status, $statuses, true)) {
        $errors[] = 'Please select a valid task status.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO tasks (user_id, title, description, status) VALUES (:user_id, :title, :description, :status)'
            );
            $stmt->execute([
                ':user_id'     => $userId,
                ':title'       => $title,
                ':description' => $description,
                ':status'      => $status,
            ]);

            set_flash('success', 'Task added successfully.');
            header('Location: dashboard.php');
            exit;
        } catch (PDOException $e) {
            error_log('Create Task Error: ' . $e->getMessage());
            $errors[] = 'Failed to save task. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Task | TaskFlow</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="dashboard.php" class="navbar-brand">TaskFlow</a>
    <div class="navbar-nav">
        <a href="dashboard.php" class="nav-link">Back to Tasks</a>
    </div>
</nav>

<div class="container">
    <div class="card form-card">
        <h1 style="font-size: 1.45rem; margin-bottom: 0.2rem;">Create New Task</h1>
        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.4rem;">Add a task to your workspace</p>

        <div class="alert alert-error" id="jsAlertBanner" style="display: <?= !empty($errors) ? 'block' : 'none' ?>;">
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div><?= e($error) ?></div>
                <?php endforeach; ?>
            <?php else: ?>
                <div id="jsAlertText">Task title is required and cannot be empty.</div>
            <?php endif; ?>
        </div>

        <form method="POST" action="create_task.php" novalidate onsubmit="return validateTaskForm(this);">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label class="form-label" for="title">Task Title <span style="color:#dc2626;">*</span></label>
                <input type="text" name="title" id="title" class="form-control" maxlength="150" placeholder="Enter task title" value="<?= e($title) ?>" required>
                <div class="field-error" id="titleError">Please enter a task title before submitting.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea name="description" id="description" rows="4" class="form-control" placeholder="Describe your task..."><?= e($description) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-control" required>
                    <?php foreach ($statuses as $st): ?>
                        <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 0.5rem;">Create Task</button>
        </form>
    </div>
</div>

<script>
function validateTaskForm(form) {
    const titleInput = form.title;
    const errorEl = document.getElementById('titleError');
    const bannerEl = document.getElementById('jsAlertBanner');
    if (titleInput.value.trim() === '') {
        titleInput.classList.add('is-invalid');
        errorEl.style.display = 'block';
        bannerEl.style.display = 'block';
        titleInput.focus();
        return false;
    }
    titleInput.classList.remove('is-invalid');
    errorEl.style.display = 'none';
    bannerEl.style.display = 'none';
    return true;
}
</script>
</body>
</html>