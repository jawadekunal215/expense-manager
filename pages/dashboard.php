<?php
require_once '../includes/config.php';
requireLogin();
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$uid = $_SESSION['user_id'];

// Totals
$month = date('m'); $year = date('Y');
$monthStart = "$year-$month-01";
$monthEnd = date('Y-m-t');

$totalIncome = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='income' AND date BETWEEN '$monthStart' AND '$monthEnd'")->fetch_assoc()['t'];
$totalExpense = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='expense' AND date BETWEEN '$monthStart' AND '$monthEnd'")->fetch_assoc()['t'];
$balance = $totalIncome - $totalExpense;
$user = getUser($conn);
$budget = $user['monthly_budget'];
$budgetUsedPct = $budget > 0 ? min(100, ($totalExpense/$budget)*100) : 0;

// All time balance
$allIncome = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='income'")->fetch_assoc()['t'];
$allExpense = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='expense'")->fetch_assoc()['t'];
$netBalance = $allIncome - $allExpense;

// Recent Transactions
$recent = $conn->query("SELECT t.*, c.name as cat_name, c.icon as cat_icon, c.color as cat_color FROM transactions t LEFT JOIN categories c ON t.category_id=c.id WHERE t.user_id=$uid ORDER BY t.date DESC, t.created_at DESC LIMIT 8");

// Category Expenses (for pie chart)
$catExpenses = $conn->query("SELECT c.name, c.color, SUM(t.amount) as total FROM transactions t JOIN categories c ON t.category_id=c.id WHERE t.user_id=$uid AND t.type='expense' AND t.date BETWEEN '$monthStart' AND '$monthEnd' GROUP BY c.id ORDER BY total DESC LIMIT 6");
$catLabels = []; $catAmounts = []; $catColors = [];
while ($row = $catExpenses->fetch_assoc()) {
    $catLabels[] = $row['name'];
    $catAmounts[] = floatval($row['total']);
    $catColors[] = $row['color'];
}

// Daily expenses for bar chart (last 7 days)
$dailyData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dLabel = date('D', strtotime($d));
    $amt = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='expense' AND date='$d'")->fetch_assoc()['t'];
    $dailyData[] = ['label' => $dLabel, 'amount' => floatval($amt)];
}

// Budgets
$budgets = $conn->query("SELECT b.*, c.name as cat_name, c.icon, c.color FROM budgets b LEFT JOIN categories c ON b.category_id=c.id WHERE b.user_id=$uid AND b.month=$month AND b.year=$year LIMIT 4");

// Savings Goals
$goals = $conn->query("SELECT * FROM savings_goals WHERE user_id=$uid AND status='active' LIMIT 3");

require_once '../includes/header.php';
?>

<!-- Balance Hero -->
<div class="balance-hero">
    <div class="balance-hero-content">
        <div class="balance-label">NET BALANCE (ALL TIME)</div>
        <div class="balance-amount"><?= formatCurrency($netBalance) ?></div>
        <div class="balance-stats">
            <div>
                <i class="fas fa-arrow-up" style="color:#34d399"></i>
                <span class="balance-stat-val"><?= formatCurrency($allIncome) ?></span>
                <span class="balance-stat-key">Total Income</span>
            </div>
            <div>
                <i class="fas fa-arrow-down" style="color:#f87171"></i>
                <span class="balance-stat-val"><?= formatCurrency($allExpense) ?></span>
                <span class="balance-stat-key">Total Spent</span>
            </div>
        </div>
    </div>
