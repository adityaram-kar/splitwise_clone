<?php
require_once 'functions.php';
redirectIfNotLoggedIn();

$expense_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
$user_id = $_SESSION['user_id'];

// Validate expense and group
if (!$expense_id || !$group_id) {
    die('Invalid request.');
}

// Only delete if user is member of group
$stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$group_id, $user_id]);
if ($stmt->rowCount() === 0) {
    die("Access denied.");
}

// Remove shares first (due to FK constraints)
$stmt = $pdo->prepare("DELETE FROM expense_shares WHERE expense_id = ?");
$stmt->execute([$expense_id]);

// Remove expense
$stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND group_id = ?");
$stmt->execute([$expense_id, $group_id]);

// Redirect back
header("Location: expenses.php?group_id=".$group_id);
exit;
?>
