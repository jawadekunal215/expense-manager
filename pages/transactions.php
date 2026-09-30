<?php
require_once '../includes/config.php';
requireLogin();
$pageTitle = 'Transactions';
$activePage = 'transactions';
$uid = $_SESSION['user_id'];

// Filters
$filterType = $_GET['type'] ?? 'all'; if (!in_array($filterType, ['all', 'income', 'expense'], true)) $filterType = 'all';
$filterMonth = intval($_GET['month'] ?? date('m'));
$filterYear = intval($_GET['year'] ?? date('Y'));
$search = trim($_GET['search'] ?? '');

$where = "t.user_id=$uid";
if ($filterType !== 'all') $where .= " AND t.type='$filterType'";
$where .= " AND MONTH(t.date)=$filterMonth AND YEAR(t.date)=$filterYear";
if ($search) $where .= " AND (t.title LIKE '%".addslashes($search)."%' OR c.name LIKE '%".addslashes($search)."%')";

$transactions = $conn->query("SELECT t.*, c.name as cat_name, c.icon as cat_icon, c.color as cat_color FROM transactions t LEFT JOIN categories c ON t.category_id=c.id WHERE $where ORDER BY t.date DESC, t.created_at DESC");

$totalIn = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='income' AND MONTH(date)=$filterMonth AND YEAR(date)=$filterYear")->fetch_assoc()['t'];
$totalOut = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='expense' AND MONTH(date)=$filterMonth AND YEAR(date)=$filterYear")->fetch_assoc()['t'];

require_once '../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-exchange-alt" style="color:#6366f1;font-size:22px"></i> All Transactions</h1>
        <p>Complete history of your income and expenses</p>
    </div>
    <div class="page-header-right">
        <button class="btn btn-primary" onclick="openModal('addModal')">
            <i class="fas fa-plus"></i> Add Transaction
        </button>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
    <div class="stat-card success">
        <div class="stat-top">
            <div class="stat-icon success"><i class="fas fa-arrow-up"></i></div>
        </div>
        <div class="stat-value"><?= formatCurrency($totalIn) ?></div>
        <div class="stat-label">Total Income</div>
    </div>
    <div class="stat-card danger">
        <div class="stat-top">
            <div class="stat-icon danger"><i class="fas fa-arrow-down"></i></div>
        </div>
        <div class="stat-value"><?= formatCurrency($totalOut) ?></div>
        <div class="stat-label">Total Expense</div>
    </div>
    <div class="stat-card primary">
        <div class="stat-top">
            <div class="stat-icon primary"><i class="fas fa-balance-scale"></i></div>
        </div>
        <div class="stat-value"><?= formatCurrency($totalIn - $totalOut) ?></div>
        <div class="stat-label">Net Balance</div>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 24px">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <div>
                <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px">SEARCH</label>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search transactions...">
                </div>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px">TYPE</label>
                <select name="type" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none">
                    <option value="all" <?= $filterType=='all'?'selected':'' ?>>All Types</option>
                    <option value="income" <?= $filterType=='income'?'selected':'' ?>>Income</option>
                    <option value="expense" <?= $filterType=='expense'?'selected':'' ?>>Expense</option>
                </select>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px">MONTH</label>
                <select name="month" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $filterMonth==$m?'selected':'' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px">YEAR</label>
                <select name="year" style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none">
                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                    <option value="<?= $y ?>" <?= $filterYear==$y?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
            <a href="transactions.php" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Reset</a>
        </form>
    </div>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list-ul"></i> Transaction History
            <span style="font-size:13px;color:#94a3b8;font-weight:400;margin-left:8px">(<?= $transactions->num_rows ?> records)</span>
        </h3>
        <a href="../actions/export_csv.php?month=<?= $filterMonth ?>&year=<?= $filterYear ?>" class="btn btn-outline btn-sm">
            <i class="fas fa-download"></i> Export CSV
        </a>
    </div>
    <div class="table-container">
        <?php if ($transactions->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title / Category</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($txn = $transactions->fetch_assoc()): ?>
            <tr class="txn-row" data-type="<?= $txn['type'] ?>">
                <td style="color:#94a3b8;font-size:13px"><?= $i++ ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:12px">
                        <div style="width:38px;height:38px;border-radius:10px;background:<?= $txn['cat_color'] ? $txn['cat_color'].'22' : '#6366f122' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="fas <?= $txn['cat_icon'] ?? 'fa-tag' ?>" style="color:<?= $txn['cat_color'] ?? '#6366f1' ?>"></i>
                        </div>
                        <div>
                            <div class="txn-title"><?= htmlspecialchars($txn['title']) ?></div>
                            <div class="txn-category"><?= htmlspecialchars($txn['cat_name'] ?? 'Uncategorized') ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-size:14px;font-weight:500"><?= date('d M Y', strtotime($txn['date'])) ?></div>
                    <div style="font-size:11px;color:#94a3b8"><?= date('D', strtotime($txn['date'])) ?></div>
                </td>
                <td>
                    <span class="type-badge <?= $txn['type'] ?>">
                        <i class="fas <?= $txn['type'] === 'income' ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i>
                        <?= ucfirst($txn['type']) ?>
                    </span>
                </td>
                <td>
                    <span class="method-badge"><?= ucfirst(str_replace('_',' ', $txn['payment_method'])) ?></span>
                </td>
                <td class="<?= $txn['type'] === 'income' ? 'amount-positive' : 'amount-negative' ?>">
                    <?= $txn['type'] === 'income' ? '+' : '-' ?><?= formatCurrency($txn['amount']) ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="edit_transaction.php?id=<?= $txn['id'] ?>" class="btn btn-outline btn-icon" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="confirmDelete('../actions/delete_transaction.php?id=<?= $txn['id'] ?>', '<?= addslashes($txn['title']) ?>')" class="btn btn-danger btn-icon" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-receipt"></i>
            <h4>No transactions found</h4>
            <p>Try changing your filters or add your first transaction</p>
            <button class="btn btn-primary" style="margin-top:16px" onclick="openModal('addModal')">
                <i class="fas fa-plus"></i> Add Transaction
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
