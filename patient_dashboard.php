<?php
session_start();

$pageTitle = 'Dashboard';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Patient') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';

$conn = getDbConnection();

$patientId = $_SESSION['user_id'];
$today = date('Y-m-d');

// Fetch Today’s Appointments
$sqlToday = "
    SELECT A.appointment_id, A.appointment_date, A.appointment_time, U.full_name AS doctor_name
    FROM Appointments A
    JOIN Users U ON A.doctor_id = U.user_id
    WHERE A.patient_id = ? AND A.appointment_date = ?
    ORDER BY A.appointment_time ASC
";

$stmtToday = sqlsrv_prepare($conn, $sqlToday, [$patientId, $today]);

sqlsrv_execute($stmtToday);

$todaysAppointments = [];

while ($row = sqlsrv_fetch_array($stmtToday, SQLSRV_FETCH_ASSOC)) {
    $todaysAppointments[] = $row;
}

// Fetch Next Upcoming Appointment
$sqlNext = "
    SELECT TOP 1 A.appointment_id, A.appointment_date, A.appointment_time, U.full_name AS doctor_name
    FROM Appointments A
    JOIN Users U ON A.doctor_id = U.user_id
    WHERE A.patient_id = ? AND A.appointment_date > ?
    ORDER BY A.appointment_date ASC, A.appointment_time ASC
";

$stmtNext = sqlsrv_prepare($conn, $sqlNext, [$patientId, $today]);

sqlsrv_execute($stmtNext);

$nextAppointment = sqlsrv_fetch_array($stmtNext, SQLSRV_FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dashboard-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 24px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgb(0 0 0 / 10%);
        }

        .section-title {
            margin-top: 25px;
            margin-bottom: 10px;
            font-weight: bold;
            font-size: 20px;
        }

        .appointment-card {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 12px;
            background: #f8f9fa;
        }

        .dashboard-links a {
            display: block;
            padding: 12px;
            margin-top: 15px;
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

        <!-- Today's Appointments -->
        <div class="section-title">Today's Appointments</div>
        <?php if (empty($todaysAppointments)): ?>
            <p>No appointments for today.</p>
        <?php else: ?>
            <?php foreach ($todaysAppointments as $appt): ?>
                <div class="appointment-card">
                    <strong>Doctor:</strong> <?= htmlspecialchars($appt['doctor_name']) ?><br>
                    <strong>Time:</strong> <?= $appt['appointment_time']->format('H:i') ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Next Upcoming Appointment -->
        <div class="section-title">Next Upcoming Appointment</div>
        <?php if ($nextAppointment): ?>
            <div class="appointment-card">
                <strong>Date:</strong> <?= $nextAppointment['appointment_date']->format('Y-m-d') ?><br>
                <strong>Time:</strong> <?= $nextAppointment['appointment_time']->format('H:i') ?><br>
                <strong>Doctor:</strong> <?= htmlspecialchars($nextAppointment['doctor_name']) ?>
            </div>
        <?php else: ?>
            <p>No upcoming appointments.</p>
        <?php endif; ?>

        <div class="dashboard-links">
            <a href="appointments.php">View My Appointments</a>
            <a href="book_appointment.php">Book a New Appointment</a>
        </div>
    </div>

</body>

</html>