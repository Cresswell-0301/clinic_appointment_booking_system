<?php
session_start();
$pageTitle = 'Doctor Schedule';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Doctor') {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/audit.php';

$conn = getDbConnection();

$doctorId = $_SESSION['doctor_id'];

$message = "";
$error = "";

// Sort
$sortColumn = $_GET['sort'] ?? 'available_date';
$sortOrder  = $_GET['order'] ?? 'asc';

$allowedColumns = ['available_date', 'available_time', 'is_booked'];
if (!in_array($sortColumn, $allowedColumns, true)) {
    $sortColumn = 'available_date';
}

$sortOrder = strtolower($sortOrder) === 'desc' ? 'DESC' : 'ASC';

$orderExtra = '';

if ($sortColumn === 'available_date') {
    $orderExtra = ', available_time ASC';
} elseif ($sortColumn === 'is_booked') {
    $orderExtra = ', available_date ASC, available_time ASC';
}

function generateTimeSlots($start, $end, $durationMinutes)
{
    $slots = [];
    $current = strtotime($start);
    $endTime = strtotime($end);

    while ($current < $endTime) {
        $slots[] = date("H:i", $current);
        $current = strtotime("+$durationMinutes minutes", $current);
    }

    return $slots;
}

$keepModalOpen = false;

$old = [
    'start_date' => $_POST['start_date'] ?? '',
    'end_date' => $_POST['end_date'] ?? '',
    'days' => $_POST['days'] ?? [],
    'start_time' => $_POST['start_time'] ?? '',
    'end_time' => $_POST['end_time'] ?? '',
    'duration' => $_POST['duration'] ?? ''
];

if (isset($_GET['reset'])) {
    $old = [
        'start_date' => '',
        'end_date' => '',
        'days' => [],
        'start_time' => '',
        'end_time' => '',
        'duration' => ''
    ];
    $error = "";
}

if (isset($_POST['add_availability'])) {

    $startDate = $_POST['start_date'];
    $endDate   = $_POST['end_date'];
    $days      = $_POST['days'] ?? [];
    $startTime = $_POST['start_time'];
    $endTime   = $_POST['end_time'];
    $duration  = (int)$_POST['duration'];

    $keepModalOpen = true;

    $actualInterval = (DateTime::createFromFormat('H:i', $startTime))->diff(DateTime::createFromFormat('H:i', $endTime));
    $actual_duration_in_minutes =
        ($actualInterval->h * 60) +
        ($actualInterval->i);

    if (empty($days)) {
        $error = "Please select at least one day.";
    } elseif ($startDate > $endDate) {
        $error = "Start date cannot be later than end date.";
    } elseif ($startTime >= $endTime) {
        $error = "Start time must be earlier than end time.";
    } elseif ($startDate == $endDate && $startTime >= $endTime) {
        $error = "On the same day, start time must be earlier than end time.";
    } elseif ($actual_duration_in_minutes < $duration) {
        $error = "The time range is shorter than the slot duration.";
    } else {
        $slots = generateTimeSlots($startTime, $endTime, $duration);

        $date = $startDate;
        $createdCount = 0;

        while ($date <= $endDate) {

            $weekday = date('w', strtotime($date)); // 0–6

            if (in_array($weekday, $days)) {

                foreach ($slots as $time) {

                    // Check duplicates
                    $exists = fetchOne(
                        $conn,
                        "SELECT 1 FROM DoctorAvailability 
                        WHERE doctor_id = ? AND available_date = ? AND available_time = ?",
                        [$doctorId, $date, $time]
                    );

                    if ($exists) {
                        $error = "Slot on $date at $time already exists.";

                        auditLog(
                            $conn,
                            $_SESSION['user_id'],
                            $_SESSION['role'],
                            'ADD_AVAILABILITY_FAILED',
                            'DoctorAvailability',
                            null,
                            "Attempted to add duplicate slot on $date at $time."
                        );
                        continue;
                    }

                    // Insert new slot
                    sqlsrv_query(
                        $conn,
                        "INSERT INTO DoctorAvailability (doctor_id, available_date, available_time) 
                        VALUES (?, ?, ?)",
                        [$doctorId, $date, $time]
                    );

                    auditLog(
                        $conn,
                        $_SESSION['user_id'],
                        $_SESSION['role'],
                        'ADD_AVAILABILITY_SUCCESS',
                        'DoctorAvailability',
                        null,
                        "Added availability slot on $date at $time."
                    );

                    $createdCount++;
                }
            }

            $date = date('Y-m-d', strtotime($date . ' +1 day'));
        }

        if (!empty($error)) {
            $keepModalOpen = true;

            auditLog(
                $conn,
                $_SESSION['user_id'],
                $_SESSION['role'],
                'ADD_AVAILABILITY_PARTIAL_SUCCESS',
                'DoctorAvailability',
                null,
                "Created $createdCount slots with some errors: $error"
            );
        } else {
            $old = [
                'start_date' => '',
                'end_date' => '',
                'days' => [],
                'start_time' => '',
                'end_time' => '',
                'duration' => ''
            ];
            $message = "$createdCount availability slots created.";
            $keepModalOpen = false;
        }
    }
}

if (isset($_GET['delete'])) {

    $availabilityId = (int)$_GET['delete'];

    // Check if booked
    $checkBooked = "
        SELECT is_booked FROM DoctorAvailability
        WHERE availability_id = ? AND doctor_id = ?
    ";
    $slot = fetchAll($conn, $checkBooked, [$availabilityId, $doctorId]);

    if (!$slot) {
        $error = "Invalid availability ID.";

        auditLog(
            $conn,
            $_SESSION['user_id'],
            $_SESSION['role'],
            'DELETE_AVAILABILITY_FAILED',
            'DoctorAvailability',
            $availabilityId,
            'Attempted to delete non-existent availability ID: ' . $availabilityId
        );
    } elseif ($slot[0]['is_booked'] == 1) {
        $error = "You cannot delete a booked slot.";

        auditLog(
            $conn,
            $_SESSION['user_id'],
            $_SESSION['role'],
            'DELETE_AVAILABILITY_FAILED',
            'DoctorAvailability',
            $availabilityId,
            'Attempted to delete booked availability ID: ' . $availabilityId
        );
    } else {
        $deleteSql = "
            DELETE FROM DoctorAvailability
            WHERE availability_id = ? AND doctor_id = ?
        ";
        sqlsrv_query($conn, $deleteSql, [$availabilityId, $doctorId]);

        auditLog(
            $conn,
            $_SESSION['user_id'],
            $_SESSION['role'],
            'DELETE_AVAILABILITY_SUCCESS',
            'DoctorAvailability',
            $availabilityId,
            'Deleted availability ID: ' . $availabilityId
        );

        $message = "Availability deleted successfully.";

        header("Refresh:1; url=doctor_schedule.php");
    }
}

$sqlAvailability = "
    SELECT availability_id, available_date, available_time, is_booked
    FROM DoctorAvailability
    WHERE doctor_id = ?
    AND (
        available_date > CAST(GETDATE() AS DATE)
        OR (
            available_date = CAST(GETDATE() AS DATE)
            AND available_time >= CAST(GETDATE() AS TIME)
        )
    )
    ORDER BY $sortColumn $sortOrder $orderExtra
";

$availabilityList = fetchAll($conn, $sqlAvailability, [$doctorId]);

include __DIR__ . '/components/header.php';
?>

