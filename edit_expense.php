<?php
require_once 'config.php';


$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid expense ID.");
}
$expense_id = intval($_GET['id']);
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : '';


$errors = [];
$success = '';


$stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
$stmt->execute([$expense_id]);
$expense = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$expense) {
    die("Expense not found.");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description']);
    $amount = trim($_POST['amount']);
    $date = trim($_POST['date']);


    if ($description === "" || $amount === "" || $date === "") {
        $errors[] = "All fields are required.";
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $errors[] = "Amount must be a positive number.";
    }


    if (empty($errors)) {
        $stmtUpdate = $pdo->prepare("UPDATE expenses SET description = ?, amount = ?, expense_date = ? WHERE id = ?");
        if ($stmtUpdate->execute([$description, $amount, $date, $expense_id])) {
            // Update shares evenly
            $stmtShares = $pdo->prepare("SELECT user_id FROM expense_shares WHERE expense_id = ?");
            $stmtShares->execute([$expense_id]);
            $shareUsers = $stmtShares->fetchAll(PDO::FETCH_ASSOC);
            $numUsers = count($shareUsers);
            if ($numUsers > 0) {
                $newShareAmount = $amount / $numUsers;
                $updateShareStmt = $pdo->prepare("UPDATE expense_shares SET share_amount = ? WHERE expense_id = ? AND user_id = ?");
                foreach ($shareUsers as $user) {
                    $updateShareStmt->execute([$newShareAmount, $expense_id, $user['user_id']]);
                }
            }
            $success = "Expense and shares updated successfully!";
            $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
            $stmt->execute([$expense_id]);
            $expense = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $errors[] = "Failed to update expense. Please try again.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Expense</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:500px;">
    <h2>Edit Expense</h2>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $error) {
                echo "<li>" . htmlspecialchars($error) . "</li>";
            } ?></ul>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <input type="text" id="description" name="description" class="form-control" value="<?= htmlspecialchars($expense['description']) ?>" required />
        </div>
        <div class="mb-3">
            <label for="amount" class="form-label">Amount</label>
            <input type="number" step="0.01" id="amount" name="amount" class="form-control" value="<?= htmlspecialchars($expense['amount']) ?>" required />
        </div>
        <div class="mb-3">
            <label for="date" class="form-label">Date</label>
            <input type="date" id="date" name="date" class="form-control" value="<?= htmlspecialchars($expense['expense_date']) ?>" required />
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="expenses.php?group_id=<?= $group_id ?>" class="btn btn-secondary ms-1">Cancel</a>
    </form>
    <a href="expenses.php?group_id=<?= $group_id ?>" class="btn btn-link mt-3">← Back to Group Expenses</a>
</div>
</body>
</html> 