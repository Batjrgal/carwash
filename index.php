<?php
include 'db.php';
$query = "
SELECT 
    service_name,
    GROUP_CONCAT(car_type ORDER BY price  DESC  SEPARATOR ', ') AS car_types,
    GROUP_CONCAT(price ORDER BY price  DESC  SEPARATOR ', ') AS prices
FROM 
    services
GROUP BY 
    service_name";
$result = mysqli_query($conn, $query);

$services = [];
while ($row = mysqli_fetch_assoc($result)) {
  $services[] = $row;
}
$error_message = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

  <title>Нүүр хуудас</title>
  <link rel="stylesheet" href="css/style.css" />
  <!-- Unicons -->
  <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />
</head>
<style>
  /* Service Pricing Cards */
  /* Service Pricing Cards */
  .price_card {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 20px;
    padding: 20px;
  }

  .service_card {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
    width: 350px;
    transition: transform 0.3s ease-in-out;
    overflow: hidden;
  }

  .service_card:hover {
    transform: scale(1.05);
  }

  .service_card h3 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: #333;
    background: #007bff;
    color: white;
    padding: 10px;
    border-radius: 8px 8px 0 0;
  }

  .service_card ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .service_card ul li {
    font-size: 1rem;
    padding: 12px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    background: #f9f9f9;
    transition: background 0.3s;
  }

  .service_card ul li:hover {
    background: #e9ecef;
  }

  .service_card ul li:last-child {
    border-bottom: none;
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .price_card {
      flex-direction: column;
      align-items: center;
    }

    .service_card {
      width: 95%;
    }
  }
</style>

<body>
  <div>
    <!-- Header -->
    <header class="header">
      <nav class="nav">
        <a href="#" class="nav_logo"></a>

        <!-- <ul class="nav_items">
          <li class="nav_item">
            <a href="#" class="nav_link">Home</a>
            <a href="#services" class="nav_link">Services</a>
            <a href="#contact" class="nav_link">Contact</a>
            <a href="#about" class="nav_link">About</a>
          </li>
        </ul> -->

        <button class="button" id="form-open">Нэвтрэх</button>
      </nav>
    </header>

    <!-- Home -->
    <section class="home">
      <div class="form_container">
        <i class="uil uil-times form_close"></i>
        <!-- Login From -->
        <div class="form login_form">
          <form action="login.php" method="POST">
            <h2>Нэвтрэх</h2>

            <div class="input_box">
              <input type="email" name="email" placeholder="Имэйл" required />
              <i class="uil uil-envelope-alt email"></i>
            </div>
            <div class="input_box">
              <input type="password" name="password" placeholder="Нууц үг" required />
              <i class="uil uil-lock password"></i>
              <i class="uil uil-eye-slash pw_hide"></i>
            </div>
            <?php if ($error_message): ?>
              <script>alert("<?php echo $error_message; ?>");</script>
            <?php endif; ?>
            <div class="option_field">
              <span class="checkbox">
                <input type="checkbox" id="check" />
                <label for="check">Имэйл сануулах</label>
              </span>
              <!-- <a href="#" class="forgot_pw">Нууц үг сэргээх?</a> -->
            </div>

            <button class="button" type="submit">Нэвтрэх</button>

            <!-- <div class="login_signup">Бүртгэлгүй юу? <a href="#" id="signup">Бүртгүүлэх</a></div> -->
          </form>
        </div>

        <!-- Signup From -->
        <div class="form signup_form">
          <form action="signup.php" method="POST">
            <h2>Бүртгүүлэх хүсэлт</h2>
            <!-- <p>Таны бүртгэл идэвхжсэний дараа нэвтрэх боломжтой</p> -->
            <div class="input_box">
              <input type="text" name="full_name" placeholder="Овог нэр" required />
              <i class="uil uil-user name"></i>
            </div>
            <div class="input_box">
              <input type="email" name="email" placeholder="Имэйл" required />
              <i class="uil uil-envelope-alt email"></i>
            </div>
            <div class="input_box">
              <input type="number" name="phone" placeholder="Утасны дугаар" required />
              <i class="uil uil-phone phone"></i>
            </div>
            <div class="input_box">
              <input type="password" name="password" placeholder="Нууц үг" required />
              <i class="uil uil-lock password"></i>
              <i class="uil uil-eye-slash pw_hide"></i>
            </div>
            <div class="input_box">
              <input type="password" name="confirm_password" placeholder="Нууц үг давтах" required />
              <i class="uil uil-lock password"></i>
              <i class="uil uil-eye-slash pw_hide"></i>
            </div>
            <button class="button" type="submit">Бүртгүүлэх</button>
            <div class="login_signup">Бүргэлтэй юу? <a href="#" id="login">Нэвтрэх</a></div>
          </form>
        </div>
      </div>
    </section>
    <!-- Services Section -->
    <section id="services" class="content_section">
      <h2>
        <center>Үйлчилгээний мэдээлэл</center>
      </h2>
      <div class="price_card">
        <?php foreach ($services as $service): ?>
          <div class="service_card" data-name="<?= $service['service_name'] ?>">
            <h3><?= $service['service_name'] ?></h3>
            <ul>
              <?php
              $car_types = explode(', ', $service['car_types']);
              $prices = explode(', ', $service['prices']);
              foreach ($car_types as $index => $car_type):
                ?>
                <li><?= $car_type ?> - <?= $prices[$index] ?>₮</li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>
      </div>




    </section>



    <!-- <section id="contact" class="content_section">
  <h2>Contact Us</h2>
  <p>Email: contact@carwash.com | Phone: +123 456 7890</p>
