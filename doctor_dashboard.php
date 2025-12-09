<?php
session_start();
$pageTitle = 'Doctor Dashboard';

// RBAC: Only doctors can see this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Doctor') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';
$conn = getDbConnection();

$doctorId = $_SESSION['user_id'];

// Today appointments
$sqlToday = "
    SELECT COUNT(*) AS totalToday
    FROM Appointments
    WHERE doctor_id = ?
    AND CAST(appointment_date AS DATE) = CAST(GETDATE() AS DATE);
";
$todayCount = fetchOne($conn, $sqlToday, [$doctorId])['totalToday'] ?? 0;

// Pending appointments
$sqlPending = "
    SELECT COUNT(*) AS pending
    FROM Appointments
    WHERE doctor_id = ?
    AND status = 'Booked';
";
$pending = fetchOne($conn, $sqlPending, [$doctorId])['pending'] ?? 0;

// Completed appointments
$sqlCompleted = "
    SELECT COUNT(*) AS completed
    FROM Appointments
    WHERE doctor_id = ?
    AND status = 'Completed';
";
$completed = fetchOne($conn, $sqlCompleted, [$doctorId])['completed'] ?? 0;

// Upcoming appointment
$sqlNext = "
    SELECT TOP 1 appointment_date, appointment_time, patient_id
    FROM Appointments
    WHERE doctor_id = ?
    AND appointment_date >= CAST(GETDATE() AS DATE)
    AND status = 'Booked'
    ORDER BY appointment_date, appointment_time;
";

$nextAppt = fetchOne($conn, $sqlNext, [$doctorId]);

include __DIR__ . '/components/header.php';
?>

<div class="content-wrapper">

    <div class="admin-dashboard">

        <h2 class="welcome-text">Welcome, Dr. <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>

        <div class="summary-grid">

            <div class="summary-card">
                <div class="icon">📅</div>
                <div class="label">Today Appointments</div>
                <div class="value"><?php echo $todayCount; ?></div>
            </div>

            <div class="summary-card">
                <div class="icon">🕒</div>
                <div class="label">Pending Appointments</div>
                <div class="value"><?php echo $pending; ?></div>
            </div>

            <div class="summary-card">
                <div class="icon">✅</div>
                <div class="label">Completed Appointments</div>
                <div class="value"><?php echo $completed; ?></div>
            </div>

            <div class="summary-card">
                <div class="icon">🔔</div>
                <div class="label">Next Appointment</div>
                <div class="value">
                    <?php
                    if ($nextAppt) {
                        echo htmlspecialchars($nextAppt['appointment_date']->format('Y-m-d')) .
                            " @ " . htmlspecialchars($nextAppt['appointment_time']);
                    } else {
                        echo "-";
                    }
                    ?>
                </div>
            </div>

        </div>

        <!-- Links -->
        <div class="admin-actions">
            <a class="admin-btn" href="doctor_schedule.php">View Schedule</a>
            <a class="admin-btn" href="doctor_view_appointments.php">All Appointments</a>
        </div>

    </div>
</div>