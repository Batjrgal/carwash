<?php
session_start();
include('../db.php');

if (isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $user_id = $_SESSION['user_id'];

    $query = "UPDATE users SET full_name='$full_name', email='$email', phone='$phone' WHERE id='$user_id'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['full_name'] = $full_name;
        $_SESSION['email'] = $email;
        $_SESSION['phone'] = $phone;
        header("Location: settings.php?success=Мэдээлэл шинэчлэгдлээ");
    } else {
        header("Location: settings.php?error=Алдаа гарлаа");
    }
}
?>