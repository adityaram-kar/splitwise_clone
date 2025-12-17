<?php
session_start();
require_once 'config.php';

$errors = [];

// Read remembered email from cookie
$remembered_email = $_COOKIE['remember_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Please enter both username and password.";
    } else {
                try {
            // Get the user row by email (no hard-coded columns)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $isValid = false;

            if ($user) {
                // If your table has password_hash (hashed)
                if (isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                    $isValid = true;
                }
                // If your table has password (plain text or hashed)
                elseif (isset($user['password'])) {
                    if (password_verify($password, $user['password']) || $password === $user['password']) {
                        $isValid = true;
                    }
                }
            }

            if ($isValid) {
                // Set session variables safely
                $_SESSION['user_id']    = $user['id'] ?? null;
                $_SESSION['first_name'] = $user['first_name'] ?? '';
                $_SESSION['last_name']  = $user['last_name'] ?? '';
                $_SESSION['role']       = $user['role'] ?? 'user';

                // Update login_count ONLY if that column exists
                if (isset($user['login_count']) && isset($user['id'])) {
                    $new_count = (int)$user['login_count'] + 1;
                    $updateStmt = $pdo->prepare("UPDATE users SET login_count = ? WHERE id = ?");
                    $updateStmt->execute([$new_count, $user['id']]);
                    $_SESSION['login_count'] = $new_count;
                }

                // Set or clear remember email cookie
                if (!empty($_POST['remember_me'])) {
                    setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), "/");
                } else {
                    setcookie('remember_email', '', time() - 3600, "/");
                }

                // Redirect based on role if it exists
                if (isset($user['role']) && $user['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $errors[] = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            // Show real error while debugging
            $errors[] = "DB ERROR: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Sign In - Splitwise Clone</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="cssFiles/login.css">
</head>
<body>
<div class="login-wrapper">
    <!-- LEFT SIDE -->
    <div class="login-graphic">
        <div class="brand">Splitwise</div>
        <svg fill="none" viewBox="0 0 200 200">
            <rect x="40" y="40" width="40" height="40" rx="10" fill="#5fffc1"/>
            <rect x="80" y="80" width="60" height="60" rx="17" fill="#5fffc1" opacity="0.6"/>
            <rect x="50" y="110" width="60" height="25" rx="8" fill="#2859ff" opacity="0.3"/>
            <rect x="120" y="45" width="35" height="35" rx="10" fill="#e95aff" opacity="0.7"/>
        </svg>
        <p>Manage your shared expenses effortlessly</p>
    </div>
    <!-- RIGHT SIDE -->
    <div class="login-panel">
        <h2>Welcome Back</h2>
        <p>Sign in to continue managing your bills</p>
        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <?php foreach ($errors as $err): ?>
                    <?= htmlspecialchars($err) ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="login.php" autocomplete="off">
            <div class="form-group">
                <label>User Name</label>
                <div class="input-wrap">
                    <input type="text" name="email" value="<?= htmlspecialchars($remembered_email) ?>" required />
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password" required />
                    <i class="fa fa-eye" id="togglePwd"></i>
                </div>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="remember_me" <?= $remembered_email ? 'checked' : ''; ?>>
                    Remember my email
                </label>
            </div>
            <button class="login-btn" type="submit">Sign In</button>
        </form>
        <div class="register-link">
            Don't have an account?
            <a href="register.php">Register here</a>
        </div>
        <div class="register-link" style="margin-top: 10px;">
            <span>Are you an admin?</span>
            <a href="admin_login.php" style="color: #3b82f6; font-weight: 700;margin-left:4px;">Admin Login</a>
        </div>
    </div>
</div>
<script>
const pwd = document.getElementById('password');
const toggle = document.getElementById('togglePwd');
toggle.onclick = () => {
    if (pwd.type === "password") {
        pwd.type = "text";
        toggle.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        pwd.type = "password";
        toggle.classList.replace("fa-eye-slash", "fa-eye");
    }
};
</script>
</body>
</html>
