<?php
require_once '../includes/config.php';
requireLogin();

$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title   = trim($_POST['title'] ?? '');
    $amount  = floatval($_POST['amount'] ?? 0);
    $type    = $_POST['type'] ?? 'expense';
    $cat_id  = !empty($_POST['category_id']) ? intval($_POST['category_id']) : NULL;
    $date    = $_POST['date'] ?? date('Y-m-d');
    $method  = $_POST['payment_method'] ?? 'cash';
    $desc    = trim($_POST['description'] ?? '');

    if (!empty($title) && $amount > 0) {

        $sql = "INSERT INTO transactions
                (user_id, category_id, title, amount, type, description, date, payment_method)
                VALUES (?,?,?,?,?,?,?,?)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param(
            "iisdssss",
            $uid,
            $cat_id,
            $title,
            $amount,
            $type,
            $desc,
            $date,
            $method
        );

        if ($stmt->execute()) {

            if ($cat_id && $type == "expense") {

                $m = date('m', strtotime($date));
                $y = date('Y', strtotime($date));

                $update = $conn->prepare("
                    UPDATE budgets
                    SET spent = spent + ?
                    WHERE user_id = ?
                    AND category_id = ?
                    AND month = ?
                    AND year = ?
                ");

                if ($update) {
                    $update->bind_param("diiii", $amount, $uid, $cat_id, $m, $y);
                    $update->execute();
                    $update->close();
                }
            }

            $stmt->close();

            $ref = $_SERVER['HTTP_REFERER'] ?? '../pages/dashboard.php';
            header("Location: $ref?success=Transaction added successfully!");
            exit;
        } else {

            die("Execute failed: " . $stmt->error);
        }
    } else {

        header("Location: ../pages/dashboard.php?error=Please fill all required fields.");
        exit;
    }
}

header("Location: ../pages/dashboard.php?error=Invalid request.");
exit;
?>