<?php
session_start();
require_once 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Please enter both email and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, password_hash, role 
                                   FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['role'] !== 'admin') {
                    $errors[] = "Access denied. This is for admins only.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['role'] = $user['role'];

                    header("Location: admin_dashboard.php");
                    exit;
                }
            } else {
                $errors[] = "Invalid email or password.";
            }

        } catch (PDOException $e) {
            $errors[] = "An error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin Login - Splitwise Clone</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<!-- Your external CSS -->
<link rel="stylesheet" href="cssFiles/admin_login.css">
</head>
<body>

<div class="admin-login-card">

    <div class="admin-logo">
        <svg fill="none" viewBox="0 0 200 200" width="70" height="70">
            <rect x="40" y="40" width="40" height="40" rx="10" fill="#5fffc1"/>
            <rect x="80" y="80" width="60" height="60" rx="17" fill="#5fffc1" opacity="0.6"/>
            <rect x="50" y="110" width="60" height="25" rx="8" fill="#2859ff" opacity="0.3"/>
            <rect x="120" y="45" width="35" height="35" rx="10" fill="#e95aff" opacity="0.7"/>
        </svg>
    </div>

    <div class="admin-brand">Splitwise Admin</div>
    <div class="admin-desc">Admin panel access only</div>

    <h2>Admin Login</h2>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?= htmlspecialchars(implode("<br>", $errors)); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="admin_login.php" autocomplete="off">
        <div class="form-group">
            <label for="email">Email</label>
            <div class="input-wrap">
                <input type="text" id="email" name="email" required />
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrap">
                <input type="password" id="password" name="password" required />
            </div>
        </div>

        <button type="submit" class="login-btn">Sign In</button>
    </form>

    <div class="back-link">
        <a href="login.php">Back to User Login</a>
    </div>

</div>

</body>
</html>
