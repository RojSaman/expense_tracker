<?php
require_once 'db.php';
global $conn;
if (!empty($_SESSION['user_id'])) redirect('index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']    ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) {
        $error = 'Both fields are required.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            redirect('index.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login – Expense Ledger</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="auth.css">
</head>
<body class="auth-body">

<div class="auth-shell">

    <!-- Logo -->
    <div class="auth-logo">
        <h1>Expense <em>Ledger</em></h1>
        <p>Personal Finance Tracker</p>
    </div>

    <!-- Card -->
    <div class="auth-card">
        <h2>Welcome back</h2>
        <p class="subtitle">Sign in to manage your expenses.</p>

        <?php if (!empty($_GET['registered'])): ?>
            <div class="alert alert-success">✓ Account created — please log in.</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>

            <div class="field">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       placeholder="jane@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       autocomplete="email" autofocus required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Your password"
                       autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Login →
            </button>

        </form>
    </div>

    <div class="auth-footer">
        Don't have an account? <a href="signup.php">Sign up free</a>
    </div>

</div>

</body>
</html>
