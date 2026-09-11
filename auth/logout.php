<?php
require_once '../includes/config.php';
session_destroy();
header('Location: ../index.php?success=Logged+out+successfully');
exit;
?>
