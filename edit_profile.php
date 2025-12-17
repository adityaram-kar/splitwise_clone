<?php
require_once 'functions.php'; // Should include config.php and set $pdo
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = "";

// Avatar paths
$avatars = [
    'avatars/avatar1.jpg',
    'avatars/avatar2.jpg',
    'avatars/avatar3.jpg',
    'avatars/avatar4.jpg',
    'avatars/avatar5.jpg',
];

// Fetch user info
$stmt = $pdo->prepare("SELECT first_name, last_name, email, profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $selected_avatar = $_POST['avatar'] ?? $user['profile_pic'];

    if (empty($fname) || empty($lname) || empty($email) || empty($selected_avatar)) {
        $errors[] = "All fields and avatar must be selected.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif (!in_array($selected_avatar, $avatars)) {
        $errors[] = "Invalid avatar selection.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, profile_pic = ? WHERE id = ?");
        $result = $stmt->execute([$fname, $lname, $email, $selected_avatar, $user_id]);
        if ($result) {
            $success = "Profile updated successfully.";
            $user['first_name'] = $fname;
            $user['last_name'] = $lname;
            $user['email'] = $email;
            $user['profile_pic'] = $selected_avatar;
        } else {
            $errors[] = "Failed to update profile. Please try again. Error info: " . implode(', ', $stmt->errorInfo());
        }
    }
}

// Current avatar for preview
$currentAvatar = $user['profile_pic'] ?? $avatars[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Profile</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="cssFiles/edit_profile.css" rel="stylesheet" />
</head>
<body>
<div class="edit-card">
    <h2>Edit Profile</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="form-group">
        <label>Profile Picture</label>
        <div class="avatar-preview">
            <img id="selectedAvatar" src="<?= htmlspecialchars($currentAvatar) ?>" alt="Profile Picture">
        </div>
    </div>

    <form method="POST" action="edit_profile.php" autocomplete="off">
        <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name"
                   value="<?= htmlspecialchars($user['first_name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name"
                   value="<?= htmlspecialchars($user['last_name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <div class="form-group">
            <label>Select your Avatar</label>
            <div class="avatar-selection">
                <?php foreach ($avatars as $avatar): ?>
                    <label class="avatar-btn<?= ($user['profile_pic'] === $avatar) ? ' active' : '' ?>">
                        <input type="radio" name="avatar"
                               value="<?= htmlspecialchars($avatar) ?>"
                               <?= ($user['profile_pic'] === $avatar) ? 'checked' : '' ?> required>
                        <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar">
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit">Save Changes</button>
    </form>

    <a href="index.php" class="back-link">Back to Dashboard</a>
</div>

<script>
    // Update profile picture preview & highlight active avatar
    const avatarInputs = document.querySelectorAll('.avatar-btn input[type="radio"]');
    const preview = document.getElementById('selectedAvatar');
    avatarInputs.forEach(input => {
        input.addEventListener('change', () => {
            preview.src = input.value;
            document.querySelectorAll('.avatar-btn').forEach(label => {
                label.classList.remove('active');
            });
            input.parentElement.classList.add('active');
        });
    });
</script>
</body>
</html>
