<?php
require_once __DIR__ . '/includes/setCookies.php';

session_start();

$pageTitle = "Audit Logs";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'SuperAdmin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';

$conn = getDbConnection();

$emailFilter = trim($_GET['email'] ?? '');

$pageSize = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $pageSize;

$countSql = "
    SELECT COUNT(*) AS total
    FROM AuditLogs a
    LEFT JOIN Users u ON u.user_id = a.user_id
    WHERE 1=1
";

$params = [];

if ($emailFilter !== '') {
    $countSql .= " AND u.email LIKE ?";
    $params[] = "%$emailFilter%";
}

$totalRow = fetchOne($conn, $countSql, $params);
$total = (int)($totalRow['total'] ?? 0);
$totalPages = max(1, ceil($total / $pageSize));

$sql = "
    SELECT
        a.audit_id,
        a.user_id,
        u.email AS user_email,
        a.user_role,
        a.action_type,
        a.entity_name,
        a.ip_address,
        a.created_at
    FROM AuditLogs a
    LEFT JOIN Users u ON u.user_id = a.user_id
    WHERE 1=1
";

$paramsFetch = [];

if ($emailFilter !== '') {
    $sql .= " AND u.email LIKE ?";
    $paramsFetch[] = "%$emailFilter%";
}

$sql .= "
    ORDER BY a.created_at DESC
    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
";

$paramsFetch[] = $offset;
$paramsFetch[] = $pageSize;

$logs = fetchAll($conn, $sql, $paramsFetch);

include __DIR__ . '/components/header.php';
?>

<style>
    .audit-container {
        background: #fff;
        padding: 25px;
        border-radius: 10px;
    }

    .audit-table {
        width: 100%;
        border-collapse: collapse;
    }

    .audit-table th,
    .audit-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .audit-table th {
        background: #f5f7fa;
    }
</style>

<div class="content-wrapper">
    <div class="audit-container">
        <h2>Audit Logs</h2>

        <form method="get" style="margin-bottom:15px; display:flex; gap:10px; align-items:center;">
            <input type="text"
                name="email"
                placeholder="Filter by user email"
                value="<?= htmlspecialchars($_GET['email'] ?? '') ?>"
                style="padding:6px 10px; border:1px solid #ccc; border-radius:4px;">

            <button type="submit"
                class="btn btn-primary"
                style="padding:6px 12px; margin-top: 0;">
                Filter
            </button>

            <?php if (!empty($_GET['email'])): ?>
                <a href="admin_audit_logs.php"
                    style="margin-left:5px; font-size:14px; text-decoration: none; color:#555;">
                    Clear
                </a>
            <?php endif; ?>
        </form>

        <table class="audit-table">
            <tr>
                <th>Date / Time</th>
                <th>User ID</th>
                <th>User Email</th>
                <th>Role</th>
                <th>Action</th>
                <th>Entity</th>
                <th>IP Address</th>
            </tr>

            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="7" style="padding:30px;color:#777;">
                        No audit records found
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <?php
                            $dt = $log['created_at'] ?? null;

                            if ($dt instanceof DateTimeInterface) {
                                echo htmlspecialchars($dt->format('Y-m-d H:i:s'));
                            } else {
                                echo htmlspecialchars((string)$dt);
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars((string)($log['user_id'] ?? 'System')) ?></td>
                        <td><?= htmlspecialchars($log['user_email'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($log['user_role'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($log['action_type'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($log['entity_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>

        <?php if ($totalPages > 1): ?>
            <div style="margin-top:20px;text-align:center;">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                        style="padding:6px 12px;margin:0 4px; text-decoration: none; border-radius:4px; display:inline-block;
                       <?= $i === $page ? 'background:#1E88E5;color:#fff;' : 'background:#eee;' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    </div>
</div>