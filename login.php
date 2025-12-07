<?php
session_start();

require_once __DIR__ . '/includes/db.php';

$error = '';

if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if (isset($_POST['login_submit']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
}

include __DIR__ . '/includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Clinic Appointment System - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>

    </style>
</head>

<body>

    <div class="login-container">
        <h2>Login</h2>

        <?php if ($error !== ''): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required>
            </div>

            <button type="submit" class="btn-primary" name="login_submit">Login</button>

            <div class="link-row">
                <span>Don't have an account? <a href="register.php">Register here</a></span>
            </div>
        </form>
    </div>

</body>

</html>