<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../login.php"); // Redirect non-authenticated or unauthorized users
    exit();
}
include('../db.php'); // Include the database connection
$logged_in_user_id = $_SESSION['user_id'];

// Get search query from the user
$searchQuery = isset($_POST['search']) ? trim($_POST['search']) : '';

// Set the number of services per page
$servicesPerPage = 11;

// Get the current page number
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = $page > 0 ? $page : 1; // Ensure page number is valid
$offset = ($page - 1) * $servicesPerPage;

// Base SQL query
$sql = "SELECT salary.id, users.full_name AS user_name,
               salary.total_price, salary.base_price, salary.created_at, salary.salary_percentage
        FROM salary 
        JOIN users ON salary.user_id = users.id
        WHERE salary.user_id = ?";

if (!empty($searchQuery)) {
    $sql .= " AND (
        users.full_name LIKE CONCAT('%', ?, '%') OR 
        salary.total_price LIKE CONCAT('%', ?, '%') OR 
        salary.base_price LIKE CONCAT('%', ?, '%')
    )";
}

$sql .= " ORDER BY salary.created_at DESC LIMIT ?, ?";

// Prepare and bind parameters
$stmt = $conn->prepare($sql);
if (!empty($searchQuery)) {
    $stmt->bind_param('issii', $logged_in_user_id, $searchQuery, $searchQuery, $searchQuery, $offset, $servicesPerPage);
} else {
    $stmt->bind_param('iii', $logged_in_user_id, $offset, $servicesPerPage);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch total number of salaries
$totalSql = "SELECT COUNT(*) as total_salary FROM salary WHERE salary.user_id = ?";
if (!empty($searchQuery)) {
    $totalSql .= " AND (
        users.full_name LIKE CONCAT('%', ?, '%') OR 
        salary.total_price LIKE CONCAT('%', ?, '%') OR 
        salary.base_price LIKE CONCAT('%', ?, '%')
    )";
}

$totalStmt = $conn->prepare($totalSql);
if (!empty($searchQuery)) {
    $totalStmt->bind_param('isss', $logged_in_user_id, $searchQuery, $searchQuery, $searchQuery);
} else {
    $totalStmt->bind_param('i', $logged_in_user_id);
}
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$totalSalary = $totalRow['total_salary'];

// Calculate total pages
$totalPages = ceil($totalSalary / $servicesPerPage);
?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <title>Admin</title>
    <link rel="stylesheet" href="../css/e_job.css">
    <!-- Boxicons CDN Link -->
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css' rel='stylesheet'> 
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
   </head>
   <style>
    
   </style>
<body>
  <div class="sidebar">
    <div class="logo-details">
      <i class='bx bx-car'></i>
      <span class="logo_name">Машин угаалга</span>
    </div>
    <ul class="nav-links">
        <li>
          <a href="employee.php" >
            <i class='bx bx-grid-alt' ></i>
            <span class="links_name">Хянах самбар</span>
          </a>
        </li>
        
        <li>
          <a href="job.php">
            <i class='bx bx-list-ul' ></i>
            <span class="links_name">Ажлууд</span>
          </a>
        </li>
        <li>
          <a href="salary.php"class="active">
            <i class='bx bx-coin-stack' ></i>
            <span class="links_name">Цалин</span>
          </a>
        </li>
        
        <!-- <li>
          <a href="#">
            <i class='bx bx-message' ></i>
            <span class="links_name">Messages</span>
          </a>
        </li>
        <li>
          <a href="#">
            <i class='bx bx-heart' ></i>
            <span class="links_name">Favrorites</span>
          </a>
        </li> -->
        <li>
          <a href="settings.php">
            <i class='bx bx-cog' ></i>
            <span class="links_name">Тохиргоо</span>
          </a>
        </li>
        <li class="log_out">
  <a href="../index.php" onclick="return confirmLogout()">
    <i class='bx bx-log-out'></i>
    <span class="links_name">Гарах</span>
  </a>
