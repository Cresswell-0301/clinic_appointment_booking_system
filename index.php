<?php
require_once __DIR__ . '/includes/db.php';
$conn = getDbConnection();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Clinic Appointment System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <header class="navbar">
        <h1>Clinic Appointment System</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
            <a href="appointments.php">Appointments</a>
        </nav>
    </header>

    <section class="hero">
        <h2>Welcome to the Clinic Appointment Booking System</h2>
        <p>Manage appointments efficiently with a secure and user-friendly platform.</p>

        <a href="login.php" class="btn-primary">Get Started</a>
    </section>

    <section class="status-box">
        <?php if ($conn): ?>
            <p class="status success">✓ Database Connected Successfully</p>
        <?php else: ?>
            <p class="status error">✗ Failed to Connect to Database</p>
        <?php endif; ?>
    </section>

</body>

</html>