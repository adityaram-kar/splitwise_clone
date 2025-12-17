<?php
require_once 'functions.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    die("Invalid user.");
}
$user_id = intval($_GET['user_id']);

// prevent deleting self (optional)
if ($user_id == $_SESSION['user_id']) {
    die("Admin cannot delete own account.");
}

// delete expense_shares where user is involved
$stmt = $pdo->prepare("DELETE FROM expense_shares WHERE user_id = ?");
$stmt->execute([$user_id]);

// delete expenses paid by this user (and their shares)
$stmt = $pdo->prepare("SELECT id FROM expenses WHERE paid_by = ?");
$stmt->execute([$user_id]);
$exp_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
if ($exp_ids) {
    $placeholders = implode(',', array_fill(0, count($exp_ids), '?'));
    $stmtDelShares = $pdo->prepare("DELETE FROM expense_shares WHERE expense_id IN ($placeholders)");
    $stmtDelShares->execute($exp_ids);
    $stmtDelExp = $pdo->prepare("DELETE FROM expenses WHERE id IN ($placeholders)");
    $stmtDelExp->execute($exp_ids);
}

// delete from group_members
$stmt = $pdo->prepare("DELETE FROM group_members WHERE user_id = ?");
$stmt->execute([$user_id]);

// finally delete user
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$user_id]);

header("Location: admin_dashboard.php?msg=user_deleted");
exit;
?>
