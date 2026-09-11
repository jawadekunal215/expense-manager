<?php
require_once '../includes/config.php';
requireLogin();
$pageTitle = 'Budget';
$activePage = 'budget';
$uid = $_SESSION['user_id'];
$month = date('m'); $year = date('Y');

// Handle Add Budget
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_budget'])) {
    $title = trim($_POST['title']);
    $amount = floatval($_POST['amount']);
    $cat_id = intval($_POST['category_id']);
    $m = intval($_POST['month']);
    $y = intval($_POST['year']);
    if ($title && $amount > 0) {
        // Get current spending
        $spent = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND category_id=$cat_id AND type='expense' AND MONTH(date)=$m AND YEAR(date)=$y")->fetch_assoc()['t'];
        $stmt = $conn->prepare("INSERT INTO budgets (user_id,category_id,title,amount,spent,month,year) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("iisddii", $uid, $cat_id, $title, $amount, $spent, $m, $y);
        $stmt->execute();
        header('Location: budget.php?success=Budget+added+successfully');
        exit;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM budgets WHERE id=$id AND user_id=$uid");
    header('Location: budget.php?success=Budget+deleted');
    exit;
}

// Get budgets with current spending
$budgets = $conn->query("SELECT b.*, c.name as cat_name, c.icon, c.color, 
    (SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id=$uid AND category_id=b.category_id AND type='expense' AND MONTH(date)=b.month AND YEAR(date)=b.year) as actual_spent
    FROM budgets b LEFT JOIN categories c ON b.category_id=c.id 
    WHERE b.user_id=$uid ORDER BY b.year DESC, b.month DESC");

// Categories for dropdown
$categories = $conn->query("SELECT * FROM categories WHERE user_id=$uid AND type='expense' ORDER BY name");

require_once '../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-sliders-h" style="color:#6366f1;font-size:22px"></i> Budget Management</h1>
        <p>Set spending limits and track your budgets</p>
    </div>
    <div class="page-header-right">
        <button class="btn btn-primary" onclick="openModal('budgetModal')">
            <i class="fas fa-plus"></i> Create Budget
        </button>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?></div>
<?php endif; ?>

<!-- Tips -->
<div class="tips-card">
    <h4><i class="fas fa-lightbulb"></i> &nbsp;Budget Tip</h4>
    <p>Use the <strong>50/30/20 rule</strong> — allocate 50% of income to needs, 30% to wants, and 20% to savings. Set category-wise budgets to stay disciplined!</p>
</div>

<!-- Budget Cards -->
<?php if ($budgets->num_rows > 0): while ($b = $budgets->fetch_assoc()):
    $pct = $b['amount'] > 0 ? min(100, ($b['actual_spent']/$b['amount'])*100) : 0;
    $remaining = max(0, $b['amount'] - $b['actual_spent']);
    $cls = $pct >= 90 ? 'danger' : ($pct >= 70 ? 'warning' : 'success');
    $statusText = $pct >= 100 ? '🚨 Exceeded!' : ($pct >= 90 ? '⚠️ Critical' : ($pct >= 70 ? '⚡ High' : '✅ Good'));
?>
<div class="budget-card">
    <div class="budget-card-top">
        <div style="display:flex;align-items:center;gap:12px">
            <div class="budget-icon" style="background:<?= $b['color'] ?? '#6366f1' ?>22">
                <i class="fas <?= $b['icon'] ?? 'fa-tag' ?>" style="color:<?= $b['color'] ?? '#6366f1' ?>;font-size:18px"></i>
            </div>
            <div>
                <div class="budget-name"><?= htmlspecialchars($b['cat_name'] ?? $b['title']) ?></div>
                <div class="budget-meta">
                    <?= date('F', mktime(0,0,0,$b['month'],1)) ?> <?= $b['year'] ?> &bull;
                    <span style="color:<?= $pct>=90?'#ef4444':($pct>=70?'#f59e0b':'#10b981') ?>;font-weight:600"><?= $statusText ?></span>
                </div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            <div class="budget-amount">
                <div class="budget-spent"><?= formatCurrency($b['actual_spent']) ?></div>
                <div class="budget-total">of <?= formatCurrency($b['amount']) ?></div>
            </div>
            <button onclick="confirmDelete('budget.php?delete=<?= $b['id'] ?>', '<?= addslashes($b['cat_name'] ?? $b['title']) ?> budget')" class="btn btn-danger btn-icon btn-sm">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
    <div class="progress-bar-wrap">
        <div class="progress-bar <?= $cls ?>" style="width:<?= $pct ?>%"></div>
    </div>
    <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:12px;color:#94a3b8">
        <span><?= number_format($pct, 1) ?>% used</span>
        <span><?= $pct < 100 ? '₹'.number_format($remaining,2).' remaining' : '<span style="color:#ef4444">Over by ₹'.number_format($b['actual_spent']-$b['amount'],2).'</span>' ?></span>
    </div>
</div>
<?php endwhile; else: ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-sliders-h"></i>
        <h4>No budgets created yet</h4>
        <p>Create your first budget to start tracking spending limits</p>
        <button class="btn btn-primary" style="margin-top:16px" onclick="openModal('budgetModal')">
            <i class="fas fa-plus"></i> Create Budget
        </button>
    </div>
</div>
<?php endif; ?>

<!-- Budget Summary Chart -->
<?php
$budgets2 = $conn->query("SELECT b.*, c.name as cat_name, c.color,
    (SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id=$uid AND category_id=b.category_id AND type='expense' AND MONTH(date)=b.month AND YEAR(date)=b.year) as actual_spent
    FROM budgets b LEFT JOIN categories c ON b.category_id=c.id WHERE b.user_id=$uid AND b.month=$month AND b.year=$year");
$bNames=[]; $bLimits=[]; $bSpent=[]; $bColors=[];
while ($brow = $budgets2->fetch_assoc()) {
    $bNames[] = $brow['cat_name'] ?? $brow['title'];
    $bLimits[] = floatval($brow['amount']);
    $bSpent[] = floatval($brow['actual_spent']);
    $bColors[] = $brow['color'] ?? '#6366f1';
}
?>
<?php if (count($bNames) > 0): ?>
<div class="card" style="margin-top:24px">
    <div class="card-header">
        <h3><i class="fas fa-chart-bar"></i> Budget vs Actual (<?= date('F Y') ?>)</h3>
    </div>
    <div class="card-body">
        <div class="chart-wrap">
            <canvas id="budgetChart"></canvas>
        </div>
    </div>
</div>
<script>
const bCtx = document.getElementById('budgetChart').getContext('2d');
new Chart(bCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($bNames) ?>,
        datasets: [
            {
                label: 'Budget Limit',
                data: <?= json_encode($bLimits) ?>,
                backgroundColor: 'rgba(99,102,241,0.2)',
                borderColor: '#6366f1',
                borderWidth: 2, borderRadius: 6,
            },
            {
                label: 'Actual Spent',
                data: <?= json_encode($bSpent) ?>,
                backgroundColor: 'rgba(239,68,68,0.2)',
                borderColor: '#ef4444',
                borderWidth: 2, borderRadius: 6,
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => '₹'+v.toLocaleString('en-IN') } },
            x: { grid: { display: false } }
        }
    }
});
</script>
<?php endif; ?>

<!-- Add Budget Modal -->
<div class="modal-overlay" id="budgetModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Create Budget</h3>
            <button class="modal-close" onclick="closeModal('budgetModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="add_budget" value="1">
            <div class="form-group">
                <label>Budget Title</label>
                <input type="text" name="title" placeholder="e.g. Food Budget" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id">
                        <option value="">-- Category --</option>
                        <?php while($c=$categories->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Limit Amount (₹)</label>
                    <input type="number" name="amount" placeholder="5000" step="100" min="1" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Month</label>
                    <select name="month">
                        <?php for ($m=1;$m<=12;$m++): ?>
                        <option value="<?=$m?>" <?=date('m')==$m?'selected':''?>><?=date('F',mktime(0,0,0,$m,1))?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <select name="year">
                        <?php for ($y=date('Y');$y<=date('Y')+2;$y++): ?>
                        <option value="<?=$y?>"><?=$y?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('budgetModal')">Cancel</button>
                <button type="submit" class="btn-save"><i class="fas fa-check"></i> Create Budget</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