</div>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h1>Dashboard 👋</h1>
        <p>Hello, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>! Here's your financial overview for <?= date('F Y') ?>.</p>
    </div>
    <div class="page-header-right">
        <button class="btn btn-primary" onclick="openModal('addModal')">
            <i class="fas fa-plus"></i> Add Transaction
        </button>
        <a href="reports.php" class="btn btn-outline">
            <i class="fas fa-chart-pie"></i> Reports
        </a>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card success">
        <div class="stat-top">
            <div class="stat-icon success"><i class="fas fa-arrow-up"></i></div>
            <span class="stat-badge up"><i class="fas fa-arrow-up"></i> This Month</span>
        </div>
        <div class="stat-value"><?= formatCurrency($totalIncome) ?></div>
        <div class="stat-label">Monthly Income</div>
    </div>
    <div class="stat-card danger">
        <div class="stat-top">
            <div class="stat-icon danger"><i class="fas fa-arrow-down"></i></div>
            <span class="stat-badge down"><i class="fas fa-arrow-down"></i> This Month</span>
        </div>
        <div class="stat-value"><?= formatCurrency($totalExpense) ?></div>
        <div class="stat-label">Monthly Expenses</div>
    </div>
    <div class="stat-card <?= $balance >= 0 ? 'primary' : 'danger' ?>">
        <div class="stat-top">
            <div class="stat-icon primary"><i class="fas fa-wallet"></i></div>
            <span class="stat-badge <?= $balance >= 0 ? 'up' : 'down' ?>"><?= $balance >= 0 ? '✓ Surplus' : '⚠ Deficit' ?></span>
        </div>
        <div class="stat-value"><?= formatCurrency(abs($balance)) ?></div>
        <div class="stat-label">Monthly <?= $balance >= 0 ? 'Savings' : 'Overspend' ?></div>
    </div>
    <div class="stat-card warning">
        <div class="stat-top">
            <div class="stat-icon warning"><i class="fas fa-sliders-h"></i></div>
            <span class="stat-badge <?= $budgetUsedPct >= 90 ? 'down' : 'up' ?>"><?= number_format($budgetUsedPct, 0) ?>% used</span>
        </div>
        <div class="stat-value"><?= formatCurrency($budget) ?></div>
        <div class="stat-label">Monthly Budget</div>
        <div class="progress-bar-wrap" style="margin-top:12px">
            <div class="progress-bar <?= $budgetUsedPct >= 90 ? 'danger' : ($budgetUsedPct >= 70 ? 'warning' : 'success') ?>" style="width:<?= $budgetUsedPct ?>%"></div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid-2" style="margin-bottom:24px">
    <!-- Daily Spend Bar Chart -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-bar"></i> Daily Expenses (Last 7 Days)</h3>
            <a href="expenses.php" style="font-size:13px;color:#6366f1;text-decoration:none">View all →</a>
        </div>
        <div class="card-body">
            <div class="chart-wrap">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Category Pie Chart -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie"></i> Expense Breakdown</h3>
            <span style="font-size:12px;color:#94a3b8"><?= date('F Y') ?></span>
        </div>
        <div class="card-body">
            <?php if (count($catLabels) > 0): ?>
            <div class="chart-wrap-sm">
                <canvas id="categoryChart"></canvas>
            </div>
            <div class="donut-legend">
                <?php foreach ($catLabels as $i => $label): ?>
                <div class="legend-item">
                    <div class="legend-dot" style="background:<?= $catColors[$i] ?? '#6366f1' ?>"></div>
                    <span class="legend-name"><?= htmlspecialchars($label) ?></span>
                    <span class="legend-val"><?= formatCurrency($catAmounts[$i]) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-chart-pie"></i>
                <h4>No data yet</h4>
                <p>Add your first expense to see the breakdown</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bottom Row -->
