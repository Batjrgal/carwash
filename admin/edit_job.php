<?php
session_start();
include('../db.php');
date_default_timezone_set('Asia/Ulaanbaatar'); // Монголын цагийн бүс

if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jobId = $_POST['job_id'];
    $userId = $_POST['user_id'];
    $serviceId = $_POST['service_id'];
    $vehicleNumber = $_POST['vehicle_number'];
    $payment = $_POST['payment'];

    $conn->begin_transaction();

    try {
        // 1. Get previous service details
        $prevServiceSql = "SELECT service_id FROM jobs WHERE id = ?";
        $prevServiceStmt = $conn->prepare($prevServiceSql);
        $prevServiceStmt->bind_param('i', $jobId);
        $prevServiceStmt->execute();
        $prevServiceResult = $prevServiceStmt->get_result();

        if ($prevServiceResult->num_rows > 0) {
            $prevServiceRow = $prevServiceResult->fetch_assoc();
            $prevServiceId = $prevServiceRow['service_id'];

            $prevPriceSql = "SELECT price FROM services WHERE id = ?";
            $prevPriceStmt = $conn->prepare($prevPriceSql);
            $prevPriceStmt->bind_param('i', $prevServiceId);
            $prevPriceStmt->execute();
            $prevPriceResult = $prevPriceStmt->get_result();
            $prevPrice = $prevPriceResult->fetch_assoc()['price'] ?? 0;
        } else {
            throw new Exception("Previous service not found.");
        }

        // 2. Get the new service price
        $newPriceSql = "SELECT price FROM services WHERE id = ?";
        $newPriceStmt = $conn->prepare($newPriceSql);
        $newPriceStmt->bind_param('i', $serviceId);
        $newPriceStmt->execute();
        $newPriceResult = $newPriceStmt->get_result();
        $newPrice = $newPriceResult->fetch_assoc()['price'] ?? 0;

        // 3. Update the job
        $jobSql = "UPDATE jobs 
                   SET user_id = ?, service_id = ?, vehicle_number = ?, payment = ? 
                   WHERE id = ?";
        $jobStmt = $conn->prepare($jobSql);
        $jobStmt->bind_param("iissi", $userId, $serviceId, $vehicleNumber, $payment, $jobId);
        $jobStmt->execute();

        // 4. Update the salary table
        $todayDate = date('Y-m-d');
        $salarySql = "UPDATE salary 
                      SET total_price = total_price - ? + ?, 
                          base_price = total_price * (salary_percentage / 100) 
                      WHERE user_id = ? AND DATE(created_at) = ?";
        $salaryStmt = $conn->prepare($salarySql);
        $salaryStmt->bind_param('iiis', $prevPrice, $newPrice, $userId, $todayDate);
        $salaryStmt->execute();

        $conn->commit();
        header("Location: job.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }

    $prevServiceStmt->close();
    $prevPriceStmt->close();
    $newPriceStmt->close();
    $jobStmt->close();
    $salaryStmt->close();
    $conn->close();
}
?>