<?php
session_start();
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $serviceName = $_POST['service_name'];
    $carType = $_POST['car_type'];
    $price = $_POST['price'];

    $sql = "UPDATE services 
            SET service_name = ?, car_type = ?, price = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssdi', $serviceName, $carType, $price, $id);

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