<div class="grid-2-1">
    <!-- Recent Transactions -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Recent Transactions</h3>
            <a href="transactions.php" style="font-size:13px;color:#6366f1;text-decoration:none">View all →</a>
        </div>
        <div class="card-body" style="padding:0 24px">
            <?php if ($recent->num_rows > 0): while ($txn = $recent->fetch_assoc()): ?>
            <div class="txn-list-item">
                <div class="txn-list-icon" style="background:<?= $txn['cat_color'] ? $txn['cat_color'].'22' : '#6366f122' ?>">
                    <i class="fas <?= $txn['cat_icon'] ?? 'fa-tag' ?>" style="color:<?= $txn['cat_color'] ?? '#6366f1' ?>"></i>
                </div>
                <div class="txn-list-details">
                    <div class="txn-list-title"><?= htmlspecialchars($txn['title']) ?></div>
                    <div class="txn-list-meta">
                        <?= htmlspecialchars($txn['cat_name'] ?? 'Uncategorized') ?> &bull;
                        <?= date('d M', strtotime($txn['date'])) ?> &bull;
                        <?= ucfirst(str_replace('_',' ',$txn['payment_method'])) ?>
                    </div>
                </div>
                <div class="txn-list-amount <?= $txn['type'] === 'income' ? 'amount-positive' : 'amount-negative' ?>">
                    <?= $txn['type'] === 'income' ? '+' : '-' ?><?= formatCurrency($txn['amount']) ?>
                </div>
            </div>
            <?php endwhile; else: ?>
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <h4>No transactions yet</h4>
                <p>Click "Add Transaction" to get started</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Side Panel -->
    <div>
        <!-- Budget Overview -->
        <div class="card" style="margin-bottom:20px">
            <div class="card-header">
                <h3><i class="fas fa-sliders-h"></i> Budgets</h3>
                <a href="budget.php" style="font-size:13px;color:#6366f1;text-decoration:none">Manage →</a>
            </div>
            <div class="card-body" style="padding:16px">
                <?php if ($budgets->num_rows > 0): while ($b = $budgets->fetch_assoc()):
                    $pct = $b['amount'] > 0 ? min(100, ($b['spent']/$b['amount'])*100) : 0;
                    $cls = $pct >= 90 ? 'danger' : ($pct >= 70 ? 'warning' : 'success');
                ?>
                <div style="margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                        <span style="font-size:13px;font-weight:600;display:flex;align-items:center;gap:7px">
                            <i class="fas <?= $b['icon'] ?? 'fa-tag' ?>" style="color:<?= $b['color'] ?? '#6366f1' ?>"></i>
                            <?= htmlspecialchars($b['cat_name'] ?? $b['title']) ?>
                        </span>
                        <span style="font-size:12px;color:#94a3b8"><?= number_format($pct,0) ?>%</span>
                    </div>
                    <div class="progress-bar-wrap">
                        <div class="progress-bar <?= $cls ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:11px;color:#94a3b8;margin-top:3px">
                        <span>Spent: <?= formatCurrency($b['spent']) ?></span>
                        <span>Limit: <?= formatCurrency($b['amount']) ?></span>
                    </div>
                </div>
                <?php endwhile; else: ?>
                <div class="empty-state" style="padding:30px 10px">
                    <i class="fas fa-sliders-h"></i>
                    <p>No budgets set</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Savings Goals -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-piggy-bank"></i> Savings Goals</h3>
                <a href="savings.php" style="font-size:13px;color:#6366f1;text-decoration:none">View →</a>
            </div>
            <div class="card-body" style="padding:16px">
                <?php if ($goals->num_rows > 0): while ($g = $goals->fetch_assoc()):
                    $pct = $g['target_amount'] > 0 ? min(100, ($g['saved_amount']/$g['target_amount'])*100) : 0;
                ?>
                <div style="margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                        <span style="font-size:13px;font-weight:600;display:flex;align-items:center;gap:7px">
                            <i class="fas <?= $g['icon'] ?>" style="color:<?= $g['color'] ?>"></i>
                            <?= htmlspecialchars($g['title']) ?>
                        </span>
                        <span style="font-size:12px;color:#94a3b8"><?= number_format($pct,0) ?>%</span>
                    </div>
                    <div class="progress-bar-wrap">
                        <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $g['color'] ?>"></div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:3px">
                        <?= formatCurrency($g['saved_amount']) ?> / <?= formatCurrency($g['target_amount']) ?>
                    </div>
                </div>
                <?php endwhile; else: ?>
                <div class="empty-state" style="padding:30px 10px">
                    <i class="fas fa-piggy-bank"></i>
                    <p>No goals yet</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Daily Bar Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($dailyData, 'label')) ?>,
        datasets: [{
            label: 'Expenses (₹)',
            data: <?= json_encode(array_column($dailyData, 'amount')) ?>,
            backgroundColor: 'rgba(99,102,241,0.15)',
            borderColor: '#6366f1',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.05)' },
                ticks: { callback: v => '₹' + v.toLocaleString('en-IN'), font: { size: 11 } }
            },
            x: { grid: { display: false }, ticks: { font: { size: 12 } } }
        }
    }
});

// Category Donut Chart
<?php if (count($catLabels) > 0): ?>
const catCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(catCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($catLabels) ?>,
        datasets: [{
            data: <?= json_encode($catAmounts) ?>,
            backgroundColor: <?= json_encode($catColors) ?>,
            borderWidth: 3,
            borderColor: '#fff',
            hoverBorderWidth: 0,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' ₹' + ctx.raw.toLocaleString('en-IN', {minimumFractionDigits:2})
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>
