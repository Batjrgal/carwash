<?php
session_start();
if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password']; // No hashing applied
    $role = $_POST['role'];
    $status = 'Идэвхтэй';

    $sql = "INSERT INTO users (full_name, email, phone, password, role, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssss', $full_name, $email, $phone, $password, $role, $status);

    if ($stmt->execute()) {
        header("Location: users.php?message=User+added+successfully");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
