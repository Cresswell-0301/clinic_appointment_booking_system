<?php
session_start();

$pageTitle = 'Appointment Management';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'SuperAdmin')) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';
$conn = getDbConnection();

$where = [];
$params = [];

$statusFilter = $_GET['status'] ?? 'All';
$search       = substr(trim($_GET['q'] ?? ''), 0, 100);

$dateFrom = trim($_GET['from'] ?? '');
$dateTo   = trim($_GET['to'] ?? '');

if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = '';
if ($dateTo   !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo = '';

if ($dateFrom !== '') {
    $where[]  = "a.appointment_date >= ?";
    $params[] = $dateFrom;
}

if ($dateTo !== '') {
    $where[]  = "a.appointment_date <= ?";
    $params[] = $dateTo;
}

$page     = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 12;
$offset   = ($page - 1) * $pageSize;

$allowedSort = [
    'date'    => 'appointment_dt',
    'status'  => 'a.status',
    'patient' => 'p.full_name',
    'doctor'  => 'd.full_name'
];

$sortKey = $_GET['sort'] ?? 'date';
$sortCol = $allowedSort[$sortKey] ?? $allowedSort['date'];

$order = strtoupper($_GET['order'] ?? 'DESC');
$order = in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC';

$dateArrow   = arrowIcon('date', $sortKey, $order);
$statusArrow = arrowIcon('status', $sortKey, $order);

if ($statusFilter !== 'All') {
    $where[] = "a.status = ?";
    $params[] = $statusFilter;
}

if ($search !== '') {
    $where[] = "(p.full_name LIKE ? OR p.username LIKE ? OR p.email LIKE ?
                 OR d.full_name LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
SELECT
    a.appointment_id,
    DATEADD(SECOND, DATEDIFF(SECOND, '00:00:00', a.appointment_time), CAST(a.appointment_date AS datetime2)) AS appointment_dt,
    a.status,
    p.full_name AS patient_name,
    p.username  AS patient_username,
    d.full_name AS doctor_name,
    doc.specialization
FROM Appointments a
JOIN Users   p   ON a.patient_id = p.user_id
JOIN Doctors doc ON a.doctor_id  = doc.doctor_id
JOIN Users   d   ON doc.user_id  = d.user_id
$whereSql
ORDER BY $sortCol $order
OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
";

$appointments = fetchAll(
    $conn,
    $sql,
    array_merge($params, [$offset, $pageSize])
);

$sqlCount = "
SELECT COUNT(*) AS total
FROM Appointments a
JOIN Users   p   ON a.patient_id = p.user_id
JOIN Doctors doc ON a.doctor_id  = doc.doctor_id
JOIN Users   d   ON doc.user_id  = d.user_id
$whereSql
";

$totalRow = fetchOne($conn, $sqlCount, $params);
$total = (int)($totalRow['total'] ?? 0);
$totalPages = max(1, (int)ceil($total / $pageSize));

function arrowIcon($key, $currentKey, $order)
{
    if ($key === $currentKey) {
        return $order === 'ASC'
            ? '<i class="fa-solid fa-angle-up"></i>'
            : '<i class="fa-solid fa-angle-down"></i>';
    }
    return '<i class="fa-solid fa-angle-down"></i>';
}

include __DIR__ . '/components/header.php';
?>

<div class="content-wrapper">
    <div class="admin-container">

        <h2>Appointment Management</h2>

        <!-- Filter Bar -->
        <form method="get" class="filter-bar">
            <select name="status">
                <?php
                $statuses = ['All', 'Booked', 'Completed', 'Cancelled'];
                foreach ($statuses as $s):
                ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>>
                        <?= $s ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="q"
                placeholder="Search patient / doctor"
                value="<?= htmlspecialchars($search) ?>">

            <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>">
            <input type="date" name="to" value="<?= htmlspecialchars($dateTo) ?>">

            <button class="btn btn-primary" style="margin-top: 0;">Filter</button>

            <?php
            $hasFilters =
                ($statusFilter !== 'All') ||
                ($search !== '') ||
                ($dateFrom !== '') ||
                ($dateTo !== '');
            ?>

            <?php if ($hasFilters): ?>
                <button type="button" class="btn btn-secondary" style="margin-top: 0;"
                    onclick="window.location='admin_appointments.php'">
                    Clear
                </button>
            <?php endif; ?>
        </form>

        <table class="table-admin">
            <tr>
                <th>
                    <a href="?<?= http_build_query(array_merge($_GET, [
                                    'sort'  => 'date',
                                    'order' => ($sortKey === 'date' && $order === 'ASC') ? 'DESC' : 'ASC'
                                ])) ?>"
                        style="text-decoration: none; color: inherit;">
                        Date <?= $dateArrow ?>
                    </a>
                </th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Specialization</th>
                <th>
                    <a href="?<?= http_build_query(array_merge($_GET, [
                                    'sort'  => 'status',
                                    'order' => ($sortKey === 'status' && $order === 'ASC') ? 'DESC' : 'ASC'
                                ])) ?>"
                        style="text-decoration: none; color: inherit;">
                        Status <?= $statusArrow ?>
                    </a>
                </th>
            </tr>

            <?php if (empty($appointments)): ?>
                <tr>
                    <td colspan="5">No appointments found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($appointments as $a): ?>
                    <tr>
                        <td><?= $a['appointment_dt']->format('Y-m-d H:i') ?></td>
                        <td><?= htmlspecialchars($a['patient_name']) ?></td>
                        <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($a['specialization'] ?? '-') ?></td>
                        <td>
                            <span class="badge badge-<?= $a['status'] ?>">
                                <?= $a['status'] ?>
                            </span>
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