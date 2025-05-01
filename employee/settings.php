<?php
session_start();
if ($_SESSION['role'] !== 'Employee') {
    header("Location: ../index.php"); // Redirect non-admin users to the home page.
    exit();
}
include('../db.php'); // Include the database connection

$user_id = $_SESSION['user_id'];
$query = "SELECT full_name, email, phone FROM users WHERE id='$user_id'";
$result = mysqli_query($conn, $query);

if ($row = mysqli_fetch_assoc($result)) {
    $full_name = $row['full_name'];
    $email = $row['email'];
    $phone = $row['phone'];
} else {
    $full_name = "Нэр олдсонгүй";
    $email = "Имэйл олдсонгүй";
    $phone = "Утас олдсонгүй";
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <title>Admin</title>
    <link rel="stylesheet" href="../css/settings.css">
    <!-- Boxicons CDN Link -->
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css' rel='stylesheet'> 
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
   </head>
   <style>
     .sales-details input[type="text"],
  input[type="email"],
  input[type="password"],
  input[type="submit"]{
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
  }

  .sales-details input[type="submit"] {
    background-color: #2697FF;
    color: white;
    padding: 12px;
    font-size: 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }
  
  .sales-details input[type="submit"]:hover {
    background-color: #4ba1f1;
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
          <a href="settings.php" class="active">
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
        <span class="dashboard"><b>Тохиргоо</b></span>
      </div>
      
      <!-- <div class="search-box">
    <input type="text" id="search" placeholder="Бүх талбараар хайх" onkeyup="searchServices()">
    <i class='bx bx-search'></i>
    </div> -->

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
        <div class="title"><b><center>Хэрэглэгчийн мэдээлэл засах</center></b></div>
            <div class="add-service-form">
            <form action="update_profile.php" method="POST">
               
                <label>Нэр:</label>
                <input type="text" name="full_name" value="<?php echo $full_name; ?>" required>

                <label>Имэйл:</label>
                <input type="email" name="email" value="<?php echo $email; ?>" required>

                <label>Утас:</label>
                <input type="text" name="phone" value="<?php echo $phone; ?>" required>

                <input type="submit" value="Засах" name="update_profile"></input>
            </form>
        </div>
    </div>

    <div class="top-sales box">
        <div class="title"><b>Нууц үг солих</b></div>
        <div class="add-service-form">
            <form action="change_password.php" method="POST">
                <label>Хуучин нууц үг:</label>
                <input type="password" name="old_password" required>

                <label>Шинэ нууц үг:</label>
                <input type="password" name="new_password" required>

                <label>Шинэ нууц үг давтах:</label>
                <input type="password" name="confirm_password" required>

                <input type="submit" value="Засах" name="change_password"></input>
            </form>
        </div>
    </div>
    <!-- <div class="top-sales box">
        <div class="title"><b></b></div>
        <div class="add-service-form">
            
        </div>
    </div> -->
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




 </script>

</body>
</html>
<?php
$conn->close(); // Close the database connection
?>