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

$doctorId = $_SESSION['doctor_id'];

// 1. Today appointments
$sqlToday = "
    SELECT COUNT(*) AS totalToday
    FROM Appointments
    WHERE doctor_id = ?
    AND appointment_date = CAST(GETDATE() AS DATE)
    AND (
        appointment_time >= CAST(GETDATE() AS TIME)
        OR status = 'Booked'
    );
";
$todayCount = fetchOne($conn, $sqlToday, [$doctorId])['totalToday'] ?? 0;

// 2. Pending appointments (Total Count)
$sqlPending = "
    SELECT COUNT(*) AS totalToday
    FROM Appointments
    WHERE doctor_id = ?
    AND appointment_date = CAST(GETDATE() AS DATE)
    AND (
        appointment_time >= CAST(GETDATE() AS TIME)
        OR status = 'Booked'
    );
";
$pending = fetchOne($conn, $sqlPending, [$doctorId])['pending'] ?? 0;

// 3. Completed appointments
$sqlCompleted = "
    SELECT COUNT(*) AS completed
    FROM Appointments
    WHERE doctor_id = ?
    AND appointment_date = CAST(GETDATE() AS DATE)
    AND status = 'Completed';
";
$completed = fetchOne($conn, $sqlCompleted, [$doctorId])['completed'] ?? 0;

// 4. Next Upcoming Appointment (The one we want to update)
$sqlNext = "
    SELECT TOP 1 appointment_id, appointment_date, appointment_time, patient_id
    FROM Appointments
    WHERE doctor_id = ?
    AND (
        appointment_date > CAST(GETDATE() AS DATE)
        OR (
            appointment_date = CAST(GETDATE() AS DATE)
            AND appointment_time >= CAST(GETDATE() AS TIME)
        )
    )
    AND status = 'Booked'
    ORDER BY appointment_date, appointment_time;
";
$nextAppt = fetchOne($conn, $sqlNext, [$doctorId]);

// Date conversion for display
if ($nextAppt) {
    if (!($nextAppt['appointment_date'] instanceof DateTime)) {
        $nextAppt['appointment_date'] = new DateTime($nextAppt['appointment_date']);
    }
    if (!($nextAppt['appointment_time'] instanceof DateTime)) {
        $nextAppt['appointment_time'] = new DateTime($nextAppt['appointment_time']);
    }
}

// 5. Available schedule slots
$sqlSchedule = "
    SELECT COUNT(*) AS totalAvailable
    FROM DoctorAvailability
    WHERE doctor_id = ?
    AND is_booked = 0
    AND (
        available_date > CAST(GETDATE() AS DATE)
        OR (
            available_date = CAST(GETDATE() AS DATE)
            AND available_time >= CAST(GETDATE() AS TIME)
        )
    );
";
$scheduleCount = fetchOne($conn, $sqlSchedule, [$doctorId])['totalAvailable'] ?? 0;

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
                <div class="label">Today Pending Appointments</div>
                <div class="value"><?php echo $pending; ?></div>
            </div>

            <div class="summary-card">
                <div class="icon">✅</div>
                <div class="label">Today Completed Appointments</div>
                <div class="value"><?php echo $completed; ?></div>
            </div>

            <div class="summary-card" style="border: 2px solid #1E88E5;">
                <div class="icon">🔔</div>
                <div class="label">Next Appointment</div>
                <div class="value" style="font-size: 1.1em;">
                    <?php
                    if ($nextAppt) {
                        // Display Date & Time
                        echo $nextAppt['appointment_date']->format('Y-m-d')
                            . " <small>(" . $nextAppt['appointment_time']->format('H:i') . ")</small><br>";

                        // The Update Button
                        echo '<a href="doctor_update_appointment.php?id=' . $nextAppt['appointment_id'] . '" 
                                 style="
                                    display: inline-block;
                                    margin-top: 5px;
                                    padding: 5px 10px;
                                    background-color: #1E88E5;
                                    color: white;
                                    font-size: 0.8em;
                                    border-radius: 6px;
                                    text-decoration: none;
                                 ">
                                 Update Status
                              </a>';
                    } else {
                        echo "<span style='color:#999;'>No upcoming bookings</span>";
                    }
                    ?>
                </div>
            </div>

            <div class="summary-card">
                <div class="icon">📋</div>
                <div class="label">Available Slots</div>
                <div class="value"><?php echo $scheduleCount; ?></div>
            </div>
        </div>
    </div>
</div>