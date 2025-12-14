<?php
session_start();

$pageTitle = 'User Management';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'SuperAdmin')) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';
$conn = getDbConnection();

$perPage = 12;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $perPage;

$totalRow = fetchOne(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM Users
    WHERE role IN ('Patient','Doctor')
    "
);

$totalUsers = $totalRow['total'];
$totalPages = (int) ceil($totalUsers / $perPage);

$sqlUsers = "
    SELECT 
        u.user_id,
        u.full_name,
        u.username,
        u.email,
        u.role,
        u.is_active,
        d.specialization
    FROM Users u
    LEFT JOIN Doctors d ON d.user_id = u.user_id
    WHERE u.role IN ('Patient','Doctor')
    ORDER BY u.role, u.full_name
    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
";

$users = fetchAll($conn, $sqlUsers, [$offset, $perPage]);

if (isset($_POST['create_user'])) {

    $fullName = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = $_POST['role'];
    $specialization = trim($_POST['specialization'] ?? '');

    if ($fullName === '' || $username === '' || $email === '' || $password === '' || $confirmPassword === '' || $role === '') {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif ($password !== '' && strlen($password) < 12) {
        $error = "Password must be at least 12 characters.";
    } elseif ($password !== '' && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $error = "Password must contain at least one special character.";
    } elseif ($role === 'Doctor' && $specialization === '') {
        $error = "Specialization is required for doctors.";
    } else {

        // Username check
        $exists = fetchOne(
            $conn,
            "SELECT 1 FROM Users WHERE username = ?",
            [$username]
        );

        if ($exists) {
            $error = "Username already exists.";
        } else {
            sqlsrv_begin_transaction($conn);

            try {
                $passwordHash = hash('sha256', $password);

                $stmtUser = sqlsrv_query(
                    $conn,
                    "INSERT INTO Users (full_name, username, email, password_hash, role, is_active)
                     VALUES (?, ?, ?, ?, ?, 1)",
                    [$fullName, $username, $email, $passwordHash, $role]
                );

                if ($stmtUser === false) {
                    // throw new Exception("Failed to insert user.");
                    $errors = sqlsrv_errors();
                    throw new Exception($errors[0]['message']);
                }

                $newUser = fetchOne(
                    $conn,
                    "SELECT user_id FROM Users WHERE username = ?",
                    [$username]
                );

                if (!$newUser) {
                    throw new Exception("Failed to retrieve new user ID.");
                }

                if ($role === 'Doctor') {
                    $stmtDoctor = sqlsrv_query(
                        $conn,
                        "INSERT INTO Doctors (user_id, specialization)
                         VALUES (?, ?)",
                        [$newUser['user_id'], $specialization]
                    );

                    if ($stmtDoctor === false) {
                        throw new Exception("Failed to insert doctor record.");
                    }
                }

                sqlsrv_commit($conn);
                $message = ucfirst($role) . " created successfully.";

                header("Refresh:1; url=admin_users.php");
            } catch (Exception $e) {
                sqlsrv_rollback($conn);
                $error = "Creation failed: " . $e->getMessage();
                $keepModalOpen = true;
            }
        }
    }
}


if (isset($_POST['update_user'])) {
    $userId   = (int)$_POST['user_id'];
    $fullName = trim($_POST['full_name']);
    $role     = $_POST['role'];
    $password = $_POST['password'];

    if ($password !== '') {
        $passwordHash = hash('sha256', $password);

        $sql = "
            UPDATE Users
            SET full_name = ?, role = ?, is_active = ?, password_hash = ?
            WHERE user_id = ?
        ";

        $params = [$fullName, $role, 1, $passwordHash, $userId];
    } else {
        $sql = "
            UPDATE Users
            SET full_name = ?, role = ?, is_active = ?
            WHERE user_id = ?
        ";

        $params = [$fullName, $role, 1, $userId];
    }

    if ($role === 'Doctor') {
        sqlsrv_query(
            $conn,
            "
        IF EXISTS (SELECT 1 FROM Doctors WHERE user_id = ?)
            UPDATE Doctors SET specialization = ? WHERE user_id = ?
        ELSE
            INSERT INTO Doctors (user_id, specialization) VALUES (?, ?)
        ",
            [$userId, $_POST['specialization'], $userId, $userId, $_POST['specialization']]
        );
    }

    sqlsrv_query($conn, $sql, $params);
    $message = $role . " updated successfully.";

    header("Refresh:1; url=admin_users.php");
}

if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];

    if ($userId === $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        sqlsrv_query(
            $conn,
            "UPDATE Users SET is_active = 0 WHERE user_id = ?",
            [$userId]
        );

        $message = "User disabled successfully.";

        header("Refresh:1; url=admin_users.php");
    }
}

if (isset($_GET['activate'])) {
    if ($_SESSION['role'] !== 'SuperAdmin') {
        $error = "You are not authorized to activate accounts.";
    } else {
        $userId = (int)$_GET['activate'];

        sqlsrv_query(
            $conn,
            "UPDATE Users SET is_active = 1 WHERE user_id = ?",
            [$userId]
        );

        $message = "User reactivated successfully.";
        header("Refresh:1; url=admin_users.php");
    }
}

