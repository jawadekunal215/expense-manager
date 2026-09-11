<?php
require_once '../includes/config.php';
requireLogin();
$pageTitle = 'Expenses';
$activePage = 'expenses';
$uid = $_SESSION['user_id'];

$month = intval($_GET['month'] ?? date('m'));
$year  = intval($_GET['year']  ?? date('Y'));

$transactions = $conn->query("SELECT t.*, c.name as cat_name, c.icon as cat_icon, c.color as cat_color FROM transactions t LEFT JOIN categories c ON t.category_id=c.id WHERE t.user_id=$uid AND t.type='expense' AND MONTH(t.date)=$month AND YEAR(t.date)=$year ORDER BY t.date DESC, t.created_at DESC");

$total  = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='expense' AND MONTH(date)=$month AND YEAR(date)=$year")->fetch_assoc()['t'];
$count  = $conn->query("SELECT COUNT(*) as c FROM transactions WHERE user_id=$uid AND type='expense' AND MONTH(date)=$month AND YEAR(date)=$year")->fetch_assoc()['c'];
$avgExp = $count > 0 ? $total/$count : 0;
$dailyAvg = $total / max(1, date('t', mktime(0,0,0,$month,1,$year)));

// Category breakdown
$catData = $conn->query("SELECT c.name, c.color, SUM(t.amount) as total, COUNT(*) as cnt FROM transactions t JOIN categories c ON t.category_id=c.id WHERE t.user_id=$uid AND t.type='expense' AND MONTH(t.date)=$month AND YEAR(t.date)=$year GROUP BY c.id ORDER BY total DESC");
$catArr = []; while ($r = $catData->fetch_assoc()) $catArr[] = $r;

require_once '../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-arrow-down" style="color:#ef4444;font-size:22px"></i> Expenses</h1>
        <p>Track your spending for <?= date('F Y', mktime(0,0,0,$month,1,$year)) ?></p>
    </div>
    <div class="page-header-right">
        <form method="GET" style="display:flex;gap:8px">
            <select name="month" onchange="this.form.submit()" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none">
                <?php for ($m=1;$m<=12;$m++): ?>
                <option value="<?=$m?>" <?=$month==$m?'selected':''?>><?=date('F',mktime(0,0,0,$m,1))?></option>
                <?php endfor; ?>
            </select>
            <select name="year" onchange="this.form.submit()" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none">
                <?php for ($y=date('Y');$y>=2020;$y--): ?>
                <option value="<?=$y?>" <?=$year==$y?'selected':''?>><?=$y?></option>
                <?php endfor; ?>
            </select>
        </form>
        <button class="btn btn-danger" onclick="document.getElementById('txnType').value='expense';openModal('addModal')">
            <i class="fas fa-plus"></i> Add Expense
        </button>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card danger">
        <div class="stat-top"><div class="stat-icon danger"><i class="fas fa-fire-alt"></i></div></div>
        <div class="stat-value"><?= formatCurrency($total) ?></div>
        <div class="stat-label">Total Spent</div>
    </div>
    <div class="stat-card primary">
        <div class="stat-top"><div class="stat-icon primary"><i class="fas fa-receipt"></i></div></div>
        <div class="stat-value"><?= $count ?></div>
        <div class="stat-label">Transactions</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-top"><div class="stat-icon warning"><i class="fas fa-calendar-day"></i></div></div>
        <div class="stat-value"><?= formatCurrency($dailyAvg) ?></div>
        <div class="stat-label">Daily Average</div>
    </div>
    <div class="stat-card success">
        <div class="stat-top"><div class="stat-icon success"><i class="fas fa-calculator"></i></div></div>
        <div class="stat-value"><?= formatCurrency($avgExp) ?></div>
        <div class="stat-label">Avg per Transaction</div>
    </div>
</div>

<div class="grid-2-1" style="margin-bottom:24px">
    <!-- Expense Table -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Expense Transactions</h3>
            <span style="font-size:12px;color:#94a3b8"><?= $count ?> records</span>
        </div>
        <div class="table-container">
            <?php if ($transactions->num_rows > 0): ?>
            <table>
                <thead><tr><th>Item</th><th>Date</th><th>Method</th><th>Amount</th><th>Action</th></tr></thead>
                <tbody>
                <?php while ($txn = $transactions->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:36px;height:36px;border-radius:10px;background:<?= ($txn['cat_color']??'#ef4444').'22' ?>;display:flex;align-items:center;justify-content:center">
                                <i class="fas <?= $txn['cat_icon']??'fa-tag' ?>" style="color:<?= $txn['cat_color']??'#ef4444' ?>"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($txn['title']) ?></div>
                                <div style="font-size:11px;color:#94a3b8"><?= htmlspecialchars($txn['cat_name']??'-') ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:13px"><?= date('d M Y', strtotime($txn['date'])) ?></td>
                    <td><span class="method-badge"><?= ucfirst(str_replace('_',' ',$txn['payment_method'])) ?></span></td>
                    <td class="amount-negative" style="font-size:16px">-<?= formatCurrency($txn['amount']) ?></td>
                    <td>
                        <button onclick="confirmDelete('../actions/delete_transaction.php?id=<?= $txn['id'] ?>', '<?= addslashes($txn['title']) ?>')" class="btn btn-danger btn-icon btn-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-arrow-down"></i>
                <h4>No expenses this month! 🎉</h4>
                <p>Great job managing your money!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Category Breakdown -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-tags"></i> By Category</h3></div>
        <div class="card-body">
            <?php if (count($catArr) > 0): ?>
            <div class="chart-wrap-sm"><canvas id="expCatChart"></canvas></div>
            <div class="donut-legend" style="margin-top:14px">
                <?php foreach ($catArr as $c): $pct = $total>0 ? ($c['total']/$total)*100 : 0; ?>
                <div class="legend-item">
                    <div class="legend-dot" style="background:<?= $c['color'] ?>"></div>
                    <span class="legend-name"><?= htmlspecialchars($c['name']) ?></span>
                    <span style="font-size:11px;color:#94a3b8;margin-right:6px"><?= number_format($pct,1) ?>%</span>
                    <span class="legend-val"><?= formatCurrency($c['total']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state" style="padding:40px 20px"><i class="fas fa-chart-pie"></i><p>No data yet</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (count($catArr) > 0): ?>
<script>
const expCatCtx = document.getElementById('expCatChart').getContext('2d');
new Chart(expCatCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($catArr,'name')) ?>,
        datasets: [{
            data: <?= json_encode(array_map(fn($c)=>floatval($c['total']),$catArr)) ?>,
            backgroundColor: <?= json_encode(array_column($catArr,'color')) ?>,
            borderWidth: 3, borderColor: '#fff',
        }]
    },
    options: { responsive:true, maintainAspectRatio:false, cutout:'65%', plugins:{legend:{display:false}} }
});
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
