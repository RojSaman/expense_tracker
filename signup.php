<?php
require_once 'db.php';
global $conn;
if (!empty($_SESSION['user_id'])) redirect('index.php');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $pass    = $_POST['password']     ?? '';
    $confirm = $_POST['confirm']      ?? '';

    if (!$name || !$email || !$pass || !$confirm) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicate email
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $chk->bind_param('s', $email);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $error = 'An account with that email already exists.';
            $chk->close();
        } else {
            $chk->close();
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins  = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $ins->bind_param('sss', $name, $email, $hash);

            if ($ins->execute()) {
                $success = 'Account created! You can now log in.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
            $ins->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Up – Expense Ledger</title>
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
        <h2>Create account</h2>
        <p class="subtitle">Start tracking your expenses in seconds.</p>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>

            <div class="field">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" placeholder="Jane Doe"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                       autocomplete="name" required>
            </div>

            <div class="field">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="jane@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       autocomplete="email" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Min. 6 characters"
                       autocomplete="new-password" required>
            </div>

            <div class="field">
                <label for="confirm">Confirm Password</label>
                <input type="password" id="confirm" name="confirm"
                       placeholder="Repeat your password"
                       autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Create Account →
            </button>

        </form>
    </div>

    <div class="auth-footer">
        Already have an account? <a href="login.php">Login here</a>
    </div>

</div>

</body>
</html>
