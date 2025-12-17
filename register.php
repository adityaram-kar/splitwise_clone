<?php
require_once 'config.php';

$errors = [];
$success = '';
$profilePicPath = null;


// Avatar images
$avatars = [
    'avatars/avatar1.jpg',
    'avatars/avatar2.jpg',
    'avatars/avatar3.jpg',
    'avatars/avatar4.jpg',
    'avatars/avatar5.jpg',
];

// Fetch existing user data if editing
$userId = $_SESSION['user_id'] ?? null;
if ($userId) {
    $stmt = $pdo->prepare("SELECT profile_pic, first_name, last_name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $selected_avatar = $_POST['avatar'] ?? '';

    if (empty($fname) || empty($lname) || empty($email) || empty($selected_avatar)) {
        $errors[] = "All fields and avatar must be selected.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif ($password && $password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } elseif (!in_array($selected_avatar, $avatars)) {
        $errors[] = "Invalid avatar selection.";
    } else {
        $profilePicPath = $selected_avatar;
    }

    if (empty($errors)) {
        if ($userId) {
            // Update existing user
            $sql = "UPDATE users SET first_name=?, last_name=?, email=?, profile_pic=?";
            $params = [$fname, $lname, $email, $profilePicPath];

            if ($password) {
                $sql .= ", password_hash=?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id=?";
            $params[] = $userId;

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $success = "Profile updated successfully!";
                $userData['profile_pic'] = $profilePicPath;
                $userData['first_name'] = $fname;
                $userData['last_name'] = $lname;
                $userData['email'] = $email;
            } else {
                $errors[] = "Failed to update profile.";
            }
        } else {
            // New registration
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Email already registered.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, profile_pic) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$fname, $lname, $email, $password_hash, $profilePicPath])) {
                    header('Location: login.php?registered=1');
                    exit;
                } else {
                    $errors[] = "Registration failed. Please try again.";
                }
            }
        }
    }
}

// Determine avatar preview
$currentAvatar = $_POST['avatar'] ?? $userData['profile_pic'] ?? $avatars[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Profile / Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link href="cssFiles/register.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="card">
    <h1><?= $userId ? 'Edit Profile' : 'Create Account' ?></h1>
    <h4><?= $userId ? 'Update your profile' : 'Join and manage your group expenses easily' ?></h4>

    <?php if (!empty($errors)): ?>
        <div class="alert"><ul>
            <?php foreach($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? $userData['first_name'] ?? '') ?>" required />
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? $userData['last_name'] ?? '') ?>" required />
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $userData['email'] ?? '') ?>" required />
        </div>
        <div class="form-group">
            <label>Password <?= $userId ? '(leave blank to keep current)' : '' ?></label>
            <input type="password" name="password" />
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" />
        </div>

        <div class="form-group">
            <label>Profile Picture</label>
            <div class="avatar-preview">
                <img id="selectedAvatar" src="<?= htmlspecialchars($currentAvatar) ?>" alt="Selected Avatar">
            </div>
        </div>

        <div class="form-group">
            <label>Select your Avatar</label>
            <div class="avatar-selection">
                <?php foreach($avatars as $avatar): ?>
                    <label class="avatar-label">
                        <input type="radio" name="avatar" value="<?= htmlspecialchars($avatar) ?>"
                            <?= ($currentAvatar === $avatar) ? 'checked' : '' ?> required />
                        <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" />
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="btn"><?= $userId ? 'Save Changes' : 'Register' ?></button>

        <?php if (!$userId): ?>
            <div class="login-link">Already have an account? <a href="login.php">Login here</a></div>
        <?php endif; ?>
    </form>
</div>

<script>
    const avatarInputs = document.querySelectorAll('.avatar-label input[type="radio"]');
    const preview = document.getElementById('selectedAvatar');

    avatarInputs.forEach(input => {
        input.addEventListener('change', () => {
            preview.src = input.value;
        });
    });
</script>

</body>
</html>
