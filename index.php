<?php
require_once 'includes/config.php';

// Direct session check from existing architecture
if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
    exit();
}

$error = '';
$success = '';

// Handle Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, email, password, currency FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['currency'] = $user['currency'] ?? '₹';
            header("Location: pages/dashboard.php");
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// Handle Register POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email is already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // The registration form only collects a full name, but the
            // database's "username" column is NOT NULL and UNIQUE.
            // Auto-generate a unique username from the full name so the
            // insert satisfies the schema without changing the UI.
            $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
            if ($base === '') { $base = 'user'; }
            $base = substr($base, 0, 40);
            $username = $base;
            $suffix = 0;
            $uname_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            do {
                if ($suffix > 0) { $username = $base . $suffix; }
                $uname_check->bind_param("s", $username);
                $uname_check->execute();
                $taken = $uname_check->get_result()->num_rows > 0;
                $suffix++;
            } while ($taken);

            $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, currency) VALUES (?, ?, ?, ?, '₹')");
            $stmt->bind_param("ssss", $name, $username, $email, $hashed);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Add default starter categories
                $default_categories = [
                    ['Salary', 'income', '#10B981', 'fas fa-money-bill-wave'],
                    ['Freelance', 'income', '#3B82F6', 'fas fa-laptop-code'],
                    ['Investments', 'income', '#8B5CF6', 'fas fa-chart-line'],
                    ['Food & Dining', 'expense', '#EF4444', 'fas fa-utensils'],
                    ['Shopping', 'expense', '#EC4899', 'fas fa-shopping-bag'],
                    ['Transportation', 'expense', '#F59E0B', 'fas fa-car'],
                    ['Bills & Utilities', 'expense', '#6366F1', 'fas fa-file-invoice-dollar'],
                    ['Entertainment', 'expense', '#14B8A6', 'fas fa-film'],
                    ['Health', 'expense', '#06B6D4', 'fas fa-heartbeat']
                ];
                $cat_stmt = $conn->prepare("INSERT INTO categories (user_id, name, type, color, icon) VALUES (?, ?, ?, ?, ?)");
                foreach ($default_categories as $cat) {
                    $cat_stmt->bind_param("issss", $user_id, $cat[0], $cat[1], $cat[2], $cat[3]);
                    $cat_stmt->execute();
                }
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['currency'] = '₹';
                header("Location: pages/dashboard.php");
                exit();
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
    <title>SpendSmart — Your Money, Organized</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #07090e;
            --bg-surface: #0e131f;
            --bg-surface-elevated: #151c2e;
            --border-color: rgba(255, 255, 255, 0.08);
            --border-highlight: rgba(99, 102, 241, 0.35);
            
            --primary: #6366f1;
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --card-gradient: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            
            --color-green: #10b981;
            --color-red: #f43f5e;
            --color-orange: #f59e0b;
            --color-purple: #8b5cf6;
            --color-blue: #3b82f6;
            --color-teal: #14b8a6;
            
            --glass-bg: rgba(15, 23, 42, 0.75);
            --glass-blur: blur(16px);
            --shadow-subtle: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
            --shadow-glow: 0 20px 50px rgba(99, 102, 241, 0.2);
            --radius-lg: 20px;
            --radius-md: 12px;
            --radius-sm: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient Glow Blobs */
        .ambient-glow {
            position: absolute;
            width: 550px;
            height: 550px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            filter: blur(140px);
        }
        .glow-top-left {
            top: -200px;
            left: -200px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.3) 0%, rgba(9, 9, 11, 0) 70%);
        }
        .glow-top-right {
            top: 5%;
            right: -250px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.25) 0%, rgba(9, 9, 11, 0) 70%);
        }
        .glow-center {
            top: 45%;
            left: 30%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, rgba(9, 9, 11, 0) 70%);
        }

        .landing-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 24px;
            position: relative;
            z-index: 1;
        }

        .section-padding {
            padding: 100px 0;
        }

        /* Header Navigation */
        .landing-header {
            padding: 24px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(7, 9, 14, 0.85);
            backdrop-filter: var(--glass-blur);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 800;
            font-size: 1.35rem;
            letter-spacing: -0.5px;
        }
        .brand-icon {
            width: 38px;
            height: 38px;
            background: var(--primary-gradient);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }
        .landing-nav {
            display: flex;
            gap: 32px;
        }
        .landing-nav a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .landing-nav a:hover {
            color: var(--text-primary);
        }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border: none;
        }
        .btn-primary {
            background: var(--primary-gradient);
            color: #fff;
            box-shadow: 0 4px 18px rgba(99, 102, 241, 0.35);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
            color: #fff;
        }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.2);
        }
        .btn-glass {
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            backdrop-filter: var(--glass-blur);
        }
        .btn-glass:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--border-highlight);
        }
        .btn-lg {
            padding: 14px 28px;
            font-size: 1.05rem;
            border-radius: var(--radius-md);
        }
        .btn-xl {
            padding: 18px 36px;
            font-size: 1.15rem;
            border-radius: var(--radius-md);
        }
        .btn-block {
            width: 100%;
        }

        /* Hero Section */
        .hero-section {
            padding: 80px 0 100px;
            position: relative;
        }
        .hero-grid {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: rgba(99, 102, 241, 0.12);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 30px;
            color: #a5b4fc;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 24px;
        }
        .hero-title {
            font-size: 3.6rem;
            line-height: 1.12;
            font-weight: 800;
            margin-bottom: 24px;
            letter-spacing: -1px;
        }
        .gradient-text {
            background: linear-gradient(135deg, #a5b4fc 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-description {
            font-size: 1.15rem;
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 36px;
            max-width: 520px;
        }
        .hero-cta-group {
            display: flex;
            gap: 16px;
            margin-bottom: 40px;
        }
        .hero-subtext {
            display: flex;
            gap: 20px;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .hero-subtext span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .hero-subtext i {
            color: var(--color-green);
        }

        /* Visual Stage & 3D Components */
        .visual-stage {
            position: relative;
            height: 480px;
            perspective: 1200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Digital Credit Card */
        .digital-credit-card {
            width: 350px;
            height: 220px;
            border-radius: 20px;
            padding: 24px;
            position: relative;
            background: var(--card-gradient);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), 0 0 40px rgba(99, 102, 241, 0.3);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            backdrop-filter: var(--glass-blur);
        }
        .main-card-3d {
            z-index: 10;
            animation: finFloat 7s ease-in-out infinite;
            transform-style: preserve-3d;
        }
        @keyframes finFloat {
            0% { transform: translateY(0px) rotateX(12deg) rotateY(-18deg) rotateZ(2deg); }
            50% { transform: translateY(-22px) rotateX(16deg) rotateY(-10deg) rotateZ(0deg); }
            100% { transform: translateY(0px) rotateX(12deg) rotateY(-18deg) rotateZ(2deg); }
        }

        .card-glass-shine {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                rgba(255, 255, 255, 0) 30%,
                rgba(255, 255, 255, 0.12) 50%,
                rgba(255, 255, 255, 0) 70%
            );
            pointer-events: none;
        }
        .card-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-brand {
            font-weight: 700;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-network {
            font-size: 1.3rem;
            font-weight: 900;
            font-style: italic;
            letter-spacing: 1px;
        }
        .card-chip-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .card-chip {
            width: 40px;
            height: 30px;
            background: linear-gradient(135deg, #fcd34d 0%, #d97706 100%);
            border-radius: 6px;
            position: relative;
        }
        .contactless-icon {
            font-size: 1.1rem;
            opacity: 0.8;
        }
        .card-number-row {
            display: flex;
            justify-content: space-between;
            font-family: 'Courier New', Courier, monospace;
            font-size: 1.25rem;
            letter-spacing: 2px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
        }
        .card-bottom-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .card-holder label, .card-expiry label {
            display: block;
            font-size: 0.65rem;
            letter-spacing: 1px;
            opacity: 0.7;
        }
        .card-holder strong, .card-expiry strong {
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        /* Floating Money Notes Card */
        .money-notes-card {
            position: absolute;
            bottom: -20px;
            left: -30px;
            width: 250px;
            background: var(--glass-bg);
            border: 1px solid var(--border-highlight);
            border-radius: var(--radius-md);
            padding: 16px;
            backdrop-filter: var(--glass-blur);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            z-index: 12;
            animation: notesFloat 6s ease-in-out infinite 1s;
        }
        @keyframes notesFloat {
            0% { transform: translateY(0px) rotate(-4deg); }
            50% { transform: translateY(-16px) rotate(-1deg); }
            100% { transform: translateY(0px) rotate(-4deg); }
        }
        .notes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .notes-title {
            font-weight: 700;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
            color: #c7d2fe;
        }
        .mini-add-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: #fff;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }
        .notes-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .note-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            background: rgba(255, 255, 255, 0.03);
            padding: 6px 10px;
            border-radius: 6px;
        }
        .note-item.done .note-check { color: var(--color-green); }
        .note-item.pending .note-check { color: var(--color-orange); }
        .note-detail {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }
        .note-val { font-weight: 600; }
        .note-val.green { color: var(--color-green); }
        .note-val.orange { color: var(--color-orange); }

        /* Floating Pill Transactions */
        .float-item {
            position: absolute;
            z-index: 5;
            animation: pillFloat 8s ease-in-out infinite;
        }
        .tx-pill {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            padding: 8px 16px;
            border-radius: 50px;
            backdrop-filter: var(--glass-blur);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }
        .tx-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }
        .tx-icon.green { background: rgba(16, 185, 129, 0.2); color: var(--color-green); }
        .tx-icon.red { background: rgba(244, 63, 94, 0.2); color: var(--color-red); }
        .tx-icon.orange { background: rgba(245, 158, 11, 0.2); color: var(--color-orange); }
        .tx-icon.blue { background: rgba(59, 130, 246, 0.2); color: var(--color-blue); }

        .tx-info {
            display: flex;
            flex-direction: column;
        }
        .tx-label { font-size: 0.75rem; color: var(--text-muted); }
        .tx-amt { font-size: 0.85rem; font-weight: 700; }
        .tx-amt.green { color: var(--color-green); }
        .tx-amt.red { color: var(--color-red); }

        .tx-1 { top: 20px; right: -20px; animation-delay: 0.5s; }
        .tx-2 { bottom: 60px; right: -10px; animation-delay: 2s; }
        .tx-3 { top: 60px; left: -40px; animation-delay: 1.5s; }
        .tx-4 { bottom: -10px; right: 120px; animation-delay: 3s; }

        @keyframes pillFloat {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-14px); }
            100% { transform: translateY(0px); }
        }

        /* Features Section */
        .section-heading {
            margin-bottom: 60px;
        }
        .section-subtitle {
            text-transform: uppercase;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: var(--primary);
            margin-bottom: 8px;
            display: inline-block;
        }
        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 12px;
        }
        .section-desc {
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
            font-size: 1.05rem;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }
        .feature-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 32px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .feature-card:hover {
            transform: translateY(-8px);
            border-color: var(--border-highlight);
            box-shadow: var(--shadow-glow);
            background: var(--bg-surface-elevated);
        }
        .feature-icon-wrap {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 24px;
        }
        .icon-red { background: rgba(244, 63, 94, 0.15); color: var(--color-red); }
        .icon-green { background: rgba(16, 185, 129, 0.15); color: var(--color-green); }
        .icon-purple { background: rgba(139, 92, 246, 0.15); color: var(--color-purple); }
        .icon-blue { background: rgba(59, 130, 246, 0.15); color: var(--color-blue); }
        .icon-teal { background: rgba(20, 184, 166, 0.15); color: var(--color-teal); }
        .icon-indigo { background: rgba(99, 102, 241, 0.15); color: var(--primary); }

        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .feature-card p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        /* Mockup Preview Window */
        .preview-mockup-window {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.8), 0 0 50px rgba(99, 102, 241, 0.15);
        }
        .mockup-top-bar {
            background: rgba(15, 23, 42, 0.8);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }
        .mockup-dots {
            display: flex;
            gap: 8px;
        }
        .mockup-dots span {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #334155;
        }
        .mockup-dots span:nth-child(1) { background: #ef4444; }
        .mockup-dots span:nth-child(2) { background: #f59e0b; }
        .mockup-dots span:nth-child(3) { background: #10b981; }
        .mockup-address {
            margin-left: 20px;
            background: rgba(255, 255, 255, 0.05);
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .mockup-body {
            padding: 32px;
        }
        .preview-metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .preview-stat-card {
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
        }
        .stat-title {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: block;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .stat-badge {
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 12px;
        }
        .stat-badge.positive { background: rgba(16, 185, 129, 0.15); color: var(--color-green); }
        .stat-sub { font-size: 0.75rem; color: var(--text-muted); }

        .preview-dashboard-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 24px;
        }
        .preview-tx-box, .preview-insight-box {
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 24px;
        }
        .box-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .badge-mini {
            font-size: 0.7rem;
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            padding: 3px 8px;
            border-radius: 6px;
        }
        .preview-tx-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .p-tx-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }
        .p-tx-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .p-tx-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }
        .p-tx-icon.green { background: rgba(16, 185, 129, 0.15); color: var(--color-green); }
        .p-tx-icon.red { background: rgba(244, 63, 94, 0.15); color: var(--color-red); }
        .p-tx-icon.orange { background: rgba(245, 158, 11, 0.15); color: var(--color-orange); }
        .p-tx-icon.blue { background: rgba(59, 130, 246, 0.15); color: var(--color-blue); }
        .p-tx-icon.teal { background: rgba(20, 184, 166, 0.15); color: var(--color-teal); }

        .p-tx-left strong { font-size: 0.9rem; display: block; }
        .p-tx-left small { font-size: 0.75rem; color: var(--text-muted); }
        .p-tx-amount { font-weight: 700; font-size: 0.95rem; }
        .p-tx-amount.green { color: var(--color-green); }
        .p-tx-amount.red { color: var(--color-red); }

        /* Progress bars in preview */
        .chart-bars-wrap {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 24px;
        }
        .bar-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            margin-bottom: 6px;
        }
        .bar-track {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
            overflow: hidden;
        }
        .bar-fill { height: 100%; border-radius: 4px; }
        .insight-tip {
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: var(--radius-sm);
            padding: 12px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Money Notes Cards */
        .money-notes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        .reminder-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 24px;
            transition: all 0.3s ease;
        }
        .reminder-card:hover {
            transform: translateY(-6px);
        }
        .glow-indigo:hover { border-color: #6366f1; box-shadow: 0 10px 30px rgba(99, 102, 241, 0.2); }
        .glow-purple:hover { border-color: #a855f7; box-shadow: 0 10px 30px rgba(168, 85, 247, 0.2); }
        .glow-green:hover { border-color: #10b981; box-shadow: 0 10px 30px rgba(168, 185, 129, 0.2); }
        .glow-blue:hover { border-color: #3b82f6; box-shadow: 0 10px 30px rgba(59, 130, 246, 0.2); }
        .glow-orange:hover { border-color: #f59e0b; box-shadow: 0 10px 30px rgba(245, 158, 11, 0.2); }

        .rem-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            color: var(--text-muted);
        }
        .rem-badge {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .reminder-card h4 {
            font-size: 1.1rem;
            margin-bottom: 6px;
        }
        .rem-val {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 16px;
        }
        .rem-status {
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
        }
        .rem-status.done { color: var(--color-green); }
        .rem-status.pending { color: var(--color-orange); }
        .rem-status.active { color: var(--color-purple); }
        .rem-status.review { color: var(--color-blue); }

        /* Card Spending Showcase */
        .card-deck-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 32px;
            max-width: 900px;
            margin: 0 auto;
        }
        .deck-card-wrapper {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 24px;
        }
        .mini-card {
            width: 100%;
            height: 180px;
            margin-bottom: 20px;
        }
        .variant-blue {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        }
        .variant-purple {
            background: linear-gradient(135deg, #581c87 0%, #9333ea 100%);
        }
        .card-metrics-box {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .cm-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
        }
        .cm-progress-track {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
            overflow: hidden;
        }
        .cm-progress-bar {
            height: 100%;
            background: var(--color-blue);
            border-radius: 4px;
        }
        .cm-progress-bar.bar-purple { background: var(--color-purple); }
        .cm-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
        }
        .badge-safe {
            background: rgba(16, 185, 129, 0.15);
            color: var(--color-green);
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 600;
        }
        .due-text { color: var(--text-muted); }

        /* Step Cards */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }
        .step-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 36px 28px;
            position: relative;
            text-align: center;
        }
        .step-number-pill {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 0.8rem;
            font-weight: 800;
            color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
            padding: 4px 10px;
            border-radius: 12px;
        }
        .step-icon {
            width: 64px;
            height: 64px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
            margin: 0 auto 24px;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }
        .step-card h3 {
            font-size: 1.3rem;
            margin-bottom: 12px;
        }
        .step-card p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        /* CTA Section */
        .cta-banner-glass {
            background: linear-gradient(135deg, rgba(30, 27, 75, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
            border: 1px solid var(--border-highlight);
            border-radius: 30px;
            padding: 80px 40px;
            text-align: center;
            backdrop-filter: var(--glass-blur);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6), 0 0 60px rgba(99, 102, 241, 0.2);
        }
        .cta-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: -1px;
        }
        .cta-desc {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto 36px;
        }

        /* Footer */
        .landing-footer {
            border-top: 1px solid var(--border-color);
            padding: 60px 0 40px;
            background: #05070a;
        }
        .footer-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 24px;
        }
        .footer-brand p {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 8px;
            max-width: 320px;
        }
        .footer-links {
            display: flex;
            gap: 24px;
        }
        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        .footer-links a:hover { color: var(--text-primary); }
        .footer-copy {
            width: 100%;
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.04);
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }
        .modal-overlay.active {
            display: flex;
        }
        .auth-modal-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-highlight);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 440px;
            padding: 36px;
            position: relative;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7);
        }
        .modal-close-btn {
            position: absolute;
            top: 16px;
            right: 20px;
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.6rem;
            cursor: pointer;
        }
        .auth-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 24px;
        }
        .auth-tab-btn {
            flex: 1;
            background: none;
            border: none;
            padding: 12px;
            color: var(--text-muted);
            font-family: inherit;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        .auth-tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-secondary);
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: #fff;
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: var(--primary);
        }
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: rgba(244, 63, 94, 0.15);
            border: 1px solid rgba(244, 63, 94, 0.3);
            color: #fca5a5;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .hero-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .hero-description {
                margin: 0 auto 36px;
            }
            .hero-cta-group, .hero-subtext {
                justify-content: center;
            }
            .preview-dashboard-grid {
                grid-template-columns: 1fr;
            }
            .landing-nav {
                display: none;
            }
            .visual-stage {
                margin-top: 40px;
            }
        }
        @media (max-width: 600px) {
            .hero-title {
                font-size: 2.5rem;
            }
            .digital-credit-card {
                width: 300px;
                height: 190px;
                padding: 18px;
            }
            .money-notes-card {
                left: 0;
                bottom: -30px;
                width: 220px;
            }
            .hero-cta-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="landing-body">

    <!-- Ambient Glow Backgrounds -->
    <div class="ambient-glow glow-top-left"></div>
    <div class="ambient-glow glow-top-right"></div>
    <div class="ambient-glow glow-center"></div>

    <!-- Navigation Header -->
    <header class="landing-header">
        <div class="landing-container nav-container">
            <a href="index.php" class="brand-logo">
                <div class="brand-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <span>SpendSmart</span>
            </a>
            <nav class="landing-nav">
                <a href="#features">Features</a>
                <a href="#dashboard-preview">Preview</a>
                <a href="#money-notes">Money Notes</a>
                <a href="#card-spending">Cards</a>
                <a href="#how-it-works">How It Works</a>
            </nav>
            <div class="nav-actions">
                <button class="btn btn-outline" onclick="openAuthModal('login')">Sign In</button>
                <button class="btn btn-primary" onclick="openAuthModal('register')">Get Started</button>
            </div>
        </div>
    </header>

    <!-- Alert Notifications -->
    <?php if (!empty($error)): ?>
        <div class="landing-container" style="margin-top: 20px;">
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="landing-container hero-grid">
            
            <div class="hero-content">
                <div class="badge-pill">
                    <i class="fas fa-sparkles"></i> Your money, organized.
                </div>
                <h1 class="hero-title">
                    Track your money.<br>
                    <span class="gradient-text">Control your spending.</span>
                </h1>
                <p class="hero-description">
                    A simple and powerful expense manager to track your income, expenses, budgets, savings and everyday spending — all in one place.
                </p>
                
                <div class="hero-cta-group">
                    <button class="btn btn-primary btn-lg" onclick="openAuthModal('register')">
                        <span>Start Tracking</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    <a href="#features" class="btn btn-glass btn-lg">
                        <i class="fas fa-compass"></i> Explore Features
                    </a>
                </div>

                <div class="hero-subtext">
                    <span><i class="fas fa-check-circle"></i> Track expenses</span>
                    <span><i class="fas fa-check-circle"></i> Manage budgets</span>
                    <span><i class="fas fa-check-circle"></i> Monitor savings</span>
                </div>
            </div>

            <!-- Right 3D Visual Stage -->
            <div class="hero-visual">
                <div class="visual-stage">
                    
                    <!-- Floating Pill Transactions -->
                    <div class="float-item tx-pill tx-1">
                        <div class="tx-icon green"><i class="fas fa-arrow-down"></i></div>
                        <div class="tx-info">
                            <span class="tx-label">Salary</span>
                            <span class="tx-amt green">+ ₹45,000</span>
                        </div>
                    </div>

                    <div class="float-item tx-pill tx-2">
                        <div class="tx-icon red"><i class="fas fa-utensils"></i></div>
                        <div class="tx-info">
                            <span class="tx-label">Food & Dining</span>
                            <span class="tx-amt red">- ₹450</span>
                        </div>
                    </div>

                    <div class="float-item tx-pill tx-3">
                        <div class="tx-icon orange"><i class="fas fa-shopping-bag"></i></div>
                        <div class="tx-info">
                            <span class="tx-label">Shopping</span>
                            <span class="tx-amt red">- ₹1,299</span>
                        </div>
                    </div>

                    <div class="float-item tx-pill tx-4">
                        <div class="tx-icon blue"><i class="fas fa-file-invoice"></i></div>
                        <div class="tx-info">
                            <span class="tx-label">Electricity Bill</span>
                            <span class="tx-amt red">- ₹1,240</span>
                        </div>
                    </div>

                    <!-- 3D Animated Credit Card -->
                    <div class="digital-credit-card main-card-3d">
                        <div class="card-glass-shine"></div>
                        <div class="card-top-row">
                            <div class="card-brand">
                                <i class="fas fa-wallet"></i> SpendSmart
                            </div>
                            <div class="card-network">
                                <span>VISA</span>
                            </div>
                        </div>
                        <div class="card-chip-container">
                            <div class="card-chip"></div>
                            <i class="fas fa-wifi contactless-icon"></i>
                        </div>
                        <div class="card-number-row">
                            <span>••••</span>
                            <span>••••</span>
                            <span>••••</span>
                            <span>4821</span>
                        </div>
                        <div class="card-bottom-row">
                            <div class="card-holder">
                                <label>CARD HOLDER</label>
                                <strong>KUNAL JAWADE</strong>
                            </div>
                            <div class="card-expiry">
                                <label>EXPIRES</label>
                                <strong>09/29</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Money Notes Card -->
                    <div class="money-notes-card">
                        <div class="notes-header">
                            <div class="notes-title">
                                <i class="fas fa-sticky-note"></i>
                                <span>Money Notes</span>
                            </div>
                            <button class="mini-add-btn" title="Add reminder"><i class="fas fa-plus"></i></button>
                        </div>
                        <div class="notes-list">
                            <div class="note-item done">
                                <i class="fas fa-check-circle note-check"></i>
                                <div class="note-detail">
                                    <span class="note-name">Pay electricity bill</span>
                                    <span class="note-val">₹1,240</span>
                                </div>
                            </div>
                            <div class="note-item done">
                                <i class="fas fa-check-circle note-check"></i>
                                <div class="note-detail">
                                    <span class="note-name">Save this month</span>
                                    <span class="note-val green">₹5,000</span>
                                </div>
                            </div>
                            <div class="note-item pending">
                                <i class="far fa-circle note-check"></i>
                                <div class="note-detail">
                                    <span class="note-name">Credit card payment</span>
                                    <span class="note-val orange">10 September</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section-padding">
        <div class="landing-container">
            <div class="section-heading text-center">
                <span class="section-subtitle">Features</span>
                <h2 class="section-title">Everything you need to manage your money</h2>
                <p class="section-desc">Powerful fintech tools crafted into an intuitive, frictionless dark interface.</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon-wrap icon-red"><i class="fas fa-receipt"></i></div>
                    <h3>1. Expense Tracking</h3>
                    <p>Record every expense in seconds and know exactly where your money goes with visual categories.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap icon-green"><i class="fas fa-hand-holding-usd"></i></div>
                    <h3>2. Income Tracking</h3>
                    <p>Track your salary, freelance earnings, investments, and side hustles in one transparent ledger.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap icon-purple"><i class="fas fa-sliders-h"></i></div>
                    <h3>3. Budget Management</h3>
                    <p>Set monthly limits by category. Get smart notifications before you exceed your safe limits.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap icon-blue"><i class="fas fa-credit-card"></i></div>
                    <h3>4. Credit & Card Payments</h3>
                    <p>Keep card-based transactions, statement dates, and recurring subscriptions organized neatly.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap icon-teal"><i class="fas fa-piggy-bank"></i></div>
                    <h3>5. Savings Goals</h3>
                    <p>Set targets for vacations, emergency funds, or gadgets, and celebrate your savings milestones.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap icon-indigo"><i class="fas fa-chart-pie"></i></div>
                    <h3>6. Reports & Analytics</h3>
                    <p>Understand your spending patterns with interactive visual breakdowns and downloadable CSV summaries.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Preview -->
    <section id="dashboard-preview" class="section-padding">
        <div class="landing-container">
            <div class="section-heading text-center">
                <span class="section-subtitle">Live Interface Preview</span>
                <h2 class="section-title">Your complete financial picture at a glance</h2>
                <p class="section-desc">Real-time balances, income flow, and organized transactions unified in one UI.</p>
            </div>

            <div class="preview-mockup-window">
                <div class="mockup-top-bar">
                    <div class="mockup-dots"><span></span><span></span><span></span></div>
                    <div class="mockup-address"><i class="fas fa-lock"></i> app.spendsmart.io/dashboard</div>
                </div>

                <div class="mockup-body">
                    <div class="preview-metrics-grid">
                        <div class="preview-stat-card">
                            <span class="stat-title">Total Balance</span>
                            <div class="stat-value">₹52,450</div>
                            <span class="stat-badge positive"><i class="fas fa-arrow-up"></i> +12.4% this month</span>
                        </div>
                        <div class="preview-stat-card">
                            <span class="stat-title">Income</span>
                            <div class="stat-value text-green">+ ₹75,000</div>
                            <span class="stat-sub">3 sources active</span>
                        </div>
                        <div class="preview-stat-card">
                            <span class="stat-title">Expenses</span>
                            <div class="stat-value text-red">- ₹22,550</div>
                            <span class="stat-sub">42% of income</span>
                        </div>
                        <div class="preview-stat-card">
                            <span class="stat-title">Savings</span>
                            <div class="stat-value text-purple">₹15,000</div>
                            <span class="stat-sub">Goal: ₹20,000</span>
                        </div>
                        <div class="preview-stat-card">
                            <span class="stat-title">Credit Card</span>
                            <div class="stat-value text-orange">₹18,240</div>
                            <span class="stat-sub">Due in 8 days</span>
                        </div>
                    </div>

                    <div class="preview-dashboard-grid">
                        <div class="preview-tx-box">
                            <div class="box-header">
                                <h3>Recent Transactions</h3>
                                <span class="badge-mini">Live Preview</span>
                            </div>
                            <div class="preview-tx-list">
                                <div class="p-tx-item">
                                    <div class="p-tx-left">
                                        <div class="p-tx-icon green"><i class="fas fa-briefcase"></i></div>
                                        <div><strong>Monthly Salary</strong><small>Direct Deposit • 01 Sep</small></div>
                                    </div>
                                    <span class="p-tx-amount green">+ ₹45,000</span>
                                </div>
                                <div class="p-tx-item">
                                    <div class="p-tx-left">
                                        <div class="p-tx-icon red"><i class="fas fa-shopping-basket"></i></div>
                                        <div><strong>Groceries</strong><small>Supermarket • 02 Sep</small></div>
                                    </div>
                                    <span class="p-tx-amount red">- ₹3,500</span>
                                </div>
                                <div class="p-tx-item">
                                    <div class="p-tx-left">
                                        <div class="p-tx-icon orange"><i class="fas fa-taxi"></i></div>
                                        <div><strong>Uber</strong><small>Transport • 02 Sep</small></div>
                                    </div>
                                    <span class="p-tx-amount red">- ₹800</span>
                                </div>
                                <div class="p-tx-item">
                                    <div class="p-tx-left">
                                        <div class="p-tx-icon blue"><i class="fas fa-bolt"></i></div>
                                        <div><strong>Electricity Bill</strong><small>Utility • 02 Sep</small></div>
                                    </div>
                                    <span class="p-tx-amount red">- ₹1,200</span>
                                </div>
                                <div class="p-tx-item">
                                    <div class="p-tx-left">
                                        <div class="p-tx-icon teal"><i class="fas fa-laptop-code"></i></div>
                                        <div><strong>Freelance Project</strong><small>Client Payout • 03 Sep</small></div>
                                    </div>
                                    <span class="p-tx-amount green">+ ₹12,000</span>
                                </div>
                            </div>
                        </div>

                        <div class="preview-insight-box">
                            <div class="box-header">
                                <h3>Spending Breakdown</h3>
                                <span class="badge-mini">September</span>
                            </div>
                            <div class="chart-bars-wrap">
                                <div class="bar-group">
                                    <div class="bar-info"><span>Housing & Bills</span><span>₹10,500 (46%)</span></div>
                                    <div class="bar-track"><div class="bar-fill" style="width: 46%; background: #6366F1;"></div></div>
                                </div>
                                <div class="bar-group">
                                    <div class="bar-info"><span>Food & Groceries</span><span>₹6,200 (27%)</span></div>
                                    <div class="bar-track"><div class="bar-fill" style="width: 27%; background: #EC4899;"></div></div>
                                </div>
                                <div class="bar-group">
                                    <div class="bar-info"><span>Shopping</span><span>₹3,850 (17%)</span></div>
                                    <div class="bar-track"><div class="bar-fill" style="width: 17%; background: #F59E0B;"></div></div>
                                </div>
                                <div class="bar-group">
                                    <div class="bar-info"><span>Transport</span><span>₹2,000 (10%)</span></div>
                                    <div class="bar-track"><div class="bar-fill" style="width: 10%; background: #10B981;"></div></div>
                                </div>
                            </div>
                            <div class="insight-tip">
                                <i class="fas fa-lightbulb" style="color: #f59e0b;"></i>
                                <span>You are on track to save <strong>₹8,450</strong> more than last month.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Money Notes Section -->
    <section id="money-notes" class="section-padding">
        <div class="landing-container">
            <div class="section-heading text-center">
                <span class="section-subtitle">Smart Financial To-Dos</span>
                <h2 class="section-title">Never forget an important money task</h2>
                <p class="section-desc">Keep actionable notes, payment dates, and savings commitments alongside your expense tracker.</p>
            </div>

            <div class="money-notes-grid">
                <div class="reminder-card glow-indigo">
                    <div class="rem-top"><span class="rem-badge"><i class="fas fa-bolt"></i> Utilities</span><i class="far fa-bell"></i></div>
                    <h4>Pay electricity bill</h4>
                    <p class="rem-val">₹1,240 due in 4 days</p>
                    <div class="rem-status done"><i class="fas fa-check"></i> Scheduled on Auto-Pay</div>
                </div>
                <div class="reminder-card glow-purple">
                    <div class="rem-top"><span class="rem-badge"><i class="fas fa-credit-card"></i> Card Due</span><i class="far fa-calendar-alt"></i></div>
                    <h4>Credit card payment</h4>
                    <p class="rem-val">₹18,240 due on 10 Sept</p>
                    <div class="rem-status pending"><i class="far fa-clock"></i> Statement generated</div>
                </div>
                <div class="reminder-card glow-green">
                    <div class="rem-top"><span class="rem-badge"><i class="fas fa-piggy-bank"></i> Goal</span><i class="fas fa-bullseye"></i></div>
                    <h4>Save ₹5,000 this month</h4>
                    <p class="rem-val">₹3,500 saved of ₹5,000</p>
                    <div class="rem-status active"><i class="fas fa-spinner fa-spin"></i> 70% completed</div>
                </div>
                <div class="reminder-card glow-blue">
                    <div class="rem-top"><span class="rem-badge"><i class="fas fa-sync-alt"></i> Subscriptions</span><i class="fas fa-film"></i></div>
                    <h4>Review subscriptions</h4>
                    <p class="rem-val">Cancel unused OTT passes</p>
                    <div class="rem-status review"><i class="fas fa-info-circle"></i> Save ₹899/mo</div>
                </div>
                <div class="reminder-card glow-orange">
                    <div class="rem-top"><span class="rem-badge"><i class="fas fa-shield-alt"></i> Policy</span><i class="fas fa-car-crash"></i></div>
                    <h4>Renew insurance</h4>
                    <p class="rem-val">Term policy due in Oct</p>
                    <div class="rem-status pending"><i class="far fa-clock"></i> Set reminder</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Credit Card Section -->
    <section id="card-spending" class="section-padding">
        <div class="landing-container">
            <div class="section-heading text-center">
                <span class="section-subtitle">Card Management</span>
                <h2 class="section-title">Stay on top of your card spending</h2>
                <p class="section-desc">Monitor credit utilization and statement cycles without losing control of balances.</p>
            </div>

            <div class="card-deck-grid">
                <div class="deck-card-wrapper">
                    <div class="digital-credit-card mini-card variant-blue">
                        <div class="card-glass-shine"></div>
                        <div class="card-top-row"><span class="card-type">Personal Card</span><span class="card-network">VISA</span></div>
                        <div class="card-number-row"><span>••••</span><span>••••</span><span>••••</span><span>4821</span></div>
                        <div class="card-bottom-row"><span>KUNAL JAWADE</span><span>09/29</span></div>
                    </div>
                    <div class="card-metrics-box">
                        <div class="cm-row"><span>Limit Used</span><strong>₹18,240 / ₹50,000</strong></div>
                        <div class="cm-progress-track"><div class="cm-progress-bar" style="width: 36%;"></div></div>
                        <div class="cm-footer"><span class="badge-safe">36% Utilization</span><span class="due-text">Due: 10 Sep</span></div>
                    </div>
                </div>

                <div class="deck-card-wrapper">
                    <div class="digital-credit-card mini-card variant-purple">
                        <div class="card-glass-shine"></div>
                        <div class="card-top-row"><span class="card-type">Shopping Card</span><span class="card-network">Mastercard</span></div>
                        <div class="card-number-row"><span>••••</span><span>••••</span><span>••••</span><span>7392</span></div>
                        <div class="card-bottom-row"><span>KUNAL JAWADE</span><span>11/28</span></div>
                    </div>
                    <div class="card-metrics-box">
                        <div class="cm-row"><span>Limit Used</span><strong>₹7,850 / ₹30,000</strong></div>
                        <div class="cm-progress-track"><div class="cm-progress-bar bar-purple" style="width: 26%;"></div></div>
                        <div class="cm-footer"><span class="badge-safe">26% Utilization</span><span class="due-text">Due: 18 Sep</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="section-padding">
        <div class="landing-container">
            <div class="section-heading text-center">
                <span class="section-subtitle">Streamlined Process</span>
                <h2 class="section-title">How SpendSmart works</h2>
                <p class="section-desc">Three simple steps to take absolute control of your personal finances.</p>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number-pill">01</div>
                    <div class="step-icon"><i class="fas fa-wallet"></i></div>
                    <h3>Add your income</h3>
                    <p>Enter your paychecks, freelance milestones, or investment dividends to establish your monthly baseline.</p>
                </div>
                <div class="step-card">
                    <div class="step-number-pill">02</div>
                    <div class="step-icon"><i class="fas fa-receipt"></i></div>
                    <h3>Track your expenses</h3>
                    <p>Log transactions instantly on mobile or desktop with custom categories, dates, and spending notes.</p>
                </div>
                <div class="step-card">
                    <div class="step-number-pill">03</div>
                    <div class="step-icon"><i class="fas fa-chart-line"></i></div>
                    <h3>Understand your spending</h3>
                    <p>Analyze intuitive visual charts, avoid overspending with smart alerts, and build lifelong savings.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section-padding">
        <div class="landing-container">
            <div class="cta-banner-glass">
                <h2 class="cta-title">Take control of your money.</h2>
                <p class="cta-desc">Track every rupee, build better habits, and reach your financial goals with SpendSmart.</p>
                <button class="btn btn-primary btn-xl" onclick="openAuthModal('register')">
                    <span>Start Tracking for Free</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <div class="landing-container footer-flex">
            <div class="footer-brand">
                <a href="index.php" class="brand-logo">
                    <div class="brand-icon"><i class="fas fa-wallet"></i></div>
                    <span>SpendSmart</span>
                </a>
                <p>Modern expense tracking & financial notes management platform.</p>
            </div>
            <div class="footer-links">
                <a href="#features">Features</a>
                <a href="#dashboard-preview">Live Preview</a>
                <a href="#money-notes">Money Notes</a>
                <a href="#card-spending">Cards</a>
                <a href="javascript:void(0)" onclick="openAuthModal('login')">Sign In</a>
            </div>
            <div class="footer-copy">
                &copy; <?php echo date('Y'); ?> SpendSmart. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Auth Modal -->
    <div id="authModal" class="modal-overlay">
        <div class="auth-modal-card">
            <button class="modal-close-btn" onclick="closeAuthModal()">&times;</button>
            
            <div class="auth-tabs">
                <button class="auth-tab-btn active" id="tabLoginBtn" onclick="switchAuthTab('login')">Sign In</button>
                <button class="auth-tab-btn" id="tabRegisterBtn" onclick="switchAuthTab('register')">Create Account</button>
            </div>

            <!-- Login Form -->
            <form method="POST" action="index.php" id="loginForm">
                <input type="hidden" name="login" value="1">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>

            <!-- Register Form -->
            <form method="POST" action="index.php" id="registerForm" style="display: none;">
                <input type="hidden" name="register" value="1">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Kunal Jawade" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password (min 6 characters)</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </form>
        </div>
    </div>

    <script>
        // Modal Controls
        function openAuthModal(tab) {
            document.getElementById('authModal').classList.add('active');
            switchAuthTab(tab);
        }
        function closeAuthModal() {
            document.getElementById('authModal').classList.remove('active');
        }
        function switchAuthTab(tab) {
            const loginForm = document.getElementById('loginForm');
            const regForm = document.getElementById('registerForm');
            const tabLogin = document.getElementById('tabLoginBtn');
            const tabReg = document.getElementById('tabRegisterBtn');

            if (tab === 'login') {
                loginForm.style.display = 'block';
                regForm.style.display = 'none';
                tabLogin.classList.add('active');
                tabReg.classList.remove('active');
            } else {
                loginForm.style.display = 'none';
                regForm.style.display = 'block';
                tabReg.classList.add('active');
                tabLogin.classList.remove('active');
            }
        }
        window.onclick = function(e) {
            const modal = document.getElementById('authModal');
            if (e.target === modal) closeAuthModal();
        };

        // Money Notes Demo Trigger
        document.querySelector('.mini-add-btn')?.addEventListener('click', () => {
            const noteText = prompt("Enter a quick financial reminder (e.g. Pay Wifi Bill ₹799):");
            if (noteText && noteText.trim()) {
                const list = document.querySelector('.notes-list');
                const div = document.createElement('div');
                div.className = 'note-item pending';
                div.innerHTML = `
                    <i class="far fa-circle note-check"></i>
                    <div class="note-detail">
                        <span class="note-name">${noteText.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</span>
                        <span class="note-val orange">Pending</span>
                    </div>
                `;
                list.prepend(div);
            }
        });
    </script>
</body>
</html>