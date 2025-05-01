<?php
session_start();
include 'db.php'; // Your database connection script

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture user input from form
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate password confirmation
    if ($password !== $confirm_password) {
        header("Location: signup.php?error=Passwords do not match.");
        exit();
    }

    // Insert the new user into the database with 'active' status
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    $role = 'Employee'; // Default role is 'Employee'
    $status = 'Идэвхгүй'; // Default status is 'active'

    $stmt->bind_param("ssssss", $full_name, $email, $phone, $password, $role, $status);

    if ($stmt->execute()) {
        // Redirect to login page or another page
        header("Location: index.php?success=Account created successfully. Please log in.");
        exit();
    } else {
        header("Location: signup.php?error=Error creating account.");
        exit();
    }
}
?>