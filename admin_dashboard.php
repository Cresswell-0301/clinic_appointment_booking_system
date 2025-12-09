<?php
session_start();
$pageTitle = 'Dashboard';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';
$conn = getDbConnection();

// Users
$sqlTotalUsers = "SELECT COUNT(*) AS total FROM Users";
$stmt1 = sqlsrv_query($conn, $sqlTotalUsers);
$totalUsers = sqlsrv_fetch_array($stmt1, SQLSRV_FETCH_ASSOC)['total'];

// Patients
$sqlPatients = "SELECT COUNT(*) AS total FROM Users WHERE role = 'Patient'";
$stmt2 = sqlsrv_query($conn, $sqlPatients);
$totalPatients = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)['total'];

// Doctors
$sqlDoctors = "SELECT COUNT(*) AS total FROM Users WHERE role = 'Doctor'";
$stmt3 = sqlsrv_query($conn, $sqlDoctors);
$totalDoctors = sqlsrv_fetch_array($stmt3, SQLSRV_FETCH_ASSOC)['total'];

// Appointments
$sqlAppointments = "SELECT COUNT(*) AS total FROM Appointments";
$stmt4 = sqlsrv_query($conn, $sqlAppointments);
$totalAppointments = sqlsrv_fetch_array($stmt4, SQLSRV_FETCH_ASSOC)['total'];

include __DIR__ . '/components/header.php';
?>

<div class="content-wrapper">
    <div class="admin-dashboard">

        <h2 class="welcome-text">Welcome, Admin <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>

        <!-- Cards -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="icon"><i class="fa-solid fa-person"></i></div>
                <div class="label">Total Users</div>
                <div class="value"><?php echo $totalUsers; ?></div>
            </div>

            <div class="summary-card">
                <div class="icon">🧑‍💼</div>
                <div class="label">Total Patients</div>
                <div class="value"><?php echo $totalPatients; ?></div>
            </div>

            <div class="summary-card">
                <div class="icon">🩺</div>
                <div class="label">Total Doctors</div>
                <div class="value"><?php echo $totalDoctors; ?></div>
            </div>

            <div class="summary-card">
                <div class="icon">📅</div>
                <div class="label">Total Appointments</div>
                <div class="value"><?php echo $totalAppointments; ?></div>
            </div>
        </div>
    </div>
</div>