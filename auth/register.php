<?php
require_once '../includes/config.php';
if (isLoggedIn()) { header('Location: ../pages/dashboard.php'); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['full_name'] ?? '');
    $uname = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $cpass = $_POST['confirm_password'] ?? '';
    $budget = floatval($_POST['monthly_budget'] ?? 0);

    if (!$name || !$uname || !$email || !$pass || !$cpass) {
        $error = 'Please fill in all required fields.';
    } elseif ($pass !== $cpass) {
        $error = 'Passwords do not match.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $check->bind_param("ss", $email, $uname);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email or username already exists.';
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, monthly_budget) VALUES (?,?,?,?,?)");
            $stmt->bind_param("ssssd", $name, $uname, $email, $hashed, $budget);
            if ($stmt->execute()) {
                $uid = $conn->insert_id;
                // Insert default categories
                $cats = [
                    ['Food & Dining','fa-utensils','#f59e0b','expense'],
                    ['Transportation','fa-car','#3b82f6','expense'],
                    ['Shopping','fa-shopping-bag','#ec4899','expense'],
                    ['Entertainment','fa-film','#8b5cf6','expense'],
                    ['Health','fa-heartbeat','#ef4444','expense'],
                    ['Bills','fa-file-invoice','#f97316','expense'],
                    ['Rent','fa-home','#64748b','expense'],
                    ['Education','fa-graduation-cap','#06b6d4','expense'],
                    ['Salary','fa-briefcase','#10b981','income'],
                    ['Freelance','fa-laptop','#6366f1','income'],
                    ['Other Income','fa-plus-circle','#84cc16','income'],
                    ['Other Expense','fa-minus-circle','#94a3b8','expense'],
                ];
                $ci = $conn->prepare("INSERT INTO categories (user_id,name,icon,color,type) VALUES (?,?,?,?,?)");
                foreach ($cats as $c) {
                    $ci->bind_param("issss", $uid, $c[0], $c[1], $c[2], $c[3]);
                    $ci->execute();
                }
                $_SESSION['user_id'] = $uid;
                $_SESSION['user_name'] = $name;
                header('Location: ../pages/dashboard.php?success=Welcome+to+SpendSmart!');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – SpendSmart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: #0f172a;
            display: flex; align-items: center; justify-content: center;
            padding: 40px 20px;
            position: relative;
        }
        .bg {
            position: fixed; inset: 0;
            background: radial-gradient(ellipse 80% 60% at 20% 20%, rgba(99,102,241,0.2) 0%, transparent 60%),
                        radial-gradient(ellipse 60% 80% at 80% 80%, rgba(139,92,246,0.15) 0%, transparent 60%),
                        linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        .bg-dots {
            position: fixed; inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 30px 30px;
        }
        .register-box {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 48px 44px;
            width: 100%; max-width: 560px;
            position: relative; z-index: 1;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }
        .register-brand {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 32px;
        }
        .r-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: white;
        }
        .r-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 22px; font-weight: 800; color: white;
        }
        h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 24px; font-weight: 800; color: white;
            margin-bottom: 6px; letter-spacing: -0.5px;
        }
        .sub { font-size: 14px; color: rgba(255,255,255,0.4); margin-bottom: 32px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.65); margin-bottom: 7px; }
        .input-wrap { position: relative; }
        .input-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.3); font-size: 13px; }
        input, select {
            width: 100%; padding: 12px 14px 12px 40px;
            background: rgba(255,255,255,0.06);
            border: 1.5px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            font-size: 14px; color: white;
            font-family: 'Inter', sans-serif;
            outline: none; transition: all 0.25s;
            appearance: none;
        }
        input::placeholder { color: rgba(255,255,255,0.22); }
        input:focus, select:focus {
            border-color: #6366f1;
            background: rgba(99,102,241,0.1);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
        }
        select option { background: #1e293b; }
        .error-box {
            background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25);
            border-radius: 10px; padding: 12px 16px; margin-bottom: 20px;
            font-size: 13px; color: #f87171;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-reg {
            width: 100%; padding: 15px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border: none; border-radius: 12px;
            font-size: 15px; font-weight: 700; color: white;
            cursor: pointer; font-family: 'Inter', sans-serif;
            box-shadow: 0 8px 25px rgba(99,102,241,0.5);
            transition: all 0.25s ease;
            margin-top: 8px;
        }
        .btn-reg:hover { transform: translateY(-2px); box-shadow: 0 12px 35px rgba(99,102,241,0.6); }
        .login-link { text-align: center; margin-top: 20px; font-size: 13px; color: rgba(255,255,255,0.4); }
        .login-link a { color: #818cf8; font-weight: 600; text-decoration: none; }
        .password-hint { font-size: 11px; color: rgba(255,255,255,0.25); margin-top: 4px; }
        @media (max-width: 540px) { .form-row { grid-template-columns: 1fr; } .register-box { padding: 32px 24px; } }
    </style>
</head>
<body>
<div class="bg"></div>
<div class="bg-dots"></div>

<div class="register-box">
    <div class="register-brand">
        <div class="r-icon"><i class="fas fa-wallet"></i></div>
        <span class="r-title">SpendSmart</span>
    </div>

    <h2>Create your account 🚀</h2>
    <p class="sub">Join thousands of smart spenders managing their finances</p>

    <?php if ($error): ?>
    <div class="error-box"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Full Name *</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" name="full_name" placeholder="John Doe" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Username *</label>
                <div class="input-wrap">
                    <i class="fas fa-at"></i>
                    <input type="text" name="username" placeholder="john123" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Email Address *</label>
            <div class="input-wrap">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="john@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Password *</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Min 6 characters" required>
                </div>
            </div>
            <div class="form-group">
                <label>Confirm Password *</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" placeholder="Repeat password" required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Monthly Budget (₹)</label>
            <div class="input-wrap">
                <i class="fas fa-rupee-sign"></i>
                <input type="number" name="monthly_budget" placeholder="e.g. 30000" step="100" min="0" value="<?= htmlspecialchars($_POST['monthly_budget'] ?? '') ?>">
            </div>
            <div class="password-hint">You can change this anytime in settings</div>
        </div>

        <button type="submit" class="btn-reg">
            <i class="fas fa-user-plus"></i> &nbsp;Create Account
        </button>
    </form>

    <div class="login-link">
        Already have an account? <a href="../index.php">Sign in →</a>
    </div>
</div>
</body>
</html>
