<?php
session_start();
if ($_SESSION['role'] !== 'Admin') {
  header("Location: ../index.php"); // Redirect non-admin users to the home page.
  exit();
}
include('../db.php');
date_default_timezone_set('Asia/Ulaanbaatar'); // Монголын цагийн бүс

$totalUsersQuery = "SELECT COUNT(*) as total_users FROM users WHERE status = 'Идэвхтэй'";
$totalSalaryQuery = "SELECT SUM(total_price) as total_salary FROM salary";
$todayDate = date('Y-m-d');
$yesterdayDate = date('Y-m-d', strtotime('-1 day'));

// Today's queries
$totalSalaryTodayQuery = "SELECT SUM(total_price) as total_salary_today FROM salary WHERE DATE(created_at) = '$todayDate'";
$totalBaseSalaryTodayQuery = "SELECT SUM(base_price) as total_base_salary_today FROM salary WHERE DATE(created_at) = '$todayDate'";

// Yesterday's queries
$yesterdayIncomeQuery = "SELECT SUM(total_price) as yesterday_income FROM salary WHERE DATE(created_at) = '$yesterdayDate'";
$yesterdayBaseSalaryQuery = "SELECT SUM(base_price) as yesterday_base_salary FROM salary WHERE DATE(created_at) = '$yesterdayDate'";

// Previous month's total salary
$lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
$lastMonthEnd = date('Y-m-t', strtotime('last day of last month'));
$lastMonthSalaryQuery = "SELECT SUM(total_price) as last_month_salary FROM salary WHERE DATE(created_at) BETWEEN '$lastMonthStart' AND '$lastMonthEnd'";

// Fetch data from the database
$totalUsers = $conn->query($totalUsersQuery)->fetch_assoc()['total_users'] ?? 0;
$totalSalary = $conn->query($totalSalaryQuery)->fetch_assoc()['total_salary'] ?? 0;
$totalSalaryToday = $conn->query($totalSalaryTodayQuery)->fetch_assoc()['total_salary_today'] ?? 0;
$totalBaseSalaryToday = $conn->query($totalBaseSalaryTodayQuery)->fetch_assoc()['total_base_salary_today'] ?? 0;
$yesterdayIncome = $conn->query($yesterdayIncomeQuery)->fetch_assoc()['yesterday_income'] ?? 0;
$yesterdayBaseSalary = $conn->query($yesterdayBaseSalaryQuery)->fetch_assoc()['yesterday_base_salary'] ?? 0;
$lastMonthSalary = $conn->query($lastMonthSalaryQuery)->fetch_assoc()['last_month_salary'] ?? 0;

// Calculate trends
$incomeDifference = $totalSalaryToday - $yesterdayIncome;
$baseSalaryDifference = $totalBaseSalaryToday - $yesterdayBaseSalary;
$incomeTrend = $incomeDifference >= 0 ? 'up' : 'down';
$baseSalaryTrend = $baseSalaryDifference >= 0 ? 'up' : 'down';

// Monthly trend
$monthlyDifference = $totalSalary - $lastMonthSalary;
$monthlyTrend = $monthlyDifference >= 0 ? 'up' : 'down';
$yesterdayUsersQuery = "SELECT COUNT(*) as yesterday_users FROM users WHERE DATE(created_at) = '$yesterdayDate'";
$yesterdayUsers = $conn->query($yesterdayUsersQuery)->fetch_assoc()['yesterday_users'] ?? 0;

// Нийт хэрэглэгчдийн өсөлт/бууралтын тооцоолол
$userDifference = $totalUsers - $yesterdayUsers;
$userTrend = $userDifference >= 0 ? 'up' : 'down';
$searchQuery = isset($_POST['search']) ? $_POST['search'] : '';

// Set the number of services per page
$servicesPerPage = 4;

// Get the current page number
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $servicesPerPage;

