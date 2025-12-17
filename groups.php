<?php
require_once 'config.php';


$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $groupName = trim($_POST['new_group_name'] ?? '');

        if ($groupName !== '') {
            $pdo->beginTransaction();

            // 1. Create group
            $stmt = $pdo->prepare("INSERT INTO groups (group_name, created_by) VALUES (?, ?)");
            $stmt->execute([$groupName, $userId]);
            $groupId = $pdo->lastInsertId();

            // 2. Add creator as member
            $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
            $stmt->execute([$groupId, $userId]);

            $pdo->commit();
        }

        header('Location: index.php');
        exit;
    }

    if ($action === 'join') {
        $groupId = (int)($_POST['join_group_id'] ?? 0);

        if ($groupId > 0) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)");
            $stmt->execute([$groupId, $userId]);
        }

        header('Location: index.php');
        exit;
    }
}

