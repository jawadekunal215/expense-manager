<?php
require_once '../includes/config.php';
requireLogin();
$pageTitle = 'Edit Transaction';
$activePage = 'transactions';
$uid = $_SESSION['user_id'];

$id = intval($_GET['id'] ?? 0);
$txn = $conn->query("SELECT * FROM transactions WHERE id=$id AND user_id=$uid")->fetch_assoc();
if (!$txn) {
    header('Location: transactions.php?error=Transaction+not+found');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title  = trim($_POST['title']);
    $amount = floatval($_POST['amount']);
    $type   = $_POST['type'];
    $cat_id = intval($_POST['category_id']) ?: null;
    $date   = $_POST['date'];
    $method = $_POST['payment_method'];
    $desc   = trim($_POST['description']);

    if ($title && $amount > 0) {
        $stmt = $conn->prepare("UPDATE transactions SET title=?,amount=?,type=?,category_id=?,date=?,payment_method=?,description=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sdsssssii", $title, $amount, $type, $cat_id, $date, $method, $desc, $id, $uid);
        $stmt->execute();
        header('Location: transactions.php?success=Transaction+updated!');
        exit;
    }
}

$categories = $conn->query("SELECT * FROM categories WHERE user_id=$uid AND type='{$txn['type']}' ORDER BY name");
require_once '../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-edit" style="color:#6366f1;font-size:22px"></i> Edit Transaction</h1>
        <p>Modify transaction details</p>
    </div>
    <div class="page-header-right">
        <a href="transactions.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<div style="max-width:600px">
    <div class="form-card">
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" required>
                        <option value="expense" <?= $txn['type']==='expense'?'selected':'' ?>>💸 Expense</option>
                        <option value="income" <?= $txn['type']==='income'?'selected':'' ?>>💰 Income</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (₹)</label>
                    <input type="number" name="amount" value="<?= $txn['amount'] ?>" step="0.01" min="0.01" required>
                </div>
            </div>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($txn['title']) ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id">
                        <option value="">-- Select --</option>
                        <?php while ($c = $categories->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>" <?= $txn['category_id']==$c['id']?'selected':'' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?= $txn['date'] ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method">
                    <?php $methods = ['cash'=>'💵 Cash','card'=>'💳 Card','upi'=>'📱 UPI','bank_transfer'=>'🏦 Bank Transfer','other'=>'🔄 Other']; ?>
                    <?php foreach ($methods as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $txn['payment_method']===$val?'selected':'' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description"><?= htmlspecialchars($txn['description'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;gap:12px;margin-top:8px">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Transaction</button>
                <a href="transactions.php" class="btn btn-outline"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