// Modify SQL query based on the search query
$sql = "SELECT jobs.id, users.full_name AS user_name, services.service_name, services.car_type, 
               jobs.vehicle_number, services.price, jobs.payment, jobs.created_at 
        FROM jobs 
        JOIN users ON jobs.user_id = users.id 
        JOIN services ON jobs.service_id = services.id
        WHERE DATE(jobs.created_at) = CURDATE()
        ORDER BY 
    jobs.created_at DESC";

if (!empty($searchQuery)) {
  $sql .= " WHERE 
        users.full_name LIKE '%$searchQuery%' OR 
        services.service_name LIKE '%$searchQuery%' OR 
        services.car_type LIKE '%$searchQuery%' OR 
        jobs.vehicle_number LIKE '%$searchQuery%' OR 
        jobs.payment LIKE '%$searchQuery%' OR 
        services.price LIKE '%$searchQuery%' OR
        jobs.created_at LIKE '%$searchQuery%'";
}
$sql .= " LIMIT $offset, $servicesPerPage";
$result = $conn->query($sql);

// Fetch total number of services
$totalSql = "SELECT COUNT(*) as total_jobs FROM jobs WHERE DATE(jobs.created_at) = CURDATE()";
if ($searchQuery != '') {
  $totalSql .= " WHERE vehicle_number LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR payment LIKE '%" . $conn->real_escape_string($searchQuery) . "%'";
}
$totalResult = $conn->query($totalSql);
$totalRow = $totalResult->fetch_assoc();
$totalJobs = $totalRow['total_jobs'];

// Calculate total pages
$totalPages = ceil($totalJobs / $servicesPerPage);
// Query to get the top salary users

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <meta charset="UTF-8">
  <title>Admin</title>
  <link rel="stylesheet" href="../css/admin.css">
  <!-- Boxicons CDN Link -->
  <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
  <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css' rel='stylesheet'>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
