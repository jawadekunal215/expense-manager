<?php
require_once '../includes/config.php';
requireLogin();
$pageTitle = 'Categories';
$activePage = 'categories';
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cat'])) {
    $name  = trim($_POST['name']);
    $icon  = $_POST['icon'] ?? 'fa-tag';
    $color = $_POST['color'] ?? '#6366f1';
    $type  = $_POST['type'] ?? 'expense';
    if ($name) {
        $stmt = $conn->prepare("INSERT INTO categories (user_id,name,icon,color,type) VALUES (?,?,?,?,?)");
        $stmt->bind_param("issss", $uid, $name, $icon, $color, $type);
        $stmt->execute();
        header('Location: categories.php?success=Category+added!');
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM categories WHERE id=$id AND user_id=$uid");
    header('Location: categories.php?success=Category+deleted');
    exit;
}

$cats = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM transactions WHERE category_id=c.id AND user_id=$uid) as txn_count FROM categories c WHERE c.user_id=$uid ORDER BY c.type, c.name");

$icons = ['fa-tag','fa-utensils','fa-car','fa-shopping-bag','fa-film','fa-heartbeat','fa-graduation-cap','fa-file-invoice','fa-home','fa-briefcase','fa-laptop','fa-chart-line','fa-coins','fa-gift','fa-piggy-bank','fa-plane','fa-phone','fa-gamepad','fa-tshirt','fa-book','fa-dumbbell','fa-coffee','fa-dog','fa-baby'];

require_once '../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-tags" style="color:#6366f1;font-size:22px"></i> Categories</h1>
        <p>Manage your income and expense categories</p>
    </div>
    <div class="page-header-right">
        <button class="btn btn-primary" onclick="openModal('catModal')">
            <i class="fas fa-plus"></i> Add Category
        </button>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?></div>
<?php endif; ?>

<!-- Income Categories -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <h3><i class="fas fa-arrow-up" style="color:#10b981"></i> Income Categories</h3>
    </div>
    <div class="category-grid">
        <?php while ($c = $cats->fetch_assoc()): if ($c['type'] !== 'income') continue; ?>
        <div class="category-item">
            <div class="category-item-icon" style="background:<?= $c['color'] ?>22">
                <i class="fas <?= $c['icon'] ?>" style="color:<?= $c['color'] ?>"></i>
            </div>
            <div style="flex:1">
                <div class="category-item-name"><?= htmlspecialchars($c['name']) ?></div>
                <div class="category-item-type"><?= $c['txn_count'] ?> transactions</div>
            </div>
            <?php if ($c['txn_count'] == 0): ?>
            <button onclick="confirmDelete('categories.php?delete=<?= $c['id'] ?>', '<?= addslashes($c['name']) ?>')" class="btn btn-danger btn-icon btn-sm">
                <i class="fas fa-trash"></i>
            </button>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Expense Categories -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-arrow-down" style="color:#ef4444"></i> Expense Categories</h3>
    </div>
    <?php
    // Re-query for expense categories
    $cats2 = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM transactions WHERE category_id=c.id AND user_id=$uid) as txn_count FROM categories c WHERE c.user_id=$uid AND c.type='expense' ORDER BY c.name");
    ?>
    <div class="category-grid">
        <?php while ($c = $cats2->fetch_assoc()): ?>
        <div class="category-item">
            <div class="category-item-icon" style="background:<?= $c['color'] ?>22">
                <i class="fas <?= $c['icon'] ?>" style="color:<?= $c['color'] ?>"></i>
            </div>
            <div style="flex:1">
                <div class="category-item-name"><?= htmlspecialchars($c['name']) ?></div>
                <div class="category-item-type"><?= $c['txn_count'] ?> transactions</div>
            </div>
            <?php if ($c['txn_count'] == 0): ?>
            <button onclick="confirmDelete('categories.php?delete=<?= $c['id'] ?>', '<?= addslashes($c['name']) ?>')" class="btn btn-danger btn-icon btn-sm">
                <i class="fas fa-trash"></i>
            </button>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal-overlay" id="catModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add Category</h3>
            <button class="modal-close" onclick="closeModal('catModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="add_cat" value="1">
            <div class="form-group">
                <label>Category Name</label>
                <input type="text" name="name" placeholder="e.g. Groceries" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Type</label>
                    <select name="type">
                        <option value="expense">💸 Expense</option>
                        <option value="income">💰 Income</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Color</label>
                    <input type="color" name="color" value="#6366f1" style="height:44px">
                </div>
            </div>
            <div class="form-group">
                <label>Icon (Font Awesome class)</label>
                <select name="icon">
                    <?php foreach ($icons as $ic): ?>
                    <option value="<?= $ic ?>"><?= $ic ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px">
                <?php foreach (array_slice($icons, 0, 12) as $ic): ?>
                <div style="width:36px;height:36px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;cursor:pointer" onclick="document.querySelector('[name=icon]').value='<?=$ic?>'">
                    <i class="fas <?= $ic ?>" style="color:#6366f1"></i>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('catModal')">Cancel</button>
                <button type="submit" class="btn-save"><i class="fas fa-check"></i> Add Category</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
