<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// If user is already logged in, redirect based on role
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../index.php");
    }
    exit;
}

$error = '';
$success = '';

// Check for registration success message
if (isset($_SESSION['reg_success'])) {
    $success = $_SESSION['reg_success'];
    unset($_SESSION['reg_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identity) || empty($password)) {
        $error = 'ইউজারনেম/ইমেইল এবং পাসওয়ার্ড প্রদান করুন।'; // "Provide identity and password"
    } else {
        try {
            // Find user by username OR email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username_identity OR email = :email_identity");
            $stmt->execute([
                ':username_identity' => $identity,
                ':email_identity' => $identity
            ]);
            $user = $stmt->fetch();

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    // Password matches, establish session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];

                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header("Location: ../admin/dashboard.php");
                    } else {
                        header("Location: ../index.php");
                    }
                    exit;
                } else {
                    $error = 'ইউজারনেম অথবা পাসওয়ার্ড সঠিক নয়। (ত্রুটি: পাসওয়ার্ড মেলেনি, ইনপুট দৈর্ঘ্য: ' . strlen($password) . ')';
                }
            } else {
                $error = 'ইউজারনেম অথবা পাসওয়ার্ড সঠিক নয়। (ত্রুটি: ইউজার খুঁজে পাওয়া যায়নি, ইনপুট: "' . htmlspecialchars($identity) . '")';
            }
        } catch (\PDOException $e) {
            $error = 'সার্ভারে সমস্যা হয়েছে। দয়া করে আবার চেষ্টা করুন। (Error: ' . htmlspecialchars($e->getMessage()) . ')';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>লগইন - The Deshi Shop</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=3">
</head>
<body>
    <!-- Theme Toggle Utility -->
    <div style="position: absolute; top: 24px; right: 24px; z-index: 10;">
        <button id="themeToggleBtn" class="theme-toggle-btn" aria-label="Toggle Theme">
            <!-- Sun Icon (Default) -->
            <svg id="sunIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
            <!-- Moon Icon (Hidden initially) -->
            <svg id="moonIcon" style="display: none;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.3 22h-.1c-5.5 0-10-4.5-10-10 0-4.8 3.5-8.9 8.2-9.8.5-.1 1 .2 1.2.7.2.5 0 1.1-.4 1.4-2.8 2.2-4.2 5.9-3.4 9.4.8 3.4 3.7 5.9 7.2 6.1.5 0 .9.3 1.1.8.2.5-.1 1.1-.6 1.3-.8.4-1.7.5-2.6.5z"/></svg>
        </button>
    </div>

    <div class="container">
        <div class="auth-wrapper">
            <div class="auth-card">
                <div class="logo" style="justify-content: center; margin-bottom: 24px; color: var(--primary);">
                    <span style="color: var(--primary);">The Deshi Shop</span>
                </div>
                <h1 class="auth-title">লগইন করুন</h1>
                <p class="auth-subtitle">The Deshi Shop এ প্রবেশ করুন</p>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form id="loginForm" action="login.php" method="POST" novalidate>
                    <div class="form-group">
                        <label for="identity" class="form-label">ইউজারনেম অথবা ইমেইল</label>
                        <input type="text" id="identity" name="identity" class="form-control" placeholder="যেমন: admin বা admin@example.com" required>
                        <span id="identityError" class="form-error-msg"></span>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">পাসওয়ার্ড</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="পাসওয়ার্ড প্রদান করুন" required>
                        <span id="passwordError" class="form-error-msg"></span>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">প্রবেশ করুন</button>
                </form>

                <div class="auth-footer">
                    <span>অ্যাকাউন্ট নেই? <a href="register.php">রেজিস্ট্রেশন করুন</a></span>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
