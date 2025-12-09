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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body class="<?php echo ($userRole === 'Admin') ? 'admin-layout' : ''; ?>">
    <header class="navbar">
        <h1>CAS</h1>

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
                <a href="doctor_view_appointments.php">Appointments</a>

                <a href="doctor_schedule.php">Schedule</a>
            <?php endif; ?>

            <!-- Admin -->
            <?php if ($userRole === 'Admin'): ?>
                <div class="admin-menu">
                    <a href="admin_dashboard.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : ''; ?>">
                        Dashboard
                    </a>
                    <a href="admin_users.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) === 'admin_users.php' ? 'active' : ''; ?>">
                        Users
                    </a>
                    <a href="admin_doctor_availability.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) === 'admin_doctor_availability.php' ? 'active' : ''; ?>">
                        Doctor Availability
                    </a>
                    <a href="admin_appointments.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) === 'admin_appointments.php' ? 'active' : ''; ?>">
                        All Appointments
                    </a>
                </div>

                <div class="admin-logout">
                    <a href="logout.php">Logout</a>
                </div>
            <?php endif; ?>

            <!-- Logout -->
            <?php if ($userRole && $userRole !== 'Admin'): ?>
                <a href="logout.php">Logout</a>
            <?php endif; ?>
        </nav>
    </header>