</head>
<style>
  .overview-boxes .box-topic {
    font-size: 15px;
    font-weight: 500;
  }

  .p {
    position: absolute;
    bottom: 0;
    right: 0;
  }

  @media (max-width: 700px) {
    .weather-details {
      display: none;
    }
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
        <a href="admin.php" class="active">
          <i class='bx bx-grid-alt' title="Хянах самбар"></i>
          <span class="links_name">Хянах самбар</span>
        </a>
      </li>
      <!-- <li>
        <a href="services.php">
          <i class='bx bx-book-alt' title="Үйлчилгээ"></i>
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

      <script>
        function confirmLogout() {
          return confirm("Та системээс гарахдаа итгэлтэй байна уу?");
        }
      </script>

    </ul>
  </div>
  <section class="home-section">
    <nav>
      <div class="sidebar-button">
        <i class='bx bx-menu sidebarBtn'></i>
        <span class="dashboard"><b>Хянах самбар</b></span>
      </div>
      <div class="search-box">
        <input type="text" id="search" placeholder="Хайх..." autofocus onkeyup="searchServices()">
        <i class='bx bx-search'></i>
      </div>
      <div class="profile-details">
        <img src="../images/admin.avif" alt="">
        <?php
        echo '<span class="admin_name">' . $_SESSION['full_name'] . '</span>';
        ?>
        <!-- <span class="weather-details" id="weather"></span> -->
      </div>
    </nav>

    <div class="home-content">
      <div class="overview-boxes">
        <div class="box">
          <div class="right-side">
            <div class="box-topic">Нийт хэрэглэгч</div>
            <div class="number"><?php echo $totalUsers; ?></div>

            <div class="indicator">
              <i class='bx <?php echo $userTrend === "up" ? "bx-up-arrow-alt" : "bx-down-arrow-alt down"; ?>'></i>
              <span class="text">
                <?php
                echo $userTrend === "up"
                  ? "Өчигдрийнхөөс " . abs($userDifference) . " өссөн"
                  : "Өчигдрийнхөөс " . abs($userDifference) . " буурсан";
                ?>
              </span>
            </div>
          </div>
          <i class="fa-solid fa-users cart" style="color: #74C0FC;"></i>
        </div>

        <div class="box">
          <div class="right-side">
            <div class="box-topic">Нийт орлого</div>
            <div class="number">₮<?php echo number_format($totalSalary); ?></div>
            <div class="indicator">
              <i class='bx <?php echo $monthlyTrend === "up" ? "bx-up-arrow-alt" : "bx-down-arrow-alt down"; ?>'></i>
              <span class="text">
                <?php
                echo $monthlyTrend === "up"
                  ? "Өмнөх сараас ₮" . number_format(abs($monthlyDifference)) . " өссөн"
                  : "Өмнөх сараас ₮" . number_format(abs($monthlyDifference)) . " буурсан";
                ?>
              </span>
            </div>
          </div>
          <i class="fa-solid fa-money-bill cart two" style="color: #63E6BE;"></i>
        </div>
        <div class="box">
          <div class="right-side">
            <div class="box-topic">Өнөөдрийн нийт орлого</div>
            <div class="number">₮<?php echo number_format($totalSalaryToday); ?></div>
            <div class="indicator">
              <i class='bx <?php echo $incomeTrend === "up" ? "bx-up-arrow-alt" : "bx-down-arrow-alt down"; ?>'></i>
              <span class="text">
                <?php
                echo $incomeTrend === "up"
                  ? "Өчигдрийнхөөс ₮" . number_format(abs($incomeDifference)) . " өссөн"
                  : "Өчигдрийнхөөс ₮" . number_format(abs($incomeDifference)) . " буурсан";
                ?>
              </span>
            </div>

          </div>
          <i class="fa-solid fa-money-bill-1 cart three" style="color: #FFD43B;"></i>
        </div>
        <div class="box">
          <div class="right-side">
            <div class="box-topic">Өнөөдрийн ажилчдын цалин</div>
            <div class="number">₮<?php echo number_format($totalBaseSalaryToday); ?></div>
            <div class="indicator">
              <i class='bx <?php echo $baseSalaryTrend === "up" ? "bx-up-arrow-alt" : "bx-down-arrow-alt down"; ?>'></i>
              <span class="text">
                <?php
                echo $baseSalaryTrend === "up"
                  ? "Өчигдрийнхөөс ₮" . number_format(abs($baseSalaryDifference)) . " өссөн"
                  : "Өчигдрийнхөөс ₮" . number_format(abs($baseSalaryDifference)) . " буурсан";
                ?>
              </span>
            </div>
          </div>
          <i class="fa-solid fa-money-bill cart four" style="color: #e05260;"></i>
        </div>
      </div>

      <div class="sales-boxes">
        <div class="recent-sales box">
          <div class="title"><b>Өнөөдрийн ажил:</b> <?php echo $totalJobs; ?></div>

          <div class="sales-details">
            <table>
              <thead>
                <tr>
                  <th><b>№</b></th>
                  <th><b>Нэр</b></th>
                  <th><b>Үйлчилгээ</b></th>
                  <th><b>Төрөл</b></th>
                  <th><b>Машины №</b></th>
                  <th><b>Үнэ</b></th>
                  <th><b>Төлбөр</b></th>
                  <th><b>Огноо</b></th>

                </tr>
              </thead>
              <tbody>
                <?php


                if ($result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$row['id']}</td>";
                    echo "<td>{$row['user_name']}</td>";
                    echo "<td>{$row['service_name']}</td>";
                    echo "<td>{$row['car_type']}</td>";
                    echo "<td>{$row['vehicle_number']}</td>";
                    echo "<td>" . $row['price'] . "₮</td>";
                    echo "<td>{$row['payment']}</td>";
                    echo "<td>{$row['created_at']}</td>";


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
        <div class="top-sales box">
          <div class="title"><b>👑 Шилдэг ажилчид</b></div>
          <ul class="top-sales-details">
            <?php
            // Query to get the top salary users
            $topSalaryQuery = "
            SELECT users.full_name, SUM(salary.base_price) AS total_salary
            FROM salary
            JOIN users ON salary.user_id = users.id
            GROUP BY users.id
            ORDER BY total_salary DESC
            LIMIT 6"; // Adjust LIMIT as needed
            
            $topSalaryResult = $conn->query($topSalaryQuery);

            // Counter for ranking
            $rank = 1;

            // Display the top salary users with their rank
            if ($topSalaryResult->num_rows > 0) {
              while ($row = $topSalaryResult->fetch_assoc()) {
                echo '<li>';
                echo '<a href="#">';

                // Display a special avatar for the top-ranked user
                echo '<div class="avatar-container">';
                if ($rank == 1) {
                  // Special avatar for rank 1
                  echo '<img src="../images/user1.png" alt="Top User Avatar" class="special-avatar" />';
                } else {
                  // Default avatar for other users
                  echo '<img src="../images/user.png" alt="User Avatar" class="user-avatar" />';
                }
                echo '</div>';

                $full_name = htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8');

                // Үгийн уртыг шалгах
                if (mb_strlen($full_name) > 9) {
                  // Урт 8-с дээш бол 8 тэмдэгтээс хойш ... нэмэх
                  $display_name = mb_substr($full_name, 0, 8) . '..';
                } else {
                  // Урт 8 эсвэл түүнээс бага бол өөрчлөлтгүй хэвлэх
                  $display_name = $full_name;
                }

                // HTML спаных дээр title атрибутоор бүтэн үгийг харуулах
                echo '<span class="product" title="' . $full_name . '">' . $display_name . '</span>';
                echo '</a>';
                echo '<span class="price">₮' . number_format($row['total_salary']) . '</span>';
                echo '<span class="rank"><b>#' . $rank . '</b></span>'; // Display rank
                echo '</li>';

                // Increment the rank
                $rank++;
              }
            } else {
              echo "<li>No top users found</li>";
            }
            ?>
          </ul>
        </div>


      </div>

    </div>
    <!-- <p class="p"><script id="_waua80">var _wau = _wau || []; _wau.push(["small", "36jamcf7pb", "a80"]);</script><script async src="//waust.at/s.js"></script></p> -->
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


    function searchServices() {
      var input = document.getElementById("search").value.toLowerCase(); // Хайлт оруулах утгыг авах
      var table = document.querySelector("table"); // Хүснэгтийн элемент авах
      var rows = table.querySelectorAll("tbody tr"); // Хүснэгтийн мөрүүдийг авах

      rows.forEach(function (row) {
        var cells = row.querySelectorAll("td"); // Багана бүрийн утгыг авах
        var match = false; // Эхний байдлаар таараагүй гэж үзнэ

        cells.forEach(function (cell) {
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
  <script>
    // Fetch the user's location using Geolocation API
    function getWeather() {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(async (position) => {
          const lat = position.coords.latitude;
          const lon = position.coords.longitude;

          // Fetch weather data
          const apiKey = '444b81980102ecf4b0e286f0663921e8'; // Replace with your OpenWeatherMap API key
          const url = `https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&units=metric&lang=mn&appid=${apiKey}`;
          try {
            const response = await fetch(url);
            const data = await response.json();
            const temp = Math.round(data.main.temp);
            // const city = data.name;
            const icon = data.weather[0].icon; // Fetch the weather icon code
            const iconUrl = `https://openweathermap.org/img/wn/${icon}@2x.png`;

            document.getElementById('weather').innerHTML = `<img src="${iconUrl}" alt="Weather Icon" style="width:24px; vertical-align:middle;"><b> ${temp}°C </b>`;
          } catch (error) {
            document.getElementById('weather').innerText = "";
          }
        });
      } else {
        document.getElementById('weather').innerText = "";
      }
    }

    // Call the function on page load
    window.onload = getWeather;
  </script>

</body>

</html>