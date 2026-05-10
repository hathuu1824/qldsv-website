<?php
require_once 'config/cf_class.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account_id = $_SESSION['account_id']; 
    $major_id = intval($_POST['major_id']);
    $note = $_POST['note'];

    if ($major_id > 0) {
        $sql = "INSERT INTO major_registrations (account_id, major_id, note) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $account_id, $major_id, $note);

        if ($stmt->execute()) {
            header("Location: major_request.php?id=".$_GET['id']."&tab=major_reg&status=success");
        } else {
            header("Location: major_request.php?status=error");
        }
    }
}
?>