</section>

<section id="about" class="content_section">
  <h2>About Us</h2>
  <p>We are dedicated to keeping your car sparkling clean and well-maintained.</p>
</section> -->
    <!-- Footer -->
    <footer class="footer">
      <div class="footer_container">
        <p>&copy; 2024 Car Wash. All rights reserved.</p>
        <!-- <ul class="footer_links">
      <li><a href="#services">Services</a></li>
      <li><a href="#contact">Contact</a></li>
      <li><a href="#about">About</a></li>
      <li><a href="#">Privacy Policy</a></li>
    </ul> -->
        <div class="social_links">
          <a href="#"><i class="uil uil-facebook-f"></i></a>
          <a href="#"><i class="uil uil-twitter"></i></a>
          <a href="#"><i class="uil uil-instagram"></i></a>
        </div>
      </div>
      <!-- <script id="_waua80">var _wau = _wau || []; _wau.push(["small", "36jamcf7pb", "a80"]);</script><script async src="//waust.at/s.js"></script> -->
    </footer>

    <script src="js/script.js">
      document.querySelectorAll('.nav_link').forEach(link => {
        link.addEventListener('click', function (e) {
          e.preventDefault();
          const targetId = this.getAttribute('href').substring(1);
          const targetElement = document.getElementById(targetId);

          if (targetElement) {
            targetElement.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
          }
        });
      });
      const emailInput = document.querySelector('input[name="email"]');
      const rememberCheckbox = document.getElementById("check");

      // Save email to localStorage when checkbox is checked
      rememberCheckbox.addEventListener("change", () => {
        if (rememberCheckbox.checked) {
          localStorage.setItem("savedEmail", emailInput.value);
        } else {
          localStorage.removeItem("savedEmail");
        }
      });

      // Load saved email on page load
      document.addEventListener("DOMContentLoaded", () => {
        const savedEmail = localStorage.getItem("savedEmail");
        if (savedEmail) {
          emailInput.value = savedEmail;
          rememberCheckbox.checked = true;
        }
      });

    </script>
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        const modal = document.getElementById("serviceModal");
        const closeModal = document.querySelector(".close_modal");
        const serviceCards = document.querySelectorAll(".service_card");

        serviceCards.forEach(card => {
          card.addEventListener("click", () => {
            const serviceName = card.getAttribute("data-name");
            const carTypes = card.getAttribute("data-car-types").split(', ');
            const prices = card.getAttribute("data-prices").split(', ');

            // Populate modal
            document.getElementById("modal_service_name").textContent = serviceName;
            const modalDetails = document.getElementById("modal_details");
            modalDetails.innerHTML = "";

            carTypes.forEach((type, index) => {
              const price = prices[index];
              const listItem = document.createElement("li");
              listItem.textContent = `${type} - $${price}`;
              modalDetails.appendChild(listItem);
            });

            modal.style.display = "block";
          });
        });

        closeModal.addEventListener("click", () => {
          modal.style.display = "none";
        });

        window.addEventListener("click", (event) => {
          if (event.target === modal) {
            modal.style.display = "none";
          }
        });
      });
    </script>


</body>

</html>