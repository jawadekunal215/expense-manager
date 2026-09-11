<?php
require_once '../includes/config.php';
requireLogin();
$pageTitle = 'Reports';
$activePage = 'reports';
$uid = $_SESSION['user_id'];

$year = intval($_GET['year'] ?? date('Y'));

// Monthly Income vs Expense for selected year
$monthly = [];
for ($m = 1; $m <= 12; $m++) {
    $inc = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='income' AND MONTH(date)=$m AND YEAR(date)=$year")->fetch_assoc()['t'];
    $exp = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='expense' AND MONTH(date)=$m AND YEAR(date)=$year")->fetch_assoc()['t'];
    $monthly[] = ['month' => date('M', mktime(0,0,0,$m,1)), 'income' => floatval($inc), 'expense' => floatval($exp)];
}

// Top 8 expense categories
$topCats = $conn->query("SELECT c.name, c.color, SUM(t.amount) as total, COUNT(*) as txn_count FROM transactions t JOIN categories c ON t.category_id=c.id WHERE t.user_id=$uid AND t.type='expense' AND YEAR(t.date)=$year GROUP BY c.id ORDER BY total DESC LIMIT 8");

// Payment method breakdown
$payMethods = $conn->query("SELECT payment_method, SUM(amount) as total, COUNT(*) as cnt FROM transactions WHERE user_id=$uid AND type='expense' AND YEAR(date)=$year GROUP BY payment_method ORDER BY total DESC");

// Year totals
$yearInc = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='income' AND YEAR(date)=$year")->fetch_assoc()['t'];
$yearExp = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='expense' AND YEAR(date)=$year")->fetch_assoc()['t'];
$yearTxns = $conn->query("SELECT COUNT(*) as c FROM transactions WHERE user_id=$uid AND YEAR(date)=$year")->fetch_assoc()['c'];
$savingsRate = $yearInc > 0 ? (($yearInc - $yearExp) / $yearInc) * 100 : 0;

require_once '../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-chart-pie" style="color:#6366f1;font-size:22px"></i> Financial Reports</h1>
        <p>Detailed analysis of your finances</p>
    </div>
    <div class="page-header-right">
        <form method="GET" style="display:flex;gap:10px;align-items:center">
            <label style="font-size:13px;font-weight:600;color:#64748b">Year:</label>
            <select name="year" onchange="this.form.submit()" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none">
                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
        <a href="../actions/export_csv.php?year=<?= $year ?>" class="btn btn-outline">
            <i class="fas fa-download"></i> Export
        </a>
    </div>
</div>

<!-- Year Summary Stats -->
<div class="stats-grid" style="margin-bottom:28px">
    <div class="stat-card success">
        <div class="stat-top"><div class="stat-icon success"><i class="fas fa-arrow-up"></i></div></div>
        <div class="stat-value"><?= formatCurrency($yearInc) ?></div>
        <div class="stat-label">Total Income <?= $year ?></div>
    </div>
    <div class="stat-card danger">
        <div class="stat-top"><div class="stat-icon danger"><i class="fas fa-arrow-down"></i></div></div>
        <div class="stat-value"><?= formatCurrency($yearExp) ?></div>
        <div class="stat-label">Total Expenses <?= $year ?></div>
    </div>
    <div class="stat-card primary">
        <div class="stat-top"><div class="stat-icon primary"><i class="fas fa-piggy-bank"></i></div></div>
        <div class="stat-value"><?= formatCurrency($yearInc - $yearExp) ?></div>
        <div class="stat-label">Net Savings <?= $year ?></div>
    </div>
    <div class="stat-card warning">
        <div class="stat-top"><div class="stat-icon warning"><i class="fas fa-percent"></i></div></div>
        <div class="stat-value"><?= number_format($savingsRate, 1) ?>%</div>
        <div class="stat-label">Savings Rate</div>
    </div>
</div>

<!-- Monthly Trend Chart -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <h3><i class="fas fa-chart-line"></i> Monthly Income vs Expense — <?= $year ?></h3>
    </div>
    <div class="card-body">
        <div class="chart-wrap" style="height:320px">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
</div>

