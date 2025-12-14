<?php
session_start();

$pageTitle = 'Doctor Availability';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'SuperAdmin'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';
$conn = getDbConnection();

$where  = [];
$params = [];

$doctorId = (int)($_GET['doctor_id'] ?? 0);
$date     = trim($_GET['date'] ?? '');
$status = $_GET['status'] ?? 'All';

$allowedSort = [
    'date'   => 'da.available_date',
    'time'   => 'da.available_time',
    'doctor' => 'u.full_name',
    'status' => 'da.is_booked'
];

$sortKey = $_GET['sort'] ?? 'date';
$sortCol = $allowedSort[$sortKey] ?? $allowedSort['date'];

$order = strtoupper($_GET['order'] ?? 'DESC');
$order = in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC';

if ($doctorId > 0) {
    $where[]  = "d.doctor_id = ?";
    $params[] = $doctorId;
}

if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $where[]  = "da.available_date = ?";
    $params[] = $date;
}

if ($status === 'Available') {
    $where[] = "da.is_booked = 0";
}

if ($status === 'Booked') {
    $where[] = "da.is_booked = 1";
}

$page     = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 12;
$offset   = ($page - 1) * $pageSize;

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
SELECT
    da.availability_id,
    da.available_date,
    da.available_time,
    da.is_booked,
    u.full_name AS doctor_name,
    doc.specialization
FROM DoctorAvailability da
JOIN Doctors doc ON da.doctor_id = doc.doctor_id
JOIN Users u     ON doc.user_id = u.user_id
$whereSql
ORDER BY $sortCol $order
OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
";

$rows = fetchAll(
    $conn,
    $sql,
    array_merge($params, [$offset, $pageSize])
);

$sqlCount = "
SELECT COUNT(*) AS total
FROM DoctorAvailability da
JOIN Doctors doc ON da.doctor_id = doc.doctor_id
JOIN Users u     ON doc.user_id = u.user_id
$whereSql
";

$totalRow  = fetchOne($conn, $sqlCount, $params);
$total     = (int)($totalRow['total'] ?? 0);
$totalPages = max(1, (int)ceil($total / $pageSize));

$doctors = fetchAll(
    $conn,
    "
    SELECT d.doctor_id, u.full_name
    FROM Doctors d
    JOIN Users u ON d.user_id = u.user_id
    ORDER BY u.full_name
    "
);

function arrowIcon($key, $current, $order)
{
    if ($key !== $current) {
        return '<i class="fa-solid fa-angle-down" style="opacity:.4"></i>';
    }
    return $order === 'ASC'
        ? '<i class="fa-solid fa-angle-up"></i>'
        : '<i class="fa-solid fa-angle-down"></i>';
}

include __DIR__ . '/components/header.php';
?>
<div class="content-wrapper">
    <div class="admin-container">

        <h2>Doctor Availability</h2>

        <form method="get" class="filter-bar">
            <select name="doctor_id">
                <option value="0">All Doctors</option>
                <?php foreach ($doctors as $d): ?>
                    <option value="<?= $d['doctor_id'] ?>"
                        <?= $doctorId == $d['doctor_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">

            <select name="status">
                <?php foreach (['All', 'Available', 'Booked'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>>
                        <?= $s ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button class="btn btn-primary" style="margin-top: 0;">Filter</button>

            <?php if ($doctorId || $date || $status !== 'All'): ?>
                <button type="button" class="btn btn-secondary" style="margin-top: 0;"
                    onclick="window.location='admin_doctor_availability.php'">
                    Clear
                </button>
            <?php endif; ?>

        </form>


        <table class="table-admin">
            <tr>
                <th>
                    <a href="?<?= http_build_query(array_merge($_GET, [
                                    'sort' => 'date',
                                    'order' => ($sortKey === 'date' && $order === 'ASC') ? 'DESC' : 'ASC'
                                ])) ?>"
                        style="text-decoration: none; color: inherit;">
                        Date <?= arrowIcon('date', $sortKey, $order) ?>
                    </a>
                </th>

                <th>
                    <a href="?<?= http_build_query(array_merge($_GET, [
                                    'sort' => 'time',
                                    'order' => ($sortKey === 'time' && $order === 'ASC') ? 'DESC' : 'ASC'
                                ])) ?>"
                        style="text-decoration: none; color: inherit;">
                        Time <?= arrowIcon('time', $sortKey, $order) ?>
                    </a>
                </th>

                <th>
                    <a href="?<?= http_build_query(array_merge($_GET, [
                                    'sort' => 'doctor',
                                    'order' => ($sortKey === 'doctor' && $order === 'ASC') ? 'DESC' : 'ASC'
                                ])) ?>"
                        style="text-decoration: none; color: inherit;">
                        Doctor <?= arrowIcon('doctor', $sortKey, $order) ?>
                    </a>
                </th>

                <th>Specialization</th>

                <th>
                    <a href="?<?= http_build_query(array_merge($_GET, [
                                    'sort' => 'status',
                                    'order' => ($sortKey === 'status' && $order === 'ASC') ? 'DESC' : 'ASC'
                                ])) ?>"
                        style="text-decoration: none; color: inherit;">
                        Status <?= arrowIcon('status', $sortKey, $order) ?>
                    </a>
                </th>
            </tr>

            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="5">No availability found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <?php
                            $d = $r['available_date'];
                            echo ($d instanceof DateTime)
                                ? $d->format('Y-m-d')
                                : htmlspecialchars((string)$d);
                            ?>
                        </td>
                        <td>
                            <?php
                            $t = $r['available_time'];
                            echo ($t instanceof DateTime)
                                ? $t->format('H:i')
                                : htmlspecialchars(substr((string)$t, 0, 5));
                            ?>
                        </td>
                        <td><?= htmlspecialchars($r['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($r['specialization'] ?? '-') ?></td>
                        <td>
                            <?php if ($r['is_booked']): ?>
                                <span class="badge badge-Cancelled">Booked</span>
                            <?php else: ?>
                                <span class="badge badge-Completed">Available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                        style=" 
                        margin: 0 5px; 
                        padding: 6px 12px; 
                        border-radius: 4px; 
                        text-decoration: none;">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    </div>
</div>