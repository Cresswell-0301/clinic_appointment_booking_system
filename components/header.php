<?php
$userRole = $_SESSION['role'] ?? null;
$baseTitle = 'Clinic Appointment System';

$fullTitle = isset($pageTitle) && $pageTitle !== ''
    ? $pageTitle . ' | ' . $baseTitle
    : $baseTitle;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($fullTitle); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <header class="navbar">
        <h1>Clinic Appointment System</h1>

        <nav>
            <!-- Home / Dashboard -->
            <?php if (strpos($_SERVER['PHP_SELF'], 'index.php') === false && strpos($_SERVER['PHP_SELF'], 'patient_dashboard.php') === false && strpos($_SERVER['PHP_SELF'], 'doctor_dashboard.php') === false && strpos($_SERVER['PHP_SELF'], 'admin_dashboard.php') === false): ?>
                <a href=<?php
                        echo isset($_SESSION['user_id']) ?
                            ($_SESSION['role'] === 'Patient' ? 'patient_dashboard.php' : ($_SESSION['role'] === 'Doctor' ? 'doctor_dashboard.php' :
                                'admin_dashboard.php')) :
                            'index.php';
                        ?>>
                    <?php echo isset($_SESSION['user_id']) ? 'Dashboard' : 'Home'; ?>
                </a>
            <?php endif; ?>

            <!-- Login & Register -->
            <?php if (!$userRole): ?>
                <?php if (strpos($_SERVER['PHP_SELF'], 'login.php') === false): ?>
                    <a href="login.php">Login</a>
                <?php endif; ?>
                <?php if (strpos($_SERVER['PHP_SELF'], 'register.php') === false): ?>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Patient -->
            <?php if ($userRole === 'Patient'): ?>
                <a href="appointments.php">My Appointments</a>
            <?php endif; ?>

            <!-- Doctor -->
            <?php if ($userRole === 'Doctor'): ?>
                <a href="doctor_schedule.php">Schedule</a>
            <?php endif; ?>

            <!-- Admin -->
            <?php if ($userRole === 'Admin'): ?>
                <a href="admin_users.php">Users</a>
                <a href="admin_doctor_availability.php">Doctor Availability</a>
            <?php endif; ?>

            <!-- Logout -->
            <?php if ($userRole): ?>
                <a href="logout.php">Logout</a>
            <?php endif; ?>
        </nav>
    </header>