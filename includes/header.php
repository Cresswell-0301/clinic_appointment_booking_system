<?php
$userRole = $_SESSION['role'] ?? null;
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

            <?php if (!$userRole): ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>

            <?php if ($userRole === 'Patient'): ?>
                <a href="patient_dashboard.php">Dashboard</a>
                <a href="appointments.php">My Appointments</a>
            <?php endif; ?>

            <?php if ($userRole === 'Doctor'): ?>
                <a href="doctor_dashboard.php">Dashboard</a>
                <a href="doctor_schedule.php">Schedule</a>
            <?php endif; ?>

            <?php if ($userRole === 'Admin'): ?>
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="admin_users.php">Users</a>
                <a href="admin_doctor_availability.php">Doctor Availability</a>
            <?php endif; ?>

            <?php if ($userRole): ?>
                <a href="logout.php">Logout</a>
            <?php endif; ?>
        </nav>
    </header>