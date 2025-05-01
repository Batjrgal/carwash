<?php
session_start();
include('../db.php');

if (isset($_POST['change_password'])) {
    $user_id = $_SESSION['user_id'];
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        header("Location: settings.php?error=Нууц үг таарахгүй байна");
        exit();
    }

    // Хуучин нууц үгийг шалгах
    $result = mysqli_query($conn, "SELECT password FROM users WHERE id='$user_id'");
    $row = mysqli_fetch_assoc($result);

    if ($old_password !== $row['password']) {  // Hashing-гүйгээр шууд харьцуулалт
        header("Location: settings.php?error=Хуучин нууц үг буруу байна");
        exit();
    }

    // Hashing-гүйгээр шинэ нууц үгийг хадгалах (ЭРСДЭЛТЭЙ!)
    mysqli_query($conn, "UPDATE users SET password='$new_password' WHERE id='$user_id'");

    header("Location: settings.php?success=Нууц үг амжилттай солигдлоо");
}
?>