<?php
require_once 'config.php';
require_once 'functions.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

// Permission: must be a current member in group_members
$stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$group_id, $user_id]);
if ($stmt->rowCount() === 0) {
    die("You do not have permission to delete this group (not a member).");
}

// Remove all expense shares
$stmt = $pdo->prepare("SELECT id FROM expenses WHERE group_id = ?");
$stmt->execute([$group_id]);
$expense_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($expense_ids) {
    $placeholders = implode(',', array_fill(0, count($expense_ids), '?'));
    $delShares = $pdo->prepare("DELETE FROM expense_shares WHERE expense_id IN ($placeholders)");
    $delShares->execute($expense_ids);
}

// Remove all expenses
$stmt = $pdo->prepare("DELETE FROM expenses WHERE group_id = ?");
$stmt->execute([$group_id]);

// Remove all memberships
$stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ?");
$stmt->execute([$group_id]);

// Remove the group itself
$stmt = $pdo->prepare("DELETE FROM groups WHERE id = ?");
$stmt->execute([$group_id]);

header('Location: index.php?msg=group_deleted');
exit;
?>
