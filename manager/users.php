<?php
session_start();
if ($_SESSION['role'] !== 'Manager') {
    header("Location: ../index.php"); // Redirect non-admin users to the home page.
    exit();
}
include('../db.php'); // Include the database connection

// Get search query from the user
$searchQuery = isset($_POST['search']) ? $_POST['search'] : '';

// Set the number of services per page
$servicesPerPage = 7;

// Get the current page number
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $servicesPerPage;

// Modify SQL query based on the search query
$sql = "SELECT id, full_name, email, phone, password, role, status, DATE(created_at) as created_date FROM users";
if ($searchQuery != '') {
    $sql .= " WHERE full_name LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR email LIKE '%" . $conn->real_escape_string($searchQuery) . "%'";
}
$sql .= " LIMIT $offset, $servicesPerPage"; // Limit results based on pagination

$result = $conn->query($sql);

// Fetch total number of services
$totalSql = "SELECT COUNT(*) as total_users FROM users";
if ($searchQuery != '') {
    $totalSql .= " WHERE full_name LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR email LIKE '%" . $conn->real_escape_string($searchQuery) . "%'";
}
$totalResult = $conn->query($totalSql);
$totalRow = $totalResult->fetch_assoc();
$totalServices = $totalRow['total_users'];

// Calculate total pages
$totalPages = ceil($totalServices / $servicesPerPage);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <title>Admin</title>
    <link rel="stylesheet" href="../css/user.css">
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
          <a href="manager.php" >
            <i class='bx bx-grid-alt' ></i>
            <span class="links_name">Хянах самбар</span>
          </a>
        </li>
        <li>
          <a href="services.php" >
            <i class='bx bx-book-alt' ></i>
            <span class="links_name">Үйлчилгээ</span>
          </a>
        </li>
        <!-- <li>
          <a href="#">
            <i class='bx bx-box' ></i>
            <span class="links_name">Product</span>
          </a>
        </li> -->
        <li>
          <a href="job.php">
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
        <li>
          <a href="users.php"class="active">
            <i class='bx bx-user' ></i>
            <span class="links_name">Хэрэглэгч</span>
          </a>
        </li>
        <li>
          <a href="reports.php">
            <i class='bx bx-pie-chart-alt-2' ></i>
            <span class="links_name">Тайлан</span>
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
        <span class="dashboard"><b>Хэрэглэгчид</b></span>
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
          <div class="title"><b>Нийт хэрэглэгчид:</b> <?php echo $totalServices; ?></div>
          
          <div class="sales-details">
          <table>
        <thead>
            <tr>
                <th>№</th>
                <th>Нэр</th>
                <th>Имэйл</th>
                <th>Утас</th>
                <th>Төрөл</th>
                <th>Төлөв</th>
                <th>Бүртгэсэн огноо</th>
                <th>Үйлдэл</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                // Output each row of services
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['full_name'] . "</td>";
                    echo "<td>" . $row['email'] . "</td>";
                    echo "<td>" . $row['phone'] . "</td>";
                    echo "<td>" . $row['role'] . "</td>";
                    echo "<td>" . $row['status'] . "</td>";
                    echo "<td>" . $row['created_date'] . "</td>";
                    echo "<td>
                    <center><button class='edit-btn' 
            data-id='" . $row['id'] . "' 
            data-name='" . $row['full_name'] . "' 
            data-email='" . $row['email'] . "' 
            data-phone='" . $row['phone'] . "' 
            data-role='" . $row['role'] . "' 
            data-status='" . $row['status'] . "'>
        <i class='fa-solid fa-pen-to-square'></i>
    </button>
                    </td></center>";

                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7'>No users available</td></tr>";
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
        echo '<a href="?page=' . ($page - 1) . '" class="prev">Prev</a>';
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
        echo '<a href="?page=' . ($page + 1) . '" class="next">Next</a>';
    }
    ?>
</div>
        </div>
        <!-- Edit Modal -->
        <div id="editModal" class="modal">
          <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Хэрэглэгчийн мэдээлэл засах</h2>
            <form id="editServiceForm" action="update_user.php" method="POST">
        <input type="hidden" name="id" id="editId">
        <label for="editName">Нэр:</label>
        <input type="text" name="full_name" id="editName" required>
        
        <label for="editEmail">Имэйл:</label>
        <input type="email" name="email" id="editEmail" required>
        
        <label for="editPhone">Утас:</label>
        <input type="text" name="phone" id="editPhone" required>
        
        <label for="editRole">Төрөл:</label>
        <select name="role" id="editRole" required disabled title="Өөрчлөх боломжгүй">
            <option disabled value="Admin">Админ</option>
            <option disabled value="Manager">Менежер</option>
            <option value="Employee">Ажилтан</option>
        </select>
        
        <label for="editStatus">Төлөв:</label>
        <select name="status" id="editStatus" required >
            <option value="Идэвхтэй">Идэвхтэй</option>
            <option value="Идэвхгүй">Идэвхгүй</option>
        </select>
        
        <label for="editPassword">Нууц үг:</label>
        <input type="password" name="password" id="editPassword" placeholder="Нууц үгийг хадгалахын тулд хоосон орхино уу">
        
        <input type="submit" value="Засах">
    </form>
          </div>
        </div>

        <div class="top-sales box">
          <div class="title"><b>Хэрэглэгч нэмэх</b></div>
          <div class="add-service-form">
          <form action="add_user.php" method="POST">
          <label for="fullName">Овог, нэр:</label>
        <input type="text" name="full_name" placeholder="Овог, нэр оруулна уу" id="fullName" required>
        <label for="email">Имэйл:</label>
        <input type="email" name="email" placeholder="Имэйл оруулна уу" id="email" required>
        <label for="phone">Утас:</label>
        <input type="text" name="phone" placeholder="Утасны дугаар оруулна уу" id="phone" required>
        <label for="password">Нууц үг:</label>
        <input type="password" name="password" placeholder="Нууц үг оруулна уу" id="password" required>
        <label for="role">Төрөл:</label>
        <select name="role" id="role" required>
            <option disabled value="Admin">Админ</option>
            <option disabled value="Manager">Менежер</option>
            <option value="Employee">Ажилтан</option>
        </select>
            <input type="submit" value="Нэмэх">
          </form>
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

  // Get the modal
var modal = document.getElementById("editModal");

// Get the buttons
var editButtons = document.querySelectorAll(".edit-btn");

// When the user clicks the "Edit" button, open the modal and fill it with the data
editButtons.forEach(function(button) {
    button.addEventListener("click", function() {
        document.getElementById("editId").value = button.getAttribute("data-id");
        document.getElementById("editName").value = button.getAttribute("data-name");
        document.getElementById("editEmail").value = button.getAttribute("data-email");
        document.getElementById("editPhone").value = button.getAttribute("data-phone");
        document.getElementById("editRole").value = button.getAttribute("data-role");
        document.getElementById("editStatus").value = button.getAttribute("data-status");
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
    var input = document.getElementById("search").value.toLowerCase();
    var table = document.querySelector("table");
    var rows = table.querySelectorAll("tbody tr");

    rows.forEach(function(row) {
        var serviceName = row.cells[1].textContent.toLowerCase(); // Assuming service name is in the second column
        var price = row.cells[2].textContent.toLowerCase();
        if (serviceName.indexOf(input) > -1 || price.indexOf(input) > -1) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

 </script>

</body>
</html>
<?php
$conn->close(); // Close the database connection
?>