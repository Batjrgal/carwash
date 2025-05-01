<?php
session_start();
if ($_SESSION['role'] !== 'Admin') {
  header("Location: ../index.php"); // Redirect non-admin users to the home page.
  exit();
}
include('../db.php'); // Include the database connection

// Get search query from the user
$searchQuery = isset($_POST['search']) ? $_POST['search'] : '';

// Set the number of services per page
$servicesPerPage = 7;

// Get the current page number
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $servicesPerPage;

// Modify SQL query based on the search query
$sql = "SELECT id, service_name,car_type, price, created_at FROM services";
if ($searchQuery != '') {
  $sql .= " WHERE service_name LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR price LIKE '%" . $conn->real_escape_string($searchQuery) . "%'";
}
$sql .= " LIMIT $offset, $servicesPerPage"; // Limit results based on pagination

$result = $conn->query($sql);

// Fetch total number of services
$totalSql = "SELECT COUNT(*) as total_services FROM services";
if ($searchQuery != '') {
  $totalSql .= " WHERE service_name LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR price LIKE '%" . $conn->real_escape_string($searchQuery) . "%'";
}
$totalResult = $conn->query($totalSql);
$totalRow = $totalResult->fetch_assoc();
$totalServices = $totalRow['total_services'];

// Calculate total pages
$totalPages = ceil($totalServices / $servicesPerPage);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <meta charset="UTF-8">
  <title>Admin</title>
  <link rel="stylesheet" href="../css/services.css">
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
        <a href="admin.php">
          <i class='bx bx-grid-alt'></i>
          <span class="links_name">Хянах самбар</span>
        </a>
      </li>
      <!-- <li>
        <a href="services.php" class="active">
          <i class='bx bx-book-alt'></i>
          <span class="links_name">Үйлчилгээ</span>
        </a>
      </li> -->
      <!-- <li>
          <a href="#">
            <i class='bx bx-box' ></i>
            <span class="links_name">Product</span>
          </a>
        </li> -->
      <!-- <li>
        <a href="job.php">
          <i class='bx bx-list-ul'></i>
          <span class="links_name">Ажлууд</span>
        </a>
      </li>
      <li>
        <a href="salary.php">
          <i class='bx bx-coin-stack'></i>
          <span class="links_name">Цалин</span>
        </a>
      </li> -->
      <li>
        <a href="users.php">
          <i class='bx bx-user'></i>
          <span class="links_name">Хэрэглэгч</span>
        </a>
      </li>
      <!-- <li>
        <a href="reports.php">
          <i class='bx bx-pie-chart-alt-2'></i>
          <span class="links_name">Тайлан</span>
        </a>
      </li> -->
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
          <i class='bx bx-cog'></i>
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
        <span class="dashboard"><b>Үйлчилгээ</b></span>
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
          <div class="title"><b>Нийт үйлчилгээ: </b><?php echo $totalServices; ?></div>

          <div class="sales-details">
            <table>
              <thead>
                <tr>
                  <th><b>№</b></th>
                  <th><b>Үйлчилгээний нэр</b></th>
                  <th><b>Төрөл</b></th>
                  <th><b>Үнэ</b></th>
                  <!-- <th>Created At</th> -->
                  <th><b>Үйлдэл</b></th>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($result->num_rows > 0) {
                  // Output each row of services
                  while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['service_name'] . "</td>";
                    echo "<td>" . $row['car_type'] . "</td>";
                    echo "<td>" . $row['price'] . "₮</td>";
                    // echo "<td>" . $row['created_at'] . "</td>";
                    echo "<td>
                    <button class='edit-btn' data-id='" . $row['id'] . "' data-name='" . $row['service_name'] . "' data-type='" . $row['car_type'] . "' data-price='" . $row['price'] . "'><i class='fa-solid fa-pen-to-square'></i></button>
                    <button><a href='delete_service.php?id=" . $row['id'] . "' onclick='return confirm(\"Are you sure you want to delete this service?\")' class='delete-btn'><i class='fa-solid fa-trash'></i></a></button>
                    </td>";

                    echo "</tr>";
                  }
                } else {
                  echo "<tr><td colspan='6'>No services available</td></tr>";
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
        <div id="editModal" class="modal">
          <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2><b>Үйлчилгээ засах</b></h2>
            <form id="editServiceForm" action="edit_service.php" method="POST">
              <input type="hidden" name="id" id="serviceId">
              <label for="service_name"><b>Үйлчилгээний нэр</b>:</label>
              <!-- <input type="text" name="service_name" id="serviceName" required> -->
              <select name="service_name" id="serviceName">
                <option disabled selected value="">Сонгох</option>
                <option value="Бүтэн угаалга">Бүтэн угаалга</option>
                <option value="Гадна угаалга">Гадна угаалга</option>
                <option value="Ченж угаалга">Ченж угаалга</option>
              </select>
              <label for="car_type">Төрөл:</label>
              <select id="car_type" name="car_type" required>
                <option disabled selected value="">Сонгох</option>
                <option value="TUNDRA & PICKUP">TUNDRA & PICKUP</option>
                <option value="ALPHARD, VOXY, NOAH">ALPHARD, VOXY, NOAH</option>
                <option value="ТОМ ЖИЙП">ТОМ ЖИЙП</option>
                <option value="ДУНД ЖИЙП">ДУНД ЖИЙП</option>
                <option value="HARRIER, RX, RAV1">HARRIER, RX, RAV1</option>
                <option value="PRIUS 41 & KOMBI">PRIUS 41 & KOMBI</option>
                <option value="PORTER">PORTER</option>
                <option value="ЖИЖИГ МАШИН">ЖИЖИГ МАШИН</option>
              </select>
              <label for="price">Үнэ:</label>
              <input type="text" name="price" id="servicePrice" required>

              <input type="submit" value="Засах">
            </form>
          </div>
        </div>

        <div class="top-sales box">
          <div class="title"><b>Үйлчилгээ нэмэх</b></div>
          <div class="add-service-form">
            <form action="add_service.php" method="POST">
              <label for="service_name">Үйлчилгээний нэр:</label>
              <!-- <input type="text" id="service_name" name="service_name" required> -->
              <select name="service_name" id="serviceName">
                <option disabled selected value="">Сонгох</option>
                <option value="Бүтэн угаалга">Бүтэн угаалга</option>
                <option value="Гадна угаалга">Гадна угаалга</option>
                <option value="Ченж угаалга">Ченж угаалга</option>
              </select>
              <label for="car_type">Төрөл:</label>
              <select id="car_type" name="car_type" required>
                <option disabled selected value="">Сонгох</option>
                <option value="TUNDRA & PICKUP">TUNDRA & PICKUP</option>
                <option value="ALPHARD, VOXY, NOAH">ALPHARD, VOXY, NOAH</option>
                <option value="ТОМ ЖИЙП">ТОМ ЖИЙП</option>
                <option value="ДУНД ЖИЙП">ДУНД ЖИЙП</option>
                <option value="HARRIER, RX, RAV1">HARRIER, RX, RAV1</option>
                <option value="PRIUS 41 & KOMBI">PRIUS 41 & KOMBI</option>
                <option value="PORTER">PORTER</option>
                <option value="ЖИЖИГ МАШИН">ЖИЖИГ МАШИН</option>
              </select>
              <label for="price">Үнэ:</label>
              <input type="text" placeholder="0₮" id="price" name="price" required>

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
    sidebarBtn.onclick = function () {
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
    editButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        var serviceId = button.getAttribute("data-id");
        var serviceName = button.getAttribute("data-name");
        var carType = button.getAttribute("data-type");
        var servicePrice = button.getAttribute("data-price");

        // Populate the modal fields
        document.getElementById("serviceId").value = serviceId;
        document.getElementById("serviceName").value = serviceName;
        document.getElementById("car_type").value = carType; // Pre-select the car type
        document.getElementById("servicePrice").value = servicePrice;

        // Show the modal
        modal.style.display = "block";
      });
    });


    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close-btn")[0];

    // When the user clicks on <span> (x), close the modal
    span.onclick = function () {
      modal.style.display = "none";
    }

    // Close the modal when clicked outside the modal
    window.onclick = function (event) {
      if (event.target == modal) {
        modal.style.display = "none";
      }
    }
    function searchServices() {
      var input = document.getElementById("search").value.toLowerCase();
      var table = document.querySelector("table");
      var rows = table.querySelectorAll("tbody tr");

      rows.forEach(function (row) {
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