<div class="grid-2" style="margin-bottom:24px">
    <!-- Top Categories -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie"></i> Top Expense Categories</h3>
        </div>
        <div class="card-body">
            <div class="chart-wrap-sm">
                <canvas id="catPieChart"></canvas>
            </div>
            <?php
            $topCatsArr = [];
            while ($tc = $topCats->fetch_assoc()) {
                $topCatsArr[] = $tc;
            }
            ?>
            <div class="donut-legend" style="margin-top:16px">
                <?php foreach ($topCatsArr as $tc): ?>
                <div class="legend-item">
                    <div class="legend-dot" style="background:<?= $tc['color'] ?>"></div>
                    <span class="legend-name"><?= htmlspecialchars($tc['name']) ?></span>
                    <span style="font-size:12px;color:#94a3b8;margin-right:8px">(<?= $tc['txn_count'] ?> txns)</span>
                    <span class="legend-val"><?= formatCurrency($tc['total']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Payment Methods -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-credit-card"></i> Payment Methods</h3>
        </div>
        <div class="card-body">
            <div class="chart-wrap-sm">
                <canvas id="payChart"></canvas>
            </div>
            <?php
            $payArr = [];
            while ($pm = $payMethods->fetch_assoc()) {
                $payArr[] = $pm;
            }
            $payIcons = ['cash'=>'💵','card'=>'💳','upi'=>'📱','bank_transfer'=>'🏦','other'=>'🔄'];
            $payColors = ['cash'=>'#10b981','card'=>'#6366f1','upi'=>'#f59e0b','bank_transfer'=>'#06b6d4','other'=>'#94a3b8'];
            ?>
            <div class="donut-legend" style="margin-top:16px">
                <?php foreach ($payArr as $pm): $nm = str_replace('_',' ',ucfirst($pm['payment_method'])); ?>
                <div class="legend-item">
                    <div class="legend-dot" style="background:<?= $payColors[$pm['payment_method']] ?? '#94a3b8' ?>"></div>
                    <span class="legend-name"><?= ($payIcons[$pm['payment_method']]??'') . ' ' . $nm ?></span>
                    <span style="font-size:12px;color:#94a3b8;margin-right:8px">(<?= $pm['cnt'] ?>)</span>
                    <span class="legend-val"><?= formatCurrency($pm['total']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Table -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-table"></i> Month-wise Summary — <?= $year ?></h3>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Income</th>
                    <th>Expense</th>
                    <th>Savings</th>
                    <th>Savings Rate</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($monthly as $row):
                $savings = $row['income'] - $row['expense'];
                $rate = $row['income'] > 0 ? ($savings/$row['income'])*100 : 0;
                $isProfit = $savings >= 0;
            ?>
            <tr>
                <td style="font-weight:600"><?= $row['month'] ?></td>
                <td class="amount-positive">+<?= formatCurrency($row['income']) ?></td>
                <td class="amount-negative">-<?= formatCurrency($row['expense']) ?></td>
                <td class="<?= $isProfit ? 'amount-positive' : 'amount-negative' ?>">
                    <?= $isProfit ? '+' : '-' ?><?= formatCurrency(abs($savings)) ?>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="flex:1;background:#e2e8f0;border-radius:5px;height:6px">
                            <div style="width:<?= max(0,min(100,abs($rate))) ?>%;height:100%;background:<?= $isProfit ? '#10b981' : '#ef4444' ?>;border-radius:5px"></div>
                        </div>
                        <span style="font-size:12px;font-weight:600;color:<?= $isProfit ? '#10b981' : '#ef4444' ?>"><?= number_format(abs($rate), 1) ?>%</span>
                    </div>
                </td>
                <td>
                    <span class="type-badge <?= $isProfit ? 'income' : 'expense' ?>">
                        <?= $isProfit ? '✅ Saved' : '⚠ Spent more' ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Monthly Line Chart
const mCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(mCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($monthly,'month')) ?>,
        datasets: [
            {
                label: 'Income',
                data: <?= json_encode(array_column($monthly,'income')) ?>,
                borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)',
                borderWidth: 3, fill: true, tension: 0.4, pointRadius: 5, pointHoverRadius: 8,
                pointBackgroundColor: '#10b981',
            },
            {
                label: 'Expense',
                data: <?= json_encode(array_column($monthly,'expense')) ?>,
                borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)',
                borderWidth: 3, fill: true, tension: 0.4, pointRadius: 5, pointHoverRadius: 8,
                pointBackgroundColor: '#ef4444',
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => '₹'+v.toLocaleString('en-IN') }, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// Category Pie
const catPieCtx = document.getElementById('catPieChart').getContext('2d');
new Chart(catPieCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($topCatsArr,'name')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($topCatsArr,'total')) ?>,
            backgroundColor: <?= json_encode(array_column($topCatsArr,'color')) ?>,
            borderWidth: 3, borderColor: '#fff',
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
});

// Payment Chart
const payCtx = document.getElementById('payChart').getContext('2d');
const payColors = { cash:'#10b981', card:'#6366f1', upi:'#f59e0b', bank_transfer:'#06b6d4', other:'#94a3b8' };
new Chart(payCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_map(fn($p)=>str_replace('_',' ',ucfirst($p['payment_method'])), $payArr)) ?>,
        datasets: [{
            data: <?= json_encode(array_column($payArr,'total')) ?>,
            backgroundColor: <?= json_encode(array_map(fn($p)=>$payColors[$p['payment_method']]??'#94a3b8', $payArr)) ?>,
            borderWidth: 3, borderColor: '#fff',
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});
</script>

<?php require_once '../includes/footer.php'; ?>
