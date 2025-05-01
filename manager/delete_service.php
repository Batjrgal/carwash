<?php
include('../db.php'); // Include the database connection

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Delete the service from the database
    $sql = "DELETE FROM services WHERE id='$id'";

    if ($conn->query($sql) === TRUE) {
        header("Location: services.php");
    } else {
        echo "Error deleting record: " . $conn->error;
    }

    $conn->close(); // Close the database connection
    header('Location: services.php'); // Redirect back to the services page
}
?>
