<?php
require_once '../includes/config.php';
requireLogin();
$pageTitle = 'Savings Goals';
$activePage = 'savings';
$uid = $_SESSION['user_id'];

// Handle Add Goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_goal'])) {
    $title = trim($_POST['title']);
    $target = floatval($_POST['target_amount']);
    $saved = floatval($_POST['saved_amount'] ?? 0);
    $deadline = $_POST['deadline'] ?: null;
    $icon = $_POST['icon'] ?? 'fa-piggy-bank';
    $color = $_POST['color'] ?? '#10b981';
    if ($title && $target > 0) {
        $stmt = $conn->prepare("INSERT INTO savings_goals (user_id,title,target_amount,saved_amount,deadline,icon,color) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("isddsss", $uid, $title, $target, $saved, $deadline, $icon, $color);
        $stmt->execute();
        header('Location: savings.php?success=Goal+added!');
        exit;
    }
}

// Handle Add Money to Goal
if (isset($_POST['add_to_goal'])) {
    $gid = intval($_POST['goal_id']);
    $amount = floatval($_POST['add_amount']);
    if ($amount > 0) {
        $conn->query("UPDATE savings_goals SET saved_amount = saved_amount + $amount WHERE id=$gid AND user_id=$uid");
        $conn->query("UPDATE savings_goals SET status='achieved' WHERE id=$gid AND user_id=$uid AND saved_amount >= target_amount");
        header('Location: savings.php?success=Amount+added!');
        exit;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM savings_goals WHERE id=$id AND user_id=$uid");
    header('Location: savings.php?success=Goal+deleted');
    exit;
}

$goals = $conn->query("SELECT * FROM savings_goals WHERE user_id=$uid ORDER BY status ASC, created_at DESC");
$totGoals = $conn->query("SELECT COUNT(*) as c FROM savings_goals WHERE user_id=$uid AND status='active'")->fetch_assoc()['c'];
$totTarget = $conn->query("SELECT COALESCE(SUM(target_amount),0) as t FROM savings_goals WHERE user_id=$uid AND status='active'")->fetch_assoc()['t'];
$totSaved = $conn->query("SELECT COALESCE(SUM(saved_amount),0) as t FROM savings_goals WHERE user_id=$uid AND status='active'")->fetch_assoc()['t'];

require_once '../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-piggy-bank" style="color:#6366f1;font-size:22px"></i> Savings Goals</h1>
        <p>Plan and track your financial milestones</p>
    </div>
    <div class="page-header-right">
        <button class="btn btn-primary" onclick="openModal('goalModal')">
            <i class="fas fa-plus"></i> New Goal
        </button>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?></div>
<?php endif; ?>

<!-- Summary Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:28px">
    <div class="stat-card primary">
        <div class="stat-top"><div class="stat-icon primary"><i class="fas fa-bullseye"></i></div></div>
        <div class="stat-value"><?= $totGoals ?></div>
        <div class="stat-label">Active Goals</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-top"><div class="stat-icon warning"><i class="fas fa-flag"></i></div></div>
        <div class="stat-value"><?= formatCurrency($totTarget) ?></div>
        <div class="stat-label">Total Target</div>
    </div>
    <div class="stat-card success">
        <div class="stat-top"><div class="stat-icon success"><i class="fas fa-check"></i></div></div>
        <div class="stat-value"><?= formatCurrency($totSaved) ?></div>
        <div class="stat-label">Total Saved</div>
    </div>
</div>

<!-- Goals Grid -->
<div class="grid-3">
<?php if ($goals->num_rows > 0): while ($g = $goals->fetch_assoc()):
    $pct = $g['target_amount'] > 0 ? min(100, ($g['saved_amount']/$g['target_amount'])*100) : 0;
    $remaining = max(0, $g['target_amount'] - $g['saved_amount']);
    $daysLeft = $g['deadline'] ? ceil((strtotime($g['deadline']) - time()) / 86400) : null;
    $achieved = $g['status'] === 'achieved';
?>
<div class="savings-card" style="<?= $achieved ? 'opacity:0.7;' : '' ?>">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
        <div class="savings-icon" style="background:<?= $g['color'] ?>22">
            <i class="fas <?= $g['icon'] ?>" style="color:<?= $g['color'] ?>"></i>
        </div>
        <div style="display:flex;gap:6px">
            <?php if (!$achieved): ?>
            <button onclick="openAddMoney(<?= $g['id'] ?>, '<?= addslashes($g['title']) ?>')" class="btn btn-success btn-icon btn-sm" title="Add Money">
                <i class="fas fa-plus"></i>
            </button>
            <?php endif; ?>
            <button onclick="confirmDelete('savings.php?delete=<?= $g['id'] ?>', '<?= addslashes($g['title']) ?>')" class="btn btn-danger btn-icon btn-sm">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>

    <?php if ($achieved): ?>
    <div style="background:rgba(16,185,129,0.1);color:#10b981;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;display:inline-block;margin-bottom:8px">
        🎉 ACHIEVED!
    </div>
    <?php endif; ?>

    <div class="savings-title"><?= htmlspecialchars($g['title']) ?></div>
    <div class="savings-meta">
        <?php if ($g['deadline']): ?>
        <i class="fas fa-calendar-alt"></i> Deadline: <?= date('d M Y', strtotime($g['deadline'])) ?>
        <?php if ($daysLeft !== null): ?>
        &bull; <span style="color:<?= $daysLeft < 30 ? '#ef4444' : '#10b981' ?>;font-weight:600"><?= $daysLeft > 0 ? "$daysLeft days left" : "Overdue!" ?></span>
        <?php endif; ?>
        <?php else: ?>
        <i class="fas fa-infinity"></i> No deadline set
        <?php endif; ?>
    </div>

    <div class="progress-bar-wrap">
        <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $g['color'] ?>"></div>
    </div>

    <div class="savings-amounts">
        <div>
            <div class="savings-saved" style="color:<?= $g['color'] ?>"><?= formatCurrency($g['saved_amount']) ?></div>
            <div class="savings-target">of <?= formatCurrency($g['target_amount']) ?></div>
        </div>
        <div style="text-align:right">
            <div style="font-size:24px;font-weight:800;color:#0f172a"><?= number_format($pct, 0) ?>%</div>
            <?php if (!$achieved): ?>
            <div style="font-size:11px;color:#94a3b8">₹<?= number_format($remaining, 0) ?> to go</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endwhile; else: ?>
<div class="card" style="grid-column:1/-1">
    <div class="empty-state">
        <i class="fas fa-piggy-bank"></i>
        <h4>No savings goals yet</h4>
        <p>Start planning your financial future today!</p>
        <button class="btn btn-primary" style="margin-top:16px" onclick="openModal('goalModal')">
            <i class="fas fa-plus"></i> Create First Goal
        </button>
    </div>
</div>
<?php endif; ?>
</div>

<!-- Add Goal Modal -->
<div class="modal-overlay" id="goalModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> New Savings Goal</h3>
            <button class="modal-close" onclick="closeModal('goalModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="add_goal" value="1">
            <div class="form-group">
                <label>Goal Title</label>
                <input type="text" name="title" placeholder="e.g. Emergency Fund" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Target Amount (₹)</label>
                    <input type="number" name="target_amount" placeholder="50000" step="100" required>
                </div>
                <div class="form-group">
                    <label>Already Saved (₹)</label>
                    <input type="number" name="saved_amount" placeholder="0" step="100" value="0">
                </div>
            </div>
            <div class="form-group">
                <label>Deadline (optional)</label>
                <input type="date" name="deadline" min="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Icon</label>
                    <select name="icon">
                        <option value="fa-piggy-bank">🐷 Piggy Bank</option>
                        <option value="fa-home">🏠 Home</option>
                        <option value="fa-car">🚗 Car</option>
                        <option value="fa-plane">✈️ Travel</option>
                        <option value="fa-laptop">💻 Laptop</option>
                        <option value="fa-graduation-cap">🎓 Education</option>
                        <option value="fa-ring">💍 Wedding</option>
                        <option value="fa-shield-alt">🛡️ Emergency</option>
                        <option value="fa-briefcase">💼 Business</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Color</label>
                    <input type="color" name="color" value="#10b981" style="height:44px">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('goalModal')">Cancel</button>
                <button type="submit" class="btn-save"><i class="fas fa-check"></i> Create Goal</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Money Modal -->
<div class="modal-overlay" id="addMoneyModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add Money to Goal</h3>
            <button class="modal-close" onclick="closeModal('addMoneyModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="add_to_goal" value="1">
            <input type="hidden" name="goal_id" id="addMoneyGoalId">
            <div class="form-group">
                <label>Goal</label>
                <input type="text" id="addMoneyGoalName" readonly style="background:#f8fafc">
            </div>
            <div class="form-group">
                <label>Amount to Add (₹)</label>
                <input type="number" name="add_amount" placeholder="1000" step="100" min="1" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('addMoneyModal')">Cancel</button>
                <button type="submit" class="btn-save"><i class="fas fa-plus"></i> Add Money</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddMoney(id, name) {
    document.getElementById('addMoneyGoalId').value = id;
    document.getElementById('addMoneyGoalName').value = name;
    openModal('addMoneyModal');
}
</script>

<?php require_once '../includes/footer.php'; ?>
