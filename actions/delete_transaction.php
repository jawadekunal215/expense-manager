<?php
require_once '../includes/config.php';
requireLogin();
$uid = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Get the transaction info first
    $txn = $conn->query("SELECT * FROM transactions WHERE id=$id AND user_id=$uid")->fetch_assoc();
    if ($txn) {
        $conn->query("DELETE FROM transactions WHERE id=$id AND user_id=$uid");
        // Update budget if expense
        if ($txn['type'] === 'expense' && $txn['category_id']) {
            $m = date('m', strtotime($txn['date']));
            $y = date('Y', strtotime($txn['date']));
            $conn->query("UPDATE budgets SET spent = GREATEST(0, spent - {$txn['amount']}) WHERE user_id=$uid AND category_id={$txn['category_id']} AND month=$m AND year=$y");
        }
    }
}

$ref = $_SERVER['HTTP_REFERER'] ?? '../pages/transactions.php';
header("Location: $ref?success=" . urlencode('Transaction deleted successfully.'));
exit;
?>
