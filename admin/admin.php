<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
require_once __DIR__ . '/../config/db.php';

// Handle permission update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions'])) {
    $role_id = intval($_POST['role_id']);
    $modules = ['students','teachers','attendance','exams','fees','timetable','reports','notifications','users'];
    
    foreach ($modules as $module) {
        $can_view   = isset($_POST["view_{$module}"])   ? 1 : 0;
        $can_add    = isset($_POST["add_{$module}"])    ? 1 : 0;
        $can_edit   = isset($_POST["edit_{$module}"])   ? 1 : 0;
        $can_delete = isset($_POST["delete_{$module}"]) ? 1 : 0;

        $stmt = $pdo->prepare("INSERT INTO permissions (role_id, module, can_view, can_add, can_edit, can_delete)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE can_view=?, can_add=?, can_edit=?, can_delete=?");
        $stmt->execute([$role_id, $module, $can_view, $can_add, $can_edit, $can_delete,
                        $can_view, $can_add, $can_edit, $can_delete]);
    }
    $success = "Permissions updated successfully!";
}

// Fetch roles
$roles = $pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);

// Fetch permissions for selected role
$selected_role = isset($_GET['role_id']) ? intval($_GET['role_id']) : 1;
$perms_stmt = $pdo->prepare("SELECT * FROM permissions WHERE role_id = ?");
$perms_stmt->execute([$selected_role]);
$perms_raw = $perms_stmt->fetchAll(PDO::FETCH_ASSOC);
$perms = [];
foreach ($perms_raw as $p) {
    $perms[$p['module']] = $p;
}

$modules = ['students','teachers','attendance','exams','fees','timetable','reports','notifications','users'];

// User stats
$user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$role_stats = $pdo->query("SELECT r.role_name, COUNT(u.id) as cnt FROM roles r LEFT JOIN users u ON u.role_id = r.id GROUP BY r.id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - MindMerge</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .perm-table { width:100%; border-collapse:collapse; margin-top:20px; }
        .perm-table th, .perm-table td { padding:10px 14px; border:1px solid #ddd; text-align:center; }
        .perm-table th { background:#1e293b; color:#fff; }
        .perm-table tr:nth-child(even) { background:#f8fafc; }
        .module-name { text-align:left; font-weight:600; text-transform:capitalize; }
        .role-tabs { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
        .role-tab { padding:8px 20px; border-radius:6px; text-decoration:none; background:#e2e8f0; color:#1e293b; font-weight:600; }
        .role-tab.active { background:#1e293b; color:#fff; }
        .stat-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:30px; }
        .stat-card { background:#fff; border-radius:10px; padding:20px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
        .stat-card h3 { margin:0 0 6px; font-size:2rem; color:#6366f1; }
        .stat-card p { margin:0; color:#64748b; font-size:0.9rem; text-transform:capitalize; }
        .save-btn { margin-top:16px; padding:10px 28px; background:#6366f1; color:#fff; border:none; border-radius:6px; font-size:1rem; cursor:pointer; }
        .save-btn:hover { background:#4f46e5; }
        .success-msg { background:#d1fae5; color:#065f46; padding:12px; border-radius:6px; margin-bottom:16px; }
        input[type=checkbox] { width:18px; height:18px; cursor:pointer; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../dashboard/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1>⚙️ Admin Panel</h1>
        <p>Manage user roles and module permissions</p>
    </div>

    <?php if (isset($success)): ?>
        <div class="success-msg">✅ <?= $success ?></div>
    <?php endif; ?>

    <!-- Role Stats -->
    <div class="stat-cards">
        <?php foreach ($role_stats as $rs): ?>
        <div class="stat-card">
            <h3><?= $rs['cnt'] ?></h3>
            <p><?= htmlspecialchars($rs['role_name']) ?>s</p>
        </div>
        <?php endforeach; ?>
        <div class="stat-card">
            <h3><?= $user_count ?></h3>
            <p>Total Users</p>
        </div>
    </div>

    <!-- Role Tabs -->
    <div class="role-tabs">
        <?php foreach ($roles as $role): ?>
        <a href="?role_id=<?= $role['id'] ?>" 
           class="role-tab <?= $selected_role == $role['id'] ? 'active' : '' ?>">
            <?= ucfirst($role['role_name']) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Permissions Form -->
    <div class="card">
        <h2>🔐 Permissions for: 
            <?php 
            $rname = array_filter($roles, fn($r) => $r['id'] == $selected_role);
            echo ucfirst(array_values($rname)[0]['role_name'] ?? '');
            ?>
        </h2>
        <form method="POST" action="?role_id=<?= $selected_role ?>">
            <input type="hidden" name="role_id" value="<?= $selected_role ?>">
            <table class="perm-table">
                <thead>
                    <tr>
                        <th class="module-name">Module</th>
                        <th>👁 View</th>
                        <th>➕ Add</th>
                        <th>✏️ Edit</th>
                        <th>🗑 Delete</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($modules as $mod): 
                    $p = $perms[$mod] ?? ['can_view'=>0,'can_add'=>0,'can_edit'=>0,'can_delete'=>0];
                ?>
                <tr>
                    <td class="module-name"><?= ucfirst($mod) ?></td>
                    <td><input type="checkbox" name="view_<?= $mod ?>"   <?= $p['can_view']   ? 'checked' : '' ?>></td>
                    <td><input type="checkbox" name="add_<?= $mod ?>"    <?= $p['can_add']    ? 'checked' : '' ?>></td>
                    <td><input type="checkbox" name="edit_<?= $mod ?>"   <?= $p['can_edit']   ? 'checked' : '' ?>></td>
                    <td><input type="checkbox" name="delete_<?= $mod ?>" <?= $p['can_delete'] ? 'checked' : '' ?>></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($selected_role != 1): ?>
            <button type="submit" name="update_permissions" class="save-btn">💾 Save Permissions</button>
            <?php else: ?>
            <p style="color:#94a3b8; margin-top:12px;">⚠️ Admin always has full access — permissions locked.</p>
            <?php endif; ?>
        </form>
    </div>

    <div style="margin-top:20px; display:flex; gap:12px;">
        <a href="manage-users.php" style="padding:10px 24px; background:#1e293b; color:#fff; border-radius:6px; text-decoration:none;">👥 Manage Users</a>
        <a href="settings.php" style="padding:10px 24px; background:#6366f1; color:#fff; border-radius:6px; text-decoration:none;">⚙️ System Settings</a>
    </div>
</div>
</body>
</html>