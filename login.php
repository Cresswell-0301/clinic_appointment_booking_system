<?php
session_start();

$pageTitle = 'Login';

require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';

if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if (isset($_SESSION['registration_success'])) {
    $success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

if (isset($_POST['login_submit']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $_SESSION['login_error'] = 'Please enter both username and password.';
        header('Location: login.php');
        exit;
    } else {
        $conn = getDbConnection();

        $passwordHash = hash('sha256', $password);

        $sql = "SELECT user_id, full_name, email, phone_number, role
                FROM Users 
                WHERE username = ? AND password_hash = ?";

        $params = [$username, $passwordHash];

        $stmt = sqlsrv_prepare($conn, $sql, $params);

        if ($stmt === false) {
            $_SESSION['login_error'] = 'An internal error occurred. Please try again later.';
            header("Location: login.php");
            exit;
        } else {
            if (!sqlsrv_execute($stmt)) {
                $_SESSION['login_error'] = 'An internal error occurred. Please try again later.';
                header("Location: login.php");
                exit;
            } else {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

                if ($row) {
                    session_regenerate_id(true);

                    $_SESSION['is_active']  = $row['is_active'];

                    if (!$_SESSION['is_active']) {
                        $_SESSION['login_error'] = 'Your account is inactive. Please contact the administrator.';
                        header('Location: login.php');
                        exit;
                    }

                    $_SESSION['user_id']    = $row['user_id'];
                    $_SESSION['full_name']  = $row['full_name'];
                    $_SESSION['email']      = $row['email'];
                    $_SESSION['phone_number'] = $row['phone_number'];
                    $_SESSION['role']       = $row['role'];

                    switch ($row['role']) {
                        case 'Patient':
                            header('Location: patient_dashboard.php');
                            exit;

                        case 'Doctor':
                            $sqlDoctor = "
                                SELECT doctor_id FROM Doctors
                                WHERE user_id = ?
                            ";
                            $doctorRow = fetchAll($conn, $sqlDoctor, [$_SESSION['user_id']])[0] ?? [];

                            $_SESSION['doctor_id'] = $doctorRow['doctor_id'];

                            header('Location: doctor_dashboard.php');
                            exit;

                        case 'Admin':
                        case 'SuperAdmin':
                            header('Location: admin_dashboard.php');
                            exit;

                        default:
                            $error = 'Your account role is not recognized. Please contact the administrator.';
                    }
                } else {
                    $_SESSION['login_error'] = 'Invalid username or password.';
                    header('Location: login.php');
                    exit;
                }
            }
        }
    }
}

include __DIR__ . '/components/header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 60px auto;
            padding: 32px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgb(0 0 0 / 10%);
        }

        .login-container h2 {
            margin-top: 0;
            text-align: center;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .btn-primary {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border: none;
            border-radius: 4px;
            background: #1e88e5;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #1565c0;
        }

        .error-message {
            background: #ffcdd2;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 16px;
            text-align: center;
        }

        .success-message {
            background: #c8e6c9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 16px;
            text-align: center;
        }

        .link-row {
            margin-top: 12px;
            text-align: center;
        }

        .link-row a {
            color: #1e88e5;
            text-decoration: none;
        }

        .link-row a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <h2>Login</h2>

        <?php if ($success !== ''): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

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