include __DIR__ . '/components/header.php';
?>
<style>
    .admin-container {
        background: #fff;
        padding: 25px;
        padding-top: 10px;
        border-radius: 10px;
    }

    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .admin-header h2 {
        margin: 0;
    }

    .btn {
        padding: 8px 14px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 14px;
        cursor: pointer;
        border: none;
    }

    .btn-primary {
        background: #1E88E5;
        color: white;
    }

    .btn-danger {
        background: #e53935;
        color: white;
    }

    .btn-warning {
        background: #fbc02d;
        color: #000;
    }

    .btn-disabled {
        background: #e0e0e0;
        color: #777;
        cursor: not-allowed;
    }

    .btn-success {
        background: #43a047;
        color: white;
    }

    .table-admin {
        width: 100%;
        border-collapse: collapse;
    }

    .table-admin th,
    .table-admin td {
        padding: 12px;
        text-align: center;
        border-bottom: 1px solid #eee;
    }

    .table-admin th {
        background: #f5f7fa;
    }

    .badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
    }

    .badge-active {
        background: #C8E6C9;
        color: #256029;
    }

    .badge-disabled {
        background: #FFCDD2;
        color: #B71C1C;
    }

    .message-success {
        background: #E8F5E9;
        color: #256029;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 15px;
    }

    .message-error {
        background: #FFEBEE;
        color: #B71C1C;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 15px;
    }

    .schedule-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        column-gap: 24px;
        row-gap: 18px;
    }

    .layout {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }
</style>

<div class="content-wrapper">
    <div class="admin-container">

        <div class="admin-header">
            <h2>User Management</h2>

            <div>
                <button class="btn btn-primary" onclick="openUserModal('Patient')">
                    + Patient
                </button>

                <button class="btn btn-primary" onclick="openUserModal('Doctor')">
                    + Doctor
                </button>
            </div>

            <div id="userModal" class="modal">
                <div class="modal-content">
                    <h3 id="modalRoleTitle"></h3>

                    <form method="post">
                        <input type="hidden" name="mode" id="formMode" value="create">
                        <input type="hidden" name="user_id" id="editUserId">
                        <input type="hidden" name="role" id="roleHidden">

                        <div class="schedule-form-grid">
                            <div class="layout" style="grid-column: span 2;">
                                <label style="text-align: left;">Full Name</label>
                                <input type="text" name="full_name" required>
                            </div>

                            <div class="layout">
                                <label style="text-align: left;">Username</label>
                                <input type="text" name="username" required>
                            </div>

                            <div class="layout">
                                <label style="text-align: left;">Email</label>
                                <input type="email" name="email" required>
                            </div>

                            <div class="layout">
                                <label style="text-align: left;">Password</label>
                                <input type="password" name="password">
                            </div>

                            <div class="layout">
                                <label style="text-align: left;">Confirm Password</label>
                                <input type="password" name="confirm_password">
                            </div>

                            <div id="specializationField" class="layout" style="display:none; grid-column: span 2;">
                                <label>Specialization</label>
                                <input type="text" name="specialization" id="specializationInput">
                            </div>
                        </div>

                        <div id="modalError" class="error-message" style="display:none; color:red; margin-top:10px;"></div>

                        <div class="btn-row">
                            <button type="submit" id="submitBtn" name="create_user" class="btn btn-primary">
                                Create
                            </button>

                            <button type="button"
                                class="btn btn-secondary"
                                onclick="closeModal('userModal')">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="message-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <table class="table-admin">
            <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['full_name']) ?></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= $u['role'] ?></td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge badge-active">Active</span>
                        <?php else: ?>
                            <span class="badge badge-disabled">Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td style="gap: 10px; display: flex; justify-content: center;">
                        <?php if ($u['is_active']): ?>
                            <button class="btn btn-warning"
                                onclick='openEditUserModal(<?= json_encode($u) ?>)'>
                                Edit
                            </button>

                            <?php if ($_SESSION['role'] === 'SuperAdmin'): ?>
                                <a href="?delete=<?= $u['user_id'] ?>"
                                    class="btn btn-danger"
                                    onclick="return confirm('Disable this user?')">
                                    Disable
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($_SESSION['role'] === 'SuperAdmin'): ?>
                                <span class="btn btn-success"
                                    onclick="if(confirm('Reactivate this user?')) { window.location='?activate=<?= $u['user_id'] ?>'; }">
                                    Activate
                                </span>
                            <?php else: ?>
                                <span class="btn btn-disabled">Disabled</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div style="margin-top:20px; text-align:center;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>"
                    style="
                   margin: 0 5px;
                   padding: 6px 12px;
                   border-radius: 4px;
                   text-decoration: none;
                   <?= $i === $page ? 'background:#1E88E5;color:white;' : 'background:#eee;color:#333;' ?>
               ">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

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

    function closeModal(id) {
        const modal = document.getElementById(id);
        modal.style.display = "none";

        const form = modal.querySelector("form");
        if (form) {
            form.reset();
            document.getElementById("formMode").value = "create";
            document.getElementById("editUserId").value = "";

            document.querySelector("input[name='username']").disabled = false;
            document.querySelector("input[name='email']").disabled = false;

            const submitBtn = document.getElementById("submitBtn");
            submitBtn.innerText = "Create";
            submitBtn.name = "create_user";
        }
    }
</script>