<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password – SpendSmart</title>
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
        }
        .bg { position: fixed; inset: 0; background: radial-gradient(ellipse 80% 60% at 50% 50%, rgba(99,102,241,0.15) 0%, transparent 70%), linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
        .box {
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(20px); border-radius: 24px; padding: 48px 44px;
            width: 100%; max-width: 440px; position: relative; z-index: 1;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4); text-align: center;
        }
        .icon { font-size: 48px; margin-bottom: 20px; }
        h2 { font-family: 'Space Grotesk', sans-serif; font-size: 24px; font-weight: 800; color: white; margin-bottom: 8px; }
        p { font-size: 14px; color: rgba(255,255,255,0.45); line-height: 1.7; margin-bottom: 28px; }
        .alert { background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.25); border-radius: 10px; padding: 14px 18px; font-size: 14px; color: #fbbf24; margin-bottom: 20px; text-align: left; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12); border-radius: 10px; color: white; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.25s; }
        .back-btn:hover { background: rgba(255,255,255,0.12); }
    </style>
</head>
<body>
<div class="bg"></div>
<div class="box">
    <div class="icon">🔐</div>
    <h2>Forgot Password?</h2>
    <p>Password reset via email is not implemented in this demo. Please use the demo credentials to log in.</p>
    <div class="alert">
        <strong>Demo Credentials:</strong><br>
        Email: <code>demo@expensemanager.com</code><br>
        Password: <code>password</code>
    </div>
    <a href="../index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Login</a>
</div>
</body>
</html>
