<?php
require_once '../includes/config.php';

// Already logged in
if (isLoggedIn()) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {

        $error = 'Please enter your email and password.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } else {

        $stmt = $conn->prepare(
            "SELECT id, full_name, email, password, currency
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if (!$stmt) {

            $error = 'Database error. Please try again.';

        } else {

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {

                // Regenerate session ID for security
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['currency'] = $user['currency'] ?? '₹';

                header('Location: ../pages/dashboard.php');
                exit;

            } else {

                $error = 'Invalid email or password.';
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign In - SpendSmart</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background:
                radial-gradient(circle at top left, #6366f1 0%, transparent 35%),
                radial-gradient(circle at bottom right, #8b5cf6 0%, transparent 35%),
                #0f172a;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 25px;
            color: white;
        }

        .logo {
            width: 64px;
            height: 64px;
            margin: 0 auto 15px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: bold;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            box-shadow: 0 15px 35px rgba(99, 102, 241, 0.35);
        }

        .logo-section h1 {
            font-size: 28px;
            margin-bottom: 6px;
        }

        .logo-section p {
            color: #cbd5e1;
            font-size: 14px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.3);
        }

        .login-card h2 {
            color: #0f172a;
            margin-bottom: 8px;
            font-size: 24px;
        }

        .subtitle {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
        }

        input {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 15px;
            outline: none;
            transition: 0.2s;
        }

        input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
        }

        .password-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .password-row label {
            margin-bottom: 0;
        }

        .forgot {
            font-size: 13px;
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
        }

        .forgot:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-size: 16px;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            cursor: pointer;
            transition: 0.2s;
            margin-top: 5px;
        }

        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 22px 0;
            color: #94a3b8;
            font-size: 12px;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .register-text {
            text-align: center;
            font-size: 14px;
            color: #64748b;
        }

        .register-text a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 700;
        }

        .register-text a:hover {
            text-decoration: underline;
        }

        .back-home {
            text-align: center;
            margin-top: 18px;
        }

        .back-home a {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 13px;
        }

        .back-home a:hover {
            color: white;
        }
    </style>
</head>

<body>

<div class="login-container">

    <div class="logo-section">
        <div class="logo">₹</div>
        <h1>SpendSmart</h1>
        <p>Manage your money with confidence</p>
    </div>

    <div class="login-card">

        <h2>Welcome Back</h2>
        <p class="subtitle">Sign in to access your expense dashboard.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="name@example.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">

                <div class="password-row">
                    <label for="password">Password</label>

                    <a href="forgot_password.php" class="forgot">
                        Forgot Password?
                    </a>
                </div>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="login-btn">
                Sign In
            </button>

        </form>

        <div class="divider">OR</div>

        <div class="register-text">
            Don't have an account?
            <a href="register.php">Create Account</a>
        </div>

    </div>

    <div class="back-home">
        <a href="../index.php">← Back to Home</a>
    </div>

</div>

</body>
</html>