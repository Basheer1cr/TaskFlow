<?php
// file: public/register.php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$name   = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name     = trim((string)($_POST['name'] ?? ''));
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($name === '' || strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = 'Full name is required (2–100 characters).';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if (empty($errors)) {
        try {
            $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $check->execute([':email' => $email]);

            if ($check->fetch()) {
                $errors[] = 'An account with this email address already exists. Please use a different email.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
                );
                $stmt->execute([
                    ':name'          => $name,
                    ':email'         => $email,
                    ':password_hash' => $hash,
                    ':role'          => 'user',
                ]);

                set_flash('success', 'Registration complete. You can now log in.');
                header('Location: login.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('Register Error: ' . $e->getMessage());
            $errors[] = 'Registration failed. Please try a different email address.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | TaskFlow</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="tf-logo">TF</div>
        <h1 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Create Account</h1>
        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.5rem;">Sign up to track your tasks</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <div><?= e($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" autocomplete="off" novalidate onsubmit="return validateRegister(this);">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <input type="text" name="name" id="name" class="form-control" value="<?= e($name) ?>" placeholder="Enter your full name" required>
                <div class="field-error" id="nameError">Please enter your full name.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" value="<?= e($email) ?>" placeholder="Enter your email" required>
                <div class="field-error" id="emailError">Please enter a valid email address.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password (min 6 characters)</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                <div class="field-error" id="passError">Password must be at least 6 characters.</div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 0.5rem;">Register</button>
        </form>

        <p style="text-align: center; font-size: 0.88rem; color: #64748b; margin-top: 1.25rem;">
            Already have an account? <a href="login.php" style="color: #2563eb; text-decoration: none; font-weight: 600;">Login</a>
        </p>
    </div>
</div>
<script>
function validateRegister(form) {
    let valid = true;
    document.getElementById('nameError').style.display = 'none';
    document.getElementById('emailError').style.display = 'none';
    document.getElementById('passError').style.display = 'none';

    if (form.name.value.trim().length < 2) {
        document.getElementById('nameError').style.display = 'block';
        valid = false;
    }
    if (form.email.value.trim() === '') {
        document.getElementById('emailError').style.display = 'block';
        valid = false;
    }
    if (form.password.value.length < 6) {
        document.getElementById('passError').style.display = 'block';
        valid = false;
    }
    return valid;
}
</script>
</body>
</html>