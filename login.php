<?php
session_start();
include 'db.php'; // Your database connection script.

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query to check if the email, password, and status match
    $stmt = $conn->prepare("SELECT id, full_name, role, status FROM users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Check if the status is active
        if ($user['status'] === 'Идэвхтэй') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            // Redirect based on role
            if ($user['role'] === 'Admin') {
                header("Location: admin/admin.php");
            } elseif ($user['role'] === 'Manager') {
                header("Location: manager/manager.php");
            } elseif ($user['role'] === 'Employee') {
                header("Location: employee/employee.php");
            } else {
                header("Location: index.php?error=Invalid role.");
            }
            exit();
        } else {
            header("Location: index.php?error=Your account is inactive. Please activate your account.");
            exit();
        }
    } else {
        header("Location: index.php?error=Имэйл эсвэл нууц үг буруу!");
        exit();
    }
}
?>
