<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
date_default_timezone_set('Asia/Ulaanbaatar'); // Монголын цагийн бүс

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../login.php"); // Redirect non-authenticated or unauthorized users
    exit();
}

include('../db.php'); // Include the database connection
$conn->set_charset('utf8mb4');

// Нэвтэрсэн хэрэглэгчийн ID
$logged_in_user_id = $_SESSION['user_id'];

// Хайлт болон хуудасны тохиргоо
$searchQuery = isset($_POST['search']) ? $_POST['search'] : '';
$servicesPerPage = 7;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $servicesPerPage;

// Ажлуудыг авах SQL хүсэлт
$sql = "SELECT jobs.id, users.full_name AS user_name, services.service_name, services.car_type, 
               jobs.vehicle_number, services.price, jobs.payment, jobs.created_at 
        FROM jobs 
        JOIN users ON jobs.user_id = users.id 
        JOIN services ON jobs.service_id = services.id
        WHERE jobs.user_id = ?
        ORDER BY jobs.created_at DESC
        LIMIT ?, ?";

// Хүсэлтийг бэлтгэх
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $logged_in_user_id, $offset, $servicesPerPage);
$stmt->execute();
$result = $stmt->get_result();

// Нийт ажлын тоог авах
$totalSql = "SELECT COUNT(*) as total_jobs FROM jobs WHERE user_id = ?";
$totalStmt = $conn->prepare($totalSql);
$totalStmt->bind_param("i", $logged_in_user_id);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$totalJobs = $totalRow['total_jobs'];

// Хуудасны тоог тооцоолох
$totalPages = ceil($totalJobs / $servicesPerPage);

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <title>Ажилтан</title>
    <link rel="stylesheet" href="../css/e_job.css">
    <!-- Boxicons CDN Link -->
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css' rel='stylesheet'> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/2.8.0/slimselect.min.css" integrity="sha512-QhrDqeRszsauAfwqszbR3mtxV3ZWp44Lfuio9t1ccs7H15+ggGbpOqaq4dIYZZS3REFLqjQEC1BjmYDxyqz0ZA==" crossorigin="anonymous" referrerpolicy="no-referrer"/>     <meta name="viewport" content="width=device-width, initial-scale=1.0">
   </head>
   <style>
    .ss-main {
      width: 100%;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
    background-color: #f9f9f9;
    font-size: 16px;
    color: #333;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    transition: border-color 0.3s;
    }
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
          <a href="job.php" class="active">
            <i class='bx bx-list-ul' ></i>
            <span class="links_name">Ажлууд</span>
          </a>
        </li>
        <li>
          <a href="salary.php">
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
        <span class="dashboard"><b>Ажил</b></span>
      </div>
      
      <div class="search-box">
    <input type="text" id="search" placeholder="Бүх талбараар хайх..." onkeyup="searchServices()">
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
          <div class="title"><b>Нийт ажлууд:</b> <?php echo $totalJobs; ?> </div>
          
          <div class="sales-details">
          <table>
    <thead>
        <tr>
        <th><b>№</b></th>
        <th><b>Нэр</b></th>
        <th><b>Үйлчилгээ</b></th>
        <th><b>Төрөл</b></th>
        <th><b>Машины №</b></th>
        <th><b>Үнэ(₮)</b></th>
        <!-- <th><b>Төлбөр</b></th> -->
        <th><b>Огноо</b></th>
        </tr>
    </thead>
    <tbody>
        <?php
        

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
              echo "<td>" . $row['user_name'] . "</td>";
              echo "<td>" . $row['service_name'] . "</td>";
              echo "<td>" . $row['car_type'] . "</td>";
              echo "<td>" . $row['vehicle_number'] . "</td>";
              echo "<td>" . $row['price'] . "₮</td>";
              // echo "<td>" . $row['payment'] . "</td>";
              echo "<td>" . $row['created_at'] . "</td>";


                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='9'>Ажил байхгүй.</td></tr>";
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
        <!-- Edit Modal -->
        <!-- Edit Modal -->
<!-- Edit Modal -->
 


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

// Get the modal
var modal = document.getElementById("editModal");

// Get the buttons
var editButtons = document.querySelectorAll(".edit-btn");

// When the user clicks the "Edit" button, open the modal and fill it with the data
// When the user clicks the "Edit" button, open the modal and fill it with the data
editButtons.forEach(function(button) {
    button.addEventListener("click", function() {
        var jobId = button.getAttribute("data-id");
        var userName = button.getAttribute("data-user");
        var serviceName = button.getAttribute("data-service");
        var carType = button.getAttribute("data-car-type");
        var vehicleNumber = button.getAttribute("data-vehicle");
        var payment = button.getAttribute("data-payment");

        // Populate the modal fields
        document.getElementById("jobId").value = jobId;
        document.getElementById("vehicleNumber").value = vehicleNumber;
        document.getElementById("payment").value = payment;

        // Set selected values for user and service dropdowns
        var userOptions = document.querySelectorAll("#userName option");
        var serviceOptions = document.querySelectorAll("#serviceName option");

        // Preselect the correct user
        userOptions.forEach(function(option) {
            if (option.textContent === userName) {
                option.selected = true;
            }
        });

        // Preselect the correct service
        serviceOptions.forEach(function(option) {
            if (option.textContent.includes(serviceName) && option.textContent.includes(carType)) {
                option.selected = true;
            }
        });

        // Show the modal
        modal.style.display = "block";
    });
});


// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close-btn")[0];

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.style.display = "none";
}

// Close the modal when clicked outside the modal
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/slim-select/2.8.0/slimselect.min.js" integrity="sha512-mG8eLOuzKowvifd2czChe3LabGrcIU8naD1b9FUVe4+gzvtyzSy+5AafrHR57rHB+msrHlWsFaEYtumxkC90rg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
  new SlimSelect({
     select: "#service"
  });
  new SlimSelect({
     select: "#user"
  });
  new SlimSelect({
     select: "#payment1"
  });
  new SlimSelect({
     select: "#payment"
     
  });
  var userSelect = new SlimSelect({select: "#user"});
    var serviceSelect = new SlimSelect({select: "#service"});
    
    // Reinitialize dropdowns inside the modal
    function initializeEditModalDropdowns() {
        new SlimSelect({select: "#userName"});
        new SlimSelect({select: "#serviceName"});
    }
    
    // Edit button click handler
    document.querySelectorAll(".edit-btn").forEach(function(button) {
        button.addEventListener("click", function() {
            // Populate modal values and reinitialize SlimSelect
            initializeEditModalDropdowns();
        });
    });

</script>
</body>
</html>
<?php
$conn->close(); // Close the database connection
?>