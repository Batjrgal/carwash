<?php
session_start();
include('../db.php');

// Check if the user is an admin
if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $percentage = $_POST['percentage'];

    try {
        // Update the salary percentage for all users
        $sql = "UPDATE salary SET salary_percentage = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $percentage);

        if ($stmt->execute()) {
            // Redirect to salary page
            header("Location: salary.php");
            exit();
        } else {
            echo "Error updating salary percentage: " . $stmt->error;
        }

        $stmt->close();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }

    $conn->close();
}
?>
