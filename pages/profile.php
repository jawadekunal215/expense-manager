<?php
require_once '../includes/config.php';
requireLogin();
$pageTitle = 'Profile';
$activePage = 'profile';
$uid = $_SESSION['user_id'];
$user = getUser($conn);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $budget = floatval($_POST['monthly_budget']);
        $currency = trim($_POST['currency']);
        
        if ($name && $email) {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, monthly_budget=?, currency=? WHERE id=?");
            $stmt->bind_param("ssdsi", $name, $email, $budget, $currency, $uid);
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $name;
                $success = 'Profile updated successfully!';
                $user = getUser($conn);
            } else {
                $error = 'Update failed.';
            }
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        if (password_verify($current, $user['password'])) {
            if ($new === $confirm && strlen($new) >= 6) {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                $stmt->bind_param("si", $hashed, $uid);
                $stmt->execute();
                $success = 'Password changed successfully!';
            } else {
                $error = 'New passwords do not match or are too short.';
            }
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}

// Stats
$totalInc = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='income'")->fetch_assoc()['t'];
$totalExp = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='expense'")->fetch_assoc()['t'];
$totalTxn = $conn->query("SELECT COUNT(*) as c FROM transactions WHERE user_id=$uid")->fetch_assoc()['c'];

require_once '../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-user-cog" style="color:#6366f1;font-size:22px"></i> My Profile</h1>
        <p>Manage your account settings and preferences</p>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="grid-2">
    <!-- Profile Card -->
    <div>
        <div class="profile-card">
            <div class="profile-cover">
                <div class="profile-avatar-wrap">
                    <div class="profile-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
                </div>
            </div>
            <div class="profile-info">
                <div class="profile-name"><?= htmlspecialchars($user['full_name']) ?></div>
                <div class="profile-email">
                    <i class="fas fa-envelope" style="color:#6366f1;margin-right:6px"></i>
                    <?= htmlspecialchars($user['email']) ?>
                </div>
                <div style="margin-top:8px;font-size:13px;color:#94a3b8">
                    <i class="fas fa-at" style="margin-right:6px"></i>@<?= htmlspecialchars($user['username']) ?>
                    &bull;
                    <i class="fas fa-calendar-alt" style="margin-left:8px;margin-right:6px"></i>
                    Joined <?= date('M Y', strtotime($user['created_at'])) ?>
                </div>
            </div>
            <div class="profile-stats">
                <div class="report-stat">
                    <div class="report-stat-value" style="color:#10b981"><?= formatCurrency($totalInc) ?></div>
                    <div class="report-stat-label">Total Income</div>
                </div>
                <div class="report-stat">
                    <div class="report-stat-value" style="color:#ef4444"><?= formatCurrency($totalExp) ?></div>
                    <div class="report-stat-label">Total Spent</div>
                </div>
                <div class="report-stat">
                    <div class="report-stat-value" style="color:#6366f1"><?= $totalTxn ?></div>
                    <div class="report-stat-label">Transactions</div>
                </div>
            </div>
        </div>

        <!-- Budget Info -->
        <div class="card" style="margin-top:20px">
            <div class="card-header"><h3><i class="fas fa-wallet"></i> Financial Settings</h3></div>
            <div class="card-body">
                <div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid #e2e8f0">
                    <span style="font-size:14px;color:#64748b">Monthly Budget</span>
                    <span style="font-weight:700;color:#0f172a"><?= formatCurrency($user['monthly_budget']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid #e2e8f0">
                    <span style="font-size:14px;color:#64748b">Currency</span>
                    <span style="font-weight:700"><?= htmlspecialchars($user['currency']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:12px 0">
                    <span style="font-size:14px;color:#64748b">Net Worth</span>
                    <span style="font-weight:700;color:<?= ($totalInc-$totalExp)>=0?'#10b981':'#ef4444' ?>"><?= formatCurrency($totalInc - $totalExp) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Forms -->
    <div>
        <!-- Update Profile Form -->
        <div class="form-card" style="margin-bottom:20px">
            <h3 style="font-size:18px;font-weight:700;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid #e2e8f0">
                <i class="fas fa-edit" style="color:#6366f1;margin-right:8px"></i>Edit Profile
            </h3>
            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Monthly Budget (₹)</label>
                        <input type="number" name="monthly_budget" value="<?= $user['monthly_budget'] ?>" step="100">
                    </div>
                    <div class="form-group">
                        <label>Currency</label>
                        <select name="currency">
                            <option value="INR" <?= $user['currency']==='INR'?'selected':'' ?>>₹ INR</option>
                            <option value="USD" <?= $user['currency']==='USD'?'selected':'' ?>>$ USD</option>
                            <option value="EUR" <?= $user['currency']==='EUR'?'selected':'' ?>>€ EUR</option>
                            <option value="GBP" <?= $user['currency']==='GBP'?'selected':'' ?>>£ GBP</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="form-card">
            <h3 style="font-size:18px;font-weight:700;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid #e2e8f0">
                <i class="fas fa-lock" style="color:#6366f1;margin-right:8px"></i>Change Password
            </h3>
            <form method="POST">
                <input type="hidden" name="change_password" value="1">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" placeholder="Enter current password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Min 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Repeat new password" required>
                </div>
                <div class="alert alert-info" style="margin-bottom:16px">
                    <i class="fas fa-info-circle"></i>
                    Demo account password: <strong>password</strong>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
