<?php
session_start();
if ($_SESSION['role'] !== 'Manager') {
    header("Location: ../index.php");
    exit();
}

include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    $password = $_POST['password'];

    if (!empty($password)) {
        // Update with password
        $sql = "UPDATE users SET full_name=?, email=?, phone=?, role=?, status=?, password=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssi', $full_name, $email, $phone, $role, $status, $password, $id);
    } else {
        // Update without password
        $sql = "UPDATE users SET full_name=?, email=?, phone=?, role=?, status=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssi', $full_name, $email, $phone, $role, $status, $id);
    }

    if ($stmt->execute()) {
        header("Location: users.php?message=User+updated+successfully");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
