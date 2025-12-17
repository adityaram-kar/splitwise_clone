<?php
require_once 'functions.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

$stmt = $pdo->query("SELECT id, first_name, last_name, email FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard - Splitwise Clone</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Your custom CSS file -->
    <link rel="stylesheet" href="cssFiles/admin.css" />
</head>
<body>
<div class="admin-container">
    <div class="heading">Admin Dashboard</div>
    <div class="btn-row">
        <a href="logout.php" class="btn btn-logout">Logout</a>
    </div>

    <div class="section-title">Registered Users</div>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Group Memberships</th>
            <th>Recent Activity</th>
            <th>Admin Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td>
                    <?php
                    $stmt2 = $pdo->prepare("SELECT g.group_name FROM groups g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = ?");
                    $stmt2->execute([$user['id']]);
                    $groups = $stmt2->fetchAll(PDO::FETCH_COLUMN);
                    echo htmlspecialchars(implode(', ', $groups));
                    ?>
                </td>
                <td>
                    <?php
                    $stmt3 = $pdo->prepare("SELECT MAX(last_activity) as recent FROM (
                        SELECT MAX(created_at) as last_activity FROM expenses WHERE paid_by = ?
                        UNION
                        SELECT MAX(joined_at) as last_activity FROM group_members WHERE user_id = ?
                    ) t");
                    $stmt3->execute([$user['id'], $user['id']]);
                    $recent = $stmt3->fetchColumn();
                    echo $recent ? $recent : 'No activity';
                    ?>
                </td>
                <td class="action-btns">
                    <a href="admin_user_expenses.php?user_id=<?php echo $user['id']; ?>" class="btn btn-custom btn-view">View Expenses</a>
                    <a href="admin_delete_user.php?user_id=<?php echo $user['id']; ?>" class="btn btn-custom btn-delete"
                       onclick="return confirm('Delete this user and ALL their data?');">
                        Delete User
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
