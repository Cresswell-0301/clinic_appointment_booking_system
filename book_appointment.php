<?php
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/csrf.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Patient') {
    header('Location: login.php');
    exit;
}

$conn = getDbConnection();
if (!$conn) {
    die("DB connection failed: " . print_r(sqlsrv_errors(), true));
}

$errors = [];
$success = '';
$csrf = ensureCsrfToken();

// Fetch doctors for selection
$doctorsSql = "
    SELECT D.doctor_id, U.full_name, D.specialization
    FROM Doctors D
    JOIN Users U ON D.user_id = U.user_id
    ORDER BY U.full_name
";
$doctors = fetchAll($conn, $doctorsSql, []);


// If POST -> attempt booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_submit'])) {
    // Validate CSRF
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        $errors[] = 'Invalid session token. Please reload the page and try again.';
    } else {
        // Validate inputs
        $doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
        $availabilityId = isset($_POST['availability_id']) ? (int)$_POST['availability_id'] : 0;

        if ($doctorId <= 0 || $availabilityId <= 0) {
            $errors[] = 'Invalid doctor or slot selection.';
        } else {
            // Start transaction
            if (!sqlsrv_begin_transaction($conn)) {
                $errors[] = 'Unable to start transaction.';
            } else {
                try {
                    // Lock the availability row: ensure slot belongs to doctor and is not booked
                    $checkSql = "
                        SELECT availability_id, is_booked, available_date, available_time
                        FROM DoctorAvailability WITH (ROWLOCK, UPDLOCK)
                        WHERE availability_id = ? AND doctor_id = ?
                    ";
                    $params = [$availabilityId, $doctorId];
                    $stmt = sqlsrv_prepare($conn, $checkSql, $params);
                    if ($stmt === false || !sqlsrv_execute($stmt)) {
                        throw new Exception('Internal error checking availability.');
                    }
                    $avail = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                    if (!$avail) {
                        throw new Exception('Selected slot not found.');
                    }
                    if ((int)$avail['is_booked'] === 1) {
                        throw new Exception('Selected slot is already taken.');
                    }

                    // Insert appointment
                    $insertSql = "
                        INSERT INTO Appointments (patient_id, doctor_id, appointment_date, appointment_time, status)
                        VALUES (?, ?, ?, ?, 'Booked')
                    ";
                    $insertParams = [$_SESSION['user_id'], $doctorId, $avail['available_date'], $avail['available_time']];
                    $insStmt = sqlsrv_prepare($conn, $insertSql, $insertParams);
                    if ($insStmt === false || !sqlsrv_execute($insStmt)) {
                        throw new Exception('Failed to create appointment.');
                    }

                    // Mark availability as booked
                    $updateAvailSql = "
                        UPDATE DoctorAvailability
                        SET is_booked = 1
                        WHERE availability_id = ?
                    ";
                    $updStmt = sqlsrv_prepare($conn, $updateAvailSql, [$availabilityId]);
                    if ($updStmt === false || !sqlsrv_execute($updStmt)) {
                        throw new Exception('Failed to mark slot as booked.');
                    }

                    // Commit
                    if (!sqlsrv_commit($conn)) {
                        throw new Exception('Failed to commit transaction.');
                    }

                    $success = 'Appointment booked successfully.';

                } catch (Exception $e) {
                    // rollback on any error
                    sqlsrv_rollback($conn);
                    $errors[] = $e->getMessage();
                }
            }
        }
    }
}

// Optionally, if doctor selected via GET, fetch their free slots
$selectedDoctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$availableSlots = [];
if ($selectedDoctorId > 0) {
    $slotsSql = "
        SELECT availability_id, available_date, available_time
        FROM DoctorAvailability
        WHERE doctor_id = ? 
        AND is_booked = 0 
        AND (
            available_date > CONVERT(date, GETDATE())
            OR (
                available_date = CONVERT(date, GETDATE())
                AND available_time >= CONVERT(time, GETDATE())
            )
        )
        ORDER BY available_date, available_time
    ";
    $availableSlots = fetchAll($conn, $slotsSql, [$selectedDoctorId]);
}

include __DIR__ . '/components/header.php';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Book Appointment</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .book-btn-large {
            display: block;
            width: 100%;
            padding: 15px 20px;
            font-size: 1.2rem;
            font-weight: bold;
            color: white;
            background-color: #1E88E5; /* Blue theme */
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .book-btn-large:hover {
            background-color: #1565C0; /* Darker blue on hover */
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Book an Appointment</h2>

    <?php foreach ($errors as $err): ?>
        <div class="error-message"><?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>
    <?php if ($success): ?>
        <div class="success-message"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="get" action="book_appointment.php">
        <label for="doctor_id_select">Select Doctor</label>
        <select id="doctor_id_select" name="doctor_id" onchange="this.form.submit()" style="width:100%; padding:10px; margin-bottom:20px;">
            <option value="">-- choose doctor --</option>
            <?php foreach ($doctors as $d): ?>
                <option value="<?= $d['doctor_id'] ?>" <?= ($selectedDoctorId == $d['doctor_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['full_name']) ?> <?= $d['specialization'] ? ' — ' . htmlspecialchars($d['specialization']) : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($selectedDoctorId > 0): ?>
        <h3>Available slots</h3>
        <?php if (empty($availableSlots)): ?>
            <p>No available slots (for today and future). Contact admin.</p>
        <?php else: ?>
            <form method="post" action="book_appointment.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="doctor_id" value="<?= htmlspecialchars($selectedDoctorId) ?>">
                
                <table style="width:100%; border-collapse:collapse; margin-bottom:15px;">
                    <thead style="background:#f0f0f0;">
                        <tr>
                            <th style="padding:10px; text-align:left;">Select</th>
                            <th style="padding:10px; text-align:left;">Date</th>
                            <th style="padding:10px; text-align:left;">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($availableSlots as $s): ?>
                        <tr style="border-bottom:1px solid #ddd;">
                            <td style="padding:10px;">
                                <input type="radio" name="availability_id" value="<?= $s['availability_id'] ?>" required style="transform: scale(1.5);">
                            </td>
                            <td style="padding:10px;"><?= $s['available_date']->format('Y-m-d') ?></td>
                            <td style="padding:10px;"><?= $s['available_time']->format('H:i') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button name="book_submit" type="submit" class="book-btn-large">
                    Confirm Booking
                </button>

            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>