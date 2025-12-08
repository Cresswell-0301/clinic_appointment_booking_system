<?php
session_start();

$pageTitle = 'Dashboard';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Patient') {
    header('Location: login.php');
    exit;
}

include __DIR__ . '/includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dashboard-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 24px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgb(0 0 0 / 10%);
        }

        .dashboard-container h2 {
            margin-bottom: 20px;
        }

        .dashboard-links a {
            display: block;
            padding: 12px;
            margin-bottom: 12px;
            background: #1e88e5;
            color: #fff;
            text-align: center;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .dashboard-links a:hover {
            background: #1565c0;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>

        <div class="dashboard-links">
            <a href="appointments.php">View My Appointments</a>
            <a href="book_appointment.php">Book a New Appointment</a>
            <a href="edit_profile.php">Edit My Profile</a>
        </div>
    </div>

</body>

</html>