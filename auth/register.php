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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect & sanitize input
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic Server-side Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'সকল ঘর পূরণ করা আবশ্যক।'; // "All fields are required"
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $error = 'ইউজারনেম অবশ্যই ৩ থেকে ৩০ অক্ষরের মধ্যে হতে হবে।'; // "Username must be between 3 and 30 characters"
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'সঠিক ইমেইল অ্যাড্রেস প্রদান করুন।'; // "Provide a valid email address"
    } elseif (strlen($password) < 6) {
        $error = 'পাসওয়ার্ড অন্তত ৬ অক্ষরের হতে হবে।'; // "Password must be at least 6 characters"
    } elseif ($password !== $confirm_password) {
        $error = 'পাসওয়ার্ড দুটি মেলেনি।'; // "Passwords do not match"
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute([':username' => $username, ':email' => $email]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'ইউজারনেম অথবা ইমেইল ইতিমধ্যে ব্যবহার করা হয়েছে।'; // "Username or email already exists"
            } else {
                // Securely hash password using bcrypt
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // Insert new user as customer by default
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'customer')");
                $insert_stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $hashed_password
                ]);
                
                $_SESSION['reg_success'] = 'নিবন্ধন সফল হয়েছে। লগইন করুন।'; // "Registration successful. Please login."
                header("Location: login.php");
                exit;
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
    <title>নিবন্ধন - The Deshi Shop</title>
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
                <h1 class="auth-title">অ্যাকাউন্ট তৈরি করুন</h1>
                <p class="auth-subtitle">The Deshi Shop এ কেনাকাটা করতে নিবন্ধন করুন</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form id="registerForm" action="register.php" method="POST" novalidate>
                    <div class="form-group">
                        <label for="username" class="form-label">ইউজারনেম</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="যেমন: demo_user" required>
                        <span id="usernameError" class="form-error-msg"></span>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">ইমেইল এড্রেস</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="যেমন: user@example.com" required>
                        <span id="emailError" class="form-error-msg"></span>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">পাসওয়ার্ড</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="কমপক্ষে ৬টি অক্ষর" required>
                        <span id="passwordError" class="form-error-msg"></span>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">পাসওয়ার্ড নিশ্চিত করুন</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="পাসওয়ার্ডটি পুনরায় টাইপ করুন" required>
                        <span id="confirmPasswordError" class="form-error-msg"></span>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">নিবন্ধন করুন</button>
                </form>

                <div class="auth-footer">
                    <span>ইতিমধ্যে অ্যাকাউন্ট আছে? <a href="login.php">লগইন করুন</a></span>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
