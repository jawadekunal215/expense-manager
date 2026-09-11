<?php
require_once '../includes/config.php';
requireLogin();
$uid = $_SESSION['user_id'];

$month = intval($_GET['month'] ?? 0);
$year  = intval($_GET['year'] ?? date('Y'));

$where = "t.user_id=$uid AND YEAR(t.date)=$year";
if ($month) $where .= " AND MONTH(t.date)=$month";

$result = $conn->query("SELECT t.title, t.amount, t.type, t.date, t.payment_method, t.description, c.name as category FROM transactions t LEFT JOIN categories c ON t.category_id=c.id WHERE $where ORDER BY t.date DESC");

$filename = "spendsmart_" . ($month ? date('F',mktime(0,0,0,$month,1)).'_' : '') . $year . ".csv";

header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=$filename");

$out = fopen('php://output', 'w');
fputcsv($out, ['Title','Amount','Type','Category','Date','Payment Method','Description']);
while ($row = $result->fetch_assoc()) {
    fputcsv($out, [
        $row['title'],
        $row['amount'],
        $row['type'],
        $row['category'],
        $row['date'],
        str_replace('_',' ',ucfirst($row['payment_method'])),
        $row['description']
    ]);
}
fclose($out);
?>