</li>
      </ul>
  </div>
  <section class="home-section">
    <nav>
      <div class="sidebar-button">
        <i class='bx bx-menu sidebarBtn'></i>
        <span class="dashboard"><b>Цалин</b></span>
      </div>
      
      <div class="search-box">
    <input type="text" id="search" placeholder="Бүх талбараар хайх" onkeyup="searchServices()">
    <i class='bx bx-search'></i>
    </div>

      <div class="profile-details">
        <img src="../images/admin.avif" alt="">
        <?php
        echo '<span class="admin_name">' . $_SESSION['full_name'] . '</span>';
        ?>

        
      </div>
    </nav>

    <div class="home-content">
      

      <div class="sales-boxes">
        <div class="recent-sales box">
          <div class="title"><b>Нийт:</b> <?php echo $totalSalary; ?></div>
          
          <div class="sales-details">
          <table>
    <thead>
        <tr>
        <?php
        // Connect to the database
        include('../db.php');

        // Query to get the current salary percentage
        $salarySql = "SELECT salary_percentage FROM salary"; // Adjust the condition based on your requirement
        $salaryStmt = $conn->prepare($salarySql);
        $salaryStmt->execute();
        $salaryResult = $salaryStmt->get_result();

        if ($salaryResult->num_rows > 0) {
            $salaryRow = $salaryResult->fetch_assoc();
            $currentPercentage = $salaryRow['salary_percentage'];
        } else {
            $currentPercentage = 50; // Default or error value
        }

        $salaryStmt->close();
        ?>
            <th><b>№</b></th>
            <th><b>Нэр</b></th>
            <th><b>Нийт үнэ</b></th>
            <th><b>Цалин(<?php echo htmlspecialchars($currentPercentage); ?>%)</b></th>
            <th><b>Огноо</b></th>
            <!-- <th>Actions</th> -->
        </tr>
    </thead>
    <tbody>
        <?php
        

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['user_name']}</td>";
                echo "<td>" . $row['total_price'] . "₮</td>";
                echo "<td>" . $row['base_price'] . "₮</td>";
                echo "<td>{$row['created_at']}</td>";
                // echo "<td></td>";

                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='9'>Мэдээлэл байхгүй..</td></tr>";
        }
        ?>
    </tbody>
</table>
  
          </div>
          <!-- <div class="button">
            <a href="#">See All</a>
          </div> -->
          <div class="pagination">
    <?php
    // Display previous page link
    if ($page > 1) {
        echo '<a href="?page=' . ($page - 1) . '" class="prev">Өмнөх</a>';
    }

    // Display page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $page) {
            echo '<a href="?page=' . $i . '" class="active">' . $i . '</a>';
        } else {
            echo '<a href="?page=' . $i . '">' . $i . '</a>';
        }
    }

    // Display next page link
    if ($page < $totalPages) {
        echo '<a href="?page=' . ($page + 1) . '" class="next">Дараах</a>';
    }
    ?>
</div>
        </div>
        
    

        
</div>

    </div>
  </section>

  <script>
   let sidebar = document.querySelector(".sidebar");
let sidebarBtn = document.querySelector(".sidebarBtn");

// Check the localStorage for the sidebar state on page load
if (localStorage.getItem("sidebarState") === "active") {
  sidebar.classList.add("active");
  sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
} else {
  sidebar.classList.remove("active");
  sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
}

// Toggle the sidebar state when the button is clicked
sidebarBtn.onclick = function() {
  sidebar.classList.toggle("active");

  if (sidebar.classList.contains("active")) {
    sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
    // Save the state in localStorage
    localStorage.setItem("sidebarState", "active");
  } else {
    sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
    // Save the state in localStorage
    localStorage.setItem("sidebarState", "inactive");
  }
}


function searchServices() {
    var input = document.getElementById("search").value.toLowerCase(); // Хайлт оруулах утгыг авах
    var table = document.querySelector("table"); // Хүснэгтийн элемент авах
    var rows = table.querySelectorAll("tbody tr"); // Хүснэгтийн мөрүүдийг авах

    rows.forEach(function(row) {
        var cells = row.querySelectorAll("td"); // Багана бүрийн утгыг авах
        var match = false; // Эхний байдлаар таараагүй гэж үзнэ

        cells.forEach(function(cell) {
            if (cell.textContent.toLowerCase().includes(input)) { // Бүх баганаар хайх
                match = true; // Таарсан бол үнэн болгож өөрчилнө
            }
        });

        if (match) {
            row.style.display = ""; // Таарч байгаа мөрийг харуулах
        } else {
            row.style.display = "none"; // Таарахгүй бол нуух
        }
    });
}


 </script>

</body>
</html>
<?php
$conn->close(); // Close the database connection
?>