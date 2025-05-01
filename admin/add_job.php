<?php
session_start();
include('../db.php'); // Database connection
date_default_timezone_set('Asia/Ulaanbaatar'); // Монголын цагийн бүс

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $serviceId = $_POST['service_id'];
    $vehicleNumber = $_POST['vehicle_number'];
    $payment = $_POST['payment'];

    // 1. Get the price of the service
    $serviceSql = "SELECT price FROM services WHERE id = ?";
    $serviceStmt = $conn->prepare($serviceSql);
    $serviceStmt->bind_param('i', $serviceId);
    $serviceStmt->execute();
    $serviceResult = $serviceStmt->get_result();

    if ($serviceResult->num_rows > 0) {
        $serviceRow = $serviceResult->fetch_assoc();
        $servicePrice = $serviceRow['price'];

        // 2. Insert the new job into the jobs table
        $sql = "INSERT INTO jobs (user_id, service_id, vehicle_number, payment, created_at)
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iiss', $userId, $serviceId, $vehicleNumber, $payment);
        if ($stmt->execute()) {
            // 3. Update the salary table for today
            $todayDate = date('Y-m-d');
            $salaryCheckSql = "SELECT * FROM salary WHERE user_id = ? AND DATE(created_at) = ?";
            $salaryCheckStmt = $conn->prepare($salaryCheckSql);
            $salaryCheckStmt->bind_param('is', $userId, $todayDate);
            $salaryCheckStmt->execute();
            $salaryCheckResult = $salaryCheckStmt->get_result();

            if ($salaryCheckResult->num_rows > 0) {
                // If salary record exists for today, update it
                $updateSalarySql = "UPDATE salary SET total_price = total_price + ?, base_price = total_price * (salary_percentage / 100) WHERE user_id = ? AND DATE(created_at) = ?";
                $updateSalaryStmt = $conn->prepare($updateSalarySql);
                $updateSalaryStmt->bind_param('iis', $servicePrice, $userId, $todayDate);
                $updateSalaryStmt->execute();
                $updateSalaryStmt->close();
            } else {
                // Insert a new salary record
                $defaultPercentage = 50; // Default salary percentage
                $basePrice = $servicePrice * ($defaultPercentage / 100);
                $insertSalarySql = "INSERT INTO salary (user_id, total_price, base_price, salary_percentage, created_at) 
                                    VALUES (?, ?, ?, ?, NOW())";
                $insertSalaryStmt = $conn->prepare($insertSalarySql);
                $insertSalaryStmt->bind_param('iiii', $userId, $servicePrice, $basePrice, $defaultPercentage);
                $insertSalaryStmt->execute();
                $insertSalaryStmt->close();
            }

            $salaryCheckStmt->close();
            header("Location: job.php");
            exit();
        } else {
            echo "Error adding job: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error: Service not found.";
    }

    $serviceStmt->close();
    $conn->close();
}
?>