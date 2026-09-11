<?php requireLogin(); $user = getUser($conn); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'SpendSmart' ?> | SpendSmart Expense Manager</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Main CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-wallet"></i></div>
        <div class="brand-text">
            <span class="brand-name">SpendSmart</span>
            <span class="brand-tagline">Expense Manager</span>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar-sm">
            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
        </div>
        <div class="user-info-sm">
            <span class="user-name-sm"><?= htmlspecialchars($user['full_name']) ?></span>
            <span class="user-role">Personal Account</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">MAIN MENU</div>
        <ul>
            <li class="<?= ($activePage ?? '') == 'dashboard' ? 'active' : '' ?>">
                <a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
            </li>
            <li class="<?= ($activePage ?? '') == 'transactions' ? 'active' : '' ?>">
                <a href="transactions.php"><i class="fas fa-exchange-alt"></i> <span>Transactions</span></a>
            </li>
            <li class="<?= ($activePage ?? '') == 'income' ? 'active' : '' ?>">
                <a href="income.php"><i class="fas fa-arrow-up"></i> <span>Income</span></a>
            </li>
            <li class="<?= ($activePage ?? '') == 'expenses' ? 'active' : '' ?>">
                <a href="expenses.php"><i class="fas fa-arrow-down"></i> <span>Expenses</span></a>
            </li>
        </ul>
        <div class="nav-section-label">PLANNING</div>
        <ul>
            <li class="<?= ($activePage ?? '') == 'budget' ? 'active' : '' ?>">
                <a href="budget.php"><i class="fas fa-sliders-h"></i> <span>Budget</span></a>
            </li>
            <li class="<?= ($activePage ?? '') == 'savings' ? 'active' : '' ?>">
                <a href="savings.php"><i class="fas fa-piggy-bank"></i> <span>Savings Goals</span></a>
            </li>
            <li class="<?= ($activePage ?? '') == 'reports' ? 'active' : '' ?>">
                <a href="reports.php"><i class="fas fa-chart-pie"></i> <span>Reports</span></a>
            </li>
        </ul>
        <div class="nav-section-label">SETTINGS</div>
        <ul>
            <li class="<?= ($activePage ?? '') == 'categories' ? 'active' : '' ?>">
                <a href="categories.php"><i class="fas fa-tags"></i> <span>Categories</span></a>
            </li>
            <li class="<?= ($activePage ?? '') == 'profile' ? 'active' : '' ?>">
                <a href="profile.php"><i class="fas fa-user-cog"></i> <span>Profile</span></a>
            </li>
            <li>
                <a href="../auth/logout.php" class="nav-logout"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </li>
        </ul>
    </nav>
</div>

<!-- Main Content -->
<div class="main-wrapper" id="mainWrapper">
    <!-- Top Navbar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="toggle-btn" id="toggleSidebar"><i class="fas fa-bars"></i></button>
            <div class="page-breadcrumb">
                <span class="breadcrumb-icon"><i class="fas fa-home"></i></span>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current"><?= $pageTitle ?? 'Dashboard' ?></span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-date">
                <i class="far fa-calendar-alt"></i>
                <span><?= date('D, d M Y') ?></span>
            </div>
            <div class="notification-btn" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <span class="notif-badge">3</span>
                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-header">
                        <h4>Notifications</h4>
                        <span class="mark-all">Mark all read</span>
                    </div>
                    <div class="notif-item unread">
                        <div class="notif-icon warning"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="notif-text">
                            <p>Food budget is 70% used!</p>
                            <small>2 hours ago</small>
                        </div>
                    </div>
                    <div class="notif-item unread">
                        <div class="notif-icon success"><i class="fas fa-check-circle"></i></div>
                        <div class="notif-text">
                            <p>Salary of ₹45,000 received</p>
                            <small>1 day ago</small>
                        </div>
                    </div>
                    <div class="notif-item">
                        <div class="notif-icon info"><i class="fas fa-info-circle"></i></div>
                        <div class="notif-text">
                            <p>Monthly report is ready</p>
                            <small>3 days ago</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="topbar-avatar" onclick="window.location='profile.php'">
                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                <div class="avatar-status"></div>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="page-content">
