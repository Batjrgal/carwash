<?php
session_start();
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceName = $_POST['service_name'];
    $carType = $_POST['car_type'];
    $price = $_POST['price'];

    $sql = "INSERT INTO services (service_name, car_type, price, created_at) 
            VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssd', $serviceName, $carType, $price);

    if ($stmt->execute()) {
        header("Location: services.php");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    header("Location: services.php"); // Redirect to services page
    exit();
}
?>
