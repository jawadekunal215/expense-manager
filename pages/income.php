<?php
require_once '../includes/config.php';
requireLogin();
$pageTitle = 'Income';
$activePage = 'income';
$uid = $_SESSION['user_id'];

$month = intval($_GET['month'] ?? date('m'));
$year = intval($_GET['year'] ?? date('Y'));

$transactions = $conn->query("SELECT t.*, c.name as cat_name, c.icon as cat_icon, c.color as cat_color FROM transactions t LEFT JOIN categories c ON t.category_id=c.id WHERE t.user_id=$uid AND t.type='income' AND MONTH(t.date)=$month AND YEAR(t.date)=$year ORDER BY t.date DESC, t.created_at DESC");

$total = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='income' AND MONTH(date)=$month AND YEAR(date)=$year")->fetch_assoc()['t'];
$count = $conn->query("SELECT COUNT(*) as c FROM transactions WHERE user_id=$uid AND type='income' AND MONTH(date)=$month AND YEAR(date)=$year")->fetch_assoc()['c'];
$avgInc = $count > 0 ? $total / $count : 0;

// Category breakdown
$catData = $conn->query("SELECT c.name, c.color, SUM(t.amount) as total FROM transactions t JOIN categories c ON t.category_id=c.id WHERE t.user_id=$uid AND t.type='income' AND MONTH(t.date)=$month AND YEAR(t.date)=$year GROUP BY c.id ORDER BY total DESC");
$catArr = [];
while ($r = $catData->fetch_assoc()) $catArr[] = $r;

require_once '../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-arrow-up" style="color:#10b981;font-size:22px"></i> Income</h1>
        <p>All your income sources for <?= date('F Y', mktime(0,0,0,$month,1,$year)) ?></p>
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
        <button class="btn btn-success" onclick="document.getElementById('txnType').value='income';openModal('addModal')">
            <i class="fas fa-plus"></i> Add Income
        </button>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
    <div class="stat-card success">
        <div class="stat-top"><div class="stat-icon success"><i class="fas fa-coins"></i></div></div>
        <div class="stat-value"><?= formatCurrency($total) ?></div>
        <div class="stat-label">Total Income</div>
    </div>
    <div class="stat-card primary">
        <div class="stat-top"><div class="stat-icon primary"><i class="fas fa-receipt"></i></div></div>
        <div class="stat-value"><?= $count ?></div>
        <div class="stat-label">Income Entries</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-top"><div class="stat-icon warning"><i class="fas fa-calculator"></i></div></div>
        <div class="stat-value"><?= formatCurrency($avgInc) ?></div>
        <div class="stat-label">Average per Entry</div>
    </div>
</div>

<div class="grid-2-1" style="margin-bottom:24px">
    <!-- Income Table -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Income Transactions</h3>
            <span style="font-size:12px;color:#94a3b8"><?= $count ?> entries</span>
        </div>
        <div class="table-container">
            <?php if ($transactions->num_rows > 0): ?>
            <table>
                <thead><tr>
                    <th>Source</th><th>Date</th><th>Method</th><th>Amount</th><th>Action</th>
                </tr></thead>
                <tbody>
                <?php while ($txn = $transactions->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:36px;height:36px;border-radius:10px;background:<?= ($txn['cat_color'] ?? '#10b981').'22' ?>;display:flex;align-items:center;justify-content:center">
                                <i class="fas <?= $txn['cat_icon'] ?? 'fa-tag' ?>" style="color:<?= $txn['cat_color'] ?? '#10b981' ?>"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($txn['title']) ?></div>
                                <div style="font-size:11px;color:#94a3b8"><?= htmlspecialchars($txn['cat_name'] ?? '-') ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:13px"><?= date('d M Y', strtotime($txn['date'])) ?></td>
                    <td><span class="method-badge"><?= ucfirst(str_replace('_',' ',$txn['payment_method'])) ?></span></td>
                    <td class="amount-positive" style="font-size:16px">+<?= formatCurrency($txn['amount']) ?></td>
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
                <i class="fas fa-arrow-up"></i>
                <h4>No income recorded</h4>
                <p>Add your income sources for this month</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Category Breakdown -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-tags"></i> By Category</h3></div>
        <div class="card-body">
            <?php if (count($catArr) > 0): ?>
            <div class="chart-wrap-sm"><canvas id="incCatChart"></canvas></div>
            <div class="donut-legend" style="margin-top:14px">
                <?php foreach ($catArr as $c): ?>
                <div class="legend-item">
                    <div class="legend-dot" style="background:<?= $c['color'] ?>"></div>
                    <span class="legend-name"><?= htmlspecialchars($c['name']) ?></span>
                    <span class="legend-val"><?= formatCurrency($c['total']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state" style="padding:40px 20px">
                <i class="fas fa-chart-pie"></i><p>No data yet</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (count($catArr) > 0): ?>
<script>
const incCatCtx = document.getElementById('incCatChart').getContext('2d');
new Chart(incCatCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($catArr,'name')) ?>,
        datasets: [{
            data: <?= json_encode(array_map(fn($c)=>floatval($c['total']),$catArr)) ?>,
            backgroundColor: <?= json_encode(array_column($catArr,'color')) ?>,
            borderWidth: 3, borderColor: '#fff',
        }]
    },
    options: { responsive:true, maintainAspectRatio:false, cutout:'65%', plugins:{ legend:{display:false} } }
});
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