<div class="content-wrapper">

    <div class="admin-dashboard">
        <h2 class="welcome-text">My Availability Schedule</h2>

        <?php if ($message): ?>
            <div class="success-message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <button class="btn-primary" style="margin-bottom:20px;" onclick="openModal('scheduleModal')">
            Add Schedule
        </button>

        <!-- Add availability -->
        <div id="scheduleModal" class="modal">
            <div class="modal-content">

                <h3>Add Availability</h3>

                <form method="post" action="">
                    <div class="schedule-form-grid">

                        <!-- Row 1: Dates -->
                        <div>
                            <label>Start Date</label>
                            <input type="date" name="start_date"
                                value="<?php echo htmlspecialchars($old['start_date']); ?>" required>
                        </div>

                        <div>
                            <label>End Date</label>
                            <input type="date" name="end_date"
                                value="<?php echo htmlspecialchars($old['end_date']); ?>" required>
                        </div>

                        <!-- Row 2: Days -->
                        <div class="full-width">
                            <label>Select Days</label>
                            <div class="day-checkbox-group">
                                <label>
                                    <input type="checkbox" name="days[]" value="1"
                                        <?php echo in_array("1", $old['days']) ? "checked" : ""; ?>>
                                    Monday
                                </label>

                                <label>
                                    <input type="checkbox" name="days[]" value="2"
                                        <?php echo in_array("2", $old['days']) ? "checked" : ""; ?>>
                                    Tuesday
                                </label>

                                <label>
                                    <input type="checkbox" name="days[]" value="3"
                                        <?php echo in_array("3", $old['days']) ? "checked" : ""; ?>>
                                    Wednesday
                                </label>

                                <label>
                                    <input type="checkbox" name="days[]" value="4"
                                        <?php echo in_array("4", $old['days']) ? "checked" : ""; ?>>
                                    Thursday
                                </label>

                                <label>
                                    <input type="checkbox" name="days[]" value="5"
                                        <?php echo in_array("5", $old['days']) ? "checked" : ""; ?>>
                                    Friday
                                </label>

                                <label>
                                    <input type="checkbox" name="days[]" value="6"
                                        <?php echo in_array("6", $old['days']) ? "checked" : ""; ?>>
                                    Saturday
                                </label>

                                <label>
                                    <input type="checkbox" name="days[]" value="0"
                                        <?php echo in_array("0", $old['days']) ? "checked" : ""; ?>>
                                    Sunday
                                </label>
                            </div>
                        </div>

                        <!-- Row 3: Time Range -->
                        <div>
                            <label>Start Time</label>
                            <input type="time" name="start_time"
                                value="<?php echo htmlspecialchars($old['start_time']); ?>" required>
                        </div>

                        <div>
                            <label>End Time</label>
                            <input type="time" name="end_time"
                                value="<?php echo htmlspecialchars($old['end_time']); ?>" required>
                        </div>

                        <!-- Row 4: Duration -->
                        <div class="full-width">
                            <label>Slot Duration (minutes)</label>
                            <select name="duration" required style="width: 100%;">
                                <option value="30" <?php echo $old['duration'] == "30" ? "selected" : ""; ?>>30 minutes</option>
                                <option value="60" <?php echo $old['duration'] == "60" ? "selected" : ""; ?>>60 minutes</option>
                            </select>
                        </div>
                    </div>

                    <div id="modalError" class="error-message" style="display:none; color:red; margin-top:10px;"></div>

                    <!-- Buttons -->
                    <div class="btn-row">
                        <button class="btn-primary" id="generateBtn" type="submit" name="add_availability">
                            Generate Availability
                        </button>

                        <button type="button" class="btn-secondary" onclick="closeModal('scheduleModal')">
                            Close
                        </button>
                    </div>
                </form>

            </div>
        </div>

        <!-- Availability List -->
        <h3>Upcoming Availability</h3>

        <?php
        $dateArrow = $timeArrow = $statusArrow = '<i class="fa-solid fa-angle-down"></i>';

        if ($sortColumn === 'available_date') {
            $dateArrow = ($sortOrder === 'ASC')
                ? '<i class="fa-solid fa-angle-up"></i>'
                : '<i class="fa-solid fa-angle-down"></i>';
        }

        if ($sortColumn === 'available_time') {
            $timeArrow = ($sortOrder === 'ASC')
                ? '<i class="fa-solid fa-angle-up"></i>'
                : '<i class="fa-solid fa-angle-down"></i>';
        }

        if ($sortColumn === 'is_booked') {
            $statusArrow = ($sortOrder === 'ASC')
                ? '<i class="fa-solid fa-angle-up"></i>'
                : '<i class="fa-solid fa-angle-down"></i>';
        }
        ?>

        <table style="width:100%;background:white;border-radius:8px;padding:15px; text-align:center;">
            <tr>
                <th>
                    <a href="?sort=available_date&order=<?php echo ($sortColumn == 'available_date' && $sortOrder == 'ASC') ? 'desc' : 'asc'; ?>"
                        style=" text-decoration:none; color: black;">
                        Date <?php echo $dateArrow; ?>
                    </a>
                </th>

                <th>
                    <a href="?sort=available_time&order=<?php echo ($sortColumn == 'available_time' && $sortOrder == 'ASC') ? 'desc' : 'asc'; ?>"
                        style=" text-decoration:none; color: black;">
                        Time <?php echo $timeArrow; ?>
                    </a>
                </th>

                <th>
                    <a href="?sort=is_booked&order=<?php echo ($sortColumn == 'is_booked' && $sortOrder == 'ASC') ? 'desc' : 'asc'; ?>"
                        style=" text-decoration:none; color: black;">
                        Status <?php echo $statusArrow; ?>
                    </a>
                </th>

                <th>Action</th>
            </tr>

            <?php if (empty($availabilityList)): ?>
                <tr>
                    <td colspan="4" style="text-align:center;">No availability slots added yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($availabilityList as $slot): ?>
                    <tr>
                        <td><?php echo $slot['available_date']->format('Y-m-d'); ?></td>
                        <td><?php echo $slot['available_time']->format('H:i'); ?></td>
                        <td>
                            <?php echo $slot['is_booked'] ? 'Booked' : 'Available'; ?>
                        </td>
                        <td>
                            <?php if (!$slot['is_booked']): ?>
                                <a href="?delete=<?php echo $slot['availability_id']; ?>" class="admin-btn" style="background:#e53935;">Delete</a>
                            <?php else: ?>
                                <span class="admin-btn" style="color:gray; background:#e0e0e0;">Locked</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

        </table>

    </div>
</div>

<script src="assets/js/modal.js" defer>
    <?php if ($keepModalOpen): ?>
        document.addEventListener("DOMContentLoaded", function() {
            openScheduleModal();
            const err = document.getElementById("modalError");
            if (err) {
                err.style.display = "block";
                err.innerText = "<?php echo addslashes($error); ?>";
            }
        });
    <?php endif; ?>
</script>

</body>

</html>