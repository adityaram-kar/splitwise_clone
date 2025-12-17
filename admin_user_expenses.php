<?php
require_once 'functions.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    die("Invalid user.");
}
$user_id = intval($_GET['user_id']);

// fetch user
$stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) die("User not found.");

// fetch expenses paid by this user
$stmt = $pdo->prepare("
    SELECT e.id, e.description, e.amount, e.expense_date, g.group_name
    FROM expenses e
    LEFT JOIN groups g ON e.group_id = g.id
    WHERE e.paid_by = ?
    ORDER BY e.expense_date DESC
");
$stmt->execute([$user_id]);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin - User Expenses</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="cssFiles/admin_user_expenses.css" rel="stylesheet" />
</head>
<body>
<div class="wrapper">
    <h3>Expenses paid by <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>

    <a href="admin_dashboard.php" class="btn-back mb-3">← Back to Admin Dashboard</a>

    <?php if ($expenses): ?>
        <table class="table table-bordered mt-3">
            <thead>
            <tr>
                <th>ID</th>
                <th>Group</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Admin Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($expenses as $e): ?>
                <tr>
                    <td><?= $e['id']; ?></td>
                    <td><?= htmlspecialchars($e['group_name'] ?? 'No Group'); ?></td>
                    <td><?= htmlspecialchars($e['description']); ?></td>
                    <td><?= number_format($e['amount'], 2); ?></td>
                    <td><?= htmlspecialchars($e['expense_date']); ?></td>
                    <td>
                        <a href="edit_expense.php?id=<?= $e['id']; ?>&group_id=0" class="btn-edit">Edit</a>
                        <a href="delete_expense.php?id=<?= $e['id']; ?>&group_id=0"
                           class="btn-del"
                           onclick="return confirm('Delete this expense?');">
                            Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No expenses found for this user.</p>
    <?php endif; ?>
</div>
</body>
</html>
