<?php
// file: public/login.php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['role']    = $user['role'];

                set_flash('success', 'Logged in successfully.');
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = 'Invalid email address or password.';
            }
        } catch (PDOException $e) {
            error_log('Login Error: ' . $e->getMessage());
            $errors[] = 'Unable to process login at this time.';
        }
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | TaskFlow</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="tf-logo">TF</div>
        <h1 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Welcome Back</h1>
        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.5rem;">Login to manage your tasks</p>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <div><?= e($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off" novalidate onsubmit="return validateLogin(this);">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" value="" placeholder="you@example.com" autocomplete="off" required>
                <div class="field-error" id="emailError">Please enter your email address.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" autocomplete="new-password" required>
                <div class="field-error" id="passError">Please enter your password.</div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 0.5rem;">Login</button>
        </form>

        <p style="text-align: center; font-size: 0.88rem; color: #64748b; margin-top: 1.25rem;">
            Don't have an account? <a href="register.php" style="color: #2563eb; text-decoration: none; font-weight: 600;">Register</a>
        </p>
    </div>
</div>
<script>
function validateLogin(form) {
    let valid = true;
    document.getElementById('emailError').style.display = 'none';
    document.getElementById('passError').style.display = 'none';

    if (form.email.value.trim() === '') {
        document.getElementById('emailError').style.display = 'block';
        valid = false;
    }
    if (form.password.value === '') {
        document.getElementById('passError').style.display = 'block';
        valid = false;
    }
    return valid;
}
</script>
</body>
</html>