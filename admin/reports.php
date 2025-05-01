<?php
session_start();
if ($_SESSION['role'] !== 'Admin') {
  header("Location: ../index.php");
  exit();
}
include('../db.php');

// Initialize variables
$reportType = isset($_GET['report_type']) ? $_GET['report_type'] : 'income';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$serviceType = isset($_GET['service_type']) ? $_GET['service_type'] : '';
$paymentType = isset($_GET['payment_type']) ? $_GET['payment_type'] : '';

// Set the number of items per page
$itemsPerPage = 9;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

// Initialize SQL queries
// Initialize SQL queries
$sql = "";
$totalSql = "";
$currentReport = 'main';

// Determine which report to show
if ($reportType == 'income') {
    if (!empty($serviceType)) {
        $currentReport = 'service';
        $sql = "SELECT 
            DATE(jobs.created_at) AS job_date,
            services.service_name,
            COUNT(*) AS service_count,
            SUM(services.price) AS total_price
        FROM jobs
        JOIN services ON jobs.service_id = services.id";
        
        // Fixed total query for service type
        $totalSql = "SELECT COUNT(*) as total FROM (
            SELECT 1 FROM jobs
            JOIN services ON jobs.service_id = services.id";
        
        // Add service filter only if not "Бүгд"
        if ($serviceType != 'Бүгд') {
            $sql .= " WHERE services.service_name = '$serviceType'";
            $totalSql .= " WHERE services.service_name = '$serviceType'";
        }
        
        // Add date filters if provided
        if (!empty($startDate) && !empty($endDate)) {
            $connector = ($serviceType != 'Бүгд') ? ' AND' : ' WHERE';
            $sql .= "$connector DATE(jobs.created_at) BETWEEN '$startDate' AND '$endDate'";
            $totalSql .= "$connector DATE(jobs.created_at) BETWEEN '$startDate' AND '$endDate'";
        }
        
        $sql .= " GROUP BY job_date, services.service_name ORDER BY job_date DESC";
        $totalSql .= " GROUP BY DATE(jobs.created_at), services.service_name) AS derived";
    } 
    elseif (!empty($paymentType)) {
        $currentReport = 'payment';
        $sql = "SELECT 
            DATE(jobs.created_at) AS job_date,
            jobs.payment,
            COUNT(*) AS payment_count,
            SUM(services.price) AS total_price
        FROM jobs
        JOIN services ON jobs.service_id = services.id";
        
        // Fixed total query for payment type
        $totalSql = "SELECT COUNT(*) as total FROM (
            SELECT 1 FROM jobs
            JOIN services ON jobs.service_id = services.id";
        
        // Add payment filter only if not "Бүгд"
        if ($paymentType != 'Бүгд') {
            $sql .= " WHERE jobs.payment = '$paymentType'";
            $totalSql .= " WHERE jobs.payment = '$paymentType'";
        }
        
        // Add date filters if provided
        if (!empty($startDate) && !empty($endDate)) {
            $connector = ($paymentType != 'Бүгд') ? ' AND' : ' WHERE';
            $sql .= "$connector DATE(jobs.created_at) BETWEEN '$startDate' AND '$endDate'";
            $totalSql .= "$connector DATE(jobs.created_at) BETWEEN '$startDate' AND '$endDate'";
        }
        
        $sql .= " GROUP BY job_date, jobs.payment ORDER BY job_date DESC";
        $totalSql .= " GROUP BY DATE(jobs.created_at), jobs.payment) AS derived";
    } 
    else {
        // Main income report
        $sql = "SELECT 
            DATE(salary.created_at) AS created_date,
            SUM(salary.total_price) AS total_price,
            salary.id
        FROM salary
        WHERE 1=1";
        
        $totalSql = "SELECT COUNT(DISTINCT DATE(created_at)) as total FROM salary WHERE 1=1";
        
        // Add date filters if provided
        if (!empty($startDate) && !empty($endDate)) {
            $sql .= " AND DATE(salary.created_at) BETWEEN '$startDate' AND '$endDate'";
            $totalSql .= " AND DATE(created_at) BETWEEN '$startDate' AND '$endDate'";
        }
        
        $sql .= " GROUP BY DATE(salary.created_at) ORDER BY created_date DESC";
    }
} 
else {
    // Salary report
    $sql = "SELECT
        users.full_name,
        DATE(salary.created_at) AS created_date,
        SUM(salary.total_price) AS total_price,
        salary.id
    FROM salary
    JOIN users ON salary.user_id = users.id
    WHERE 1=1";
    
    // Fixed total query for salary report
    $totalSql = "SELECT COUNT(*) as total FROM (
        SELECT 1 FROM salary
        JOIN users ON salary.user_id = users.id
        WHERE 1=1";
    
    // Add date filters if provided
    if (!empty($startDate) && !empty($endDate)) {
        $sql .= " AND DATE(salary.created_at) BETWEEN '$startDate' AND '$endDate'";
        $totalSql .= " AND DATE(salary.created_at) BETWEEN '$startDate' AND '$endDate'";
    }
    
    $sql .= " GROUP BY DATE(salary.created_at), users.full_name ORDER BY created_date DESC";
    $totalSql .= " GROUP BY DATE(salary.created_at), users.full_name) AS derived";
}

// Add pagination to main query
if (!empty($sql)) {
    $sql .= " LIMIT $offset, $itemsPerPage";
}

// Execute queries
if (!empty($sql) && !empty($totalSql)) {
    $result = $conn->query($sql);
    $totalResult = $conn->query($totalSql);
    
    if ($totalResult) {
        $totalRow = $totalResult->fetch_assoc();
        $totalItems = $totalRow['total'];
        $totalPages = ceil($totalItems / $itemsPerPage);
    } else {
        $totalItems = 0;
        $totalPages = 1;
    }
} else {
    $result = false;
    $totalItems = 0;
    $totalPages = 1;
}

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
  require_once('../tcpdf/tcpdf.php');

  // Create new PDF document
  $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

  // Set document information
  $pdf->SetCreator(PDF_CREATOR);
  $pdf->SetAuthor('Машин угаалга');
  $pdf->SetTitle('Тайлан');
  date_default_timezone_set('Asia/Ulaanbaatar');
  // Set default header data
  $pdf->SetHeaderData('', 0, 'Машин угаалга - Тайлан', 'Огноо: ' . date('Y-m-d, H:i:s'));

  // Set header and footer fonts
  $pdf->SetFont('dejavusans', '', 10);
  $pdf->setHeaderFont(array('dejavusans', '', PDF_FONT_SIZE_MAIN));
  $pdf->setFooterFont(array('dejavusans', '', PDF_FONT_SIZE_DATA));

  // Set default monospaced font
  $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

  // Set margins
  $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
  $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
  $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

  // Set auto page breaks
  $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

  // Set image scale factor
  $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

  // Add a page
  $pdf->AddPage();

  // Set font
  $pdf->SetFont('dejavusans', '', 10);
  $exportSql = str_replace(" LIMIT $offset, $itemsPerPage", "", $sql);
  $exportResult = $conn->query($exportSql);
  // Generate report content
  $html = '';

  if ($reportType == 'income') {
    if ($currentReport == 'service') {
        $html .= '<h2>Үйлчилгээний төрлөөр тайлан: ' . $serviceType . '</h2>';
        $html .= '<table border="1" cellpadding="4">';
        $html .= '<tr><th>№</th><th>Огноо</th><th>Үйлчилгээ</th><th>Тоо</th><th>Нийт дүн</th></tr>';

        $totalSum = 0;
        if ($exportResult->num_rows > 0) {
            $counter = 1;
            while ($row = $exportResult->fetch_assoc()) {
                $html .= '<tr>';
                $html .= '<td>' . $counter . '</td>';
                $html .= '<td>' . $row['job_date'] . '</td>';
                $html .= '<td>' . $row['service_name'] . '</td>';
                $html .= '<td>' . $row['service_count'] . '</td>';
                $html .= '<td>' . number_format($row['total_price'], 0, '.', ',') . '₮</td>';
                $html .= '</tr>';
                $totalSum += $row['total_price'];
                $counter++;
            }

            $html .= '<tr>';
            $html .= '<td colspan="4" align="left"><strong>Нийт:</strong></td>';
            $html .= '<td><strong>' . number_format($totalSum, 0, '.', ',') . '₮</strong></td>';
            $html .= '</tr>';
        } else {
            $html .= '<tr><td colspan="5">Мэдээлэл байхгүй..</td></tr>';
        }
        $html .= '</table>';
    }
    elseif ($currentReport == 'payment') {
        $html .= '<h2>Төлбөрийн төрлөөр тайлан: ' . $paymentType . '</h2>';
        $html .= '<table border="1" cellpadding="4">';
        $html .= '<tr><th>№</th><th>Огноо</th><th>Төлбөрийн төрөл</th><th>Тоо</th><th>Нийт дүн</th></tr>';

        $totalSum = 0;
        if ($exportResult->num_rows > 0) {
            $counter = 1;
            while ($row = $exportResult->fetch_assoc()) {
                $html .= '<tr>';
                $html .= '<td>' . $counter . '</td>';
                $html .= '<td>' . $row['job_date'] . '</td>';
                $html .= '<td>' . $row['payment'] . '</td>';
                $html .= '<td>' . $row['payment_count'] . '</td>';
                $html .= '<td>' . number_format($row['total_price'], 0, '.', ',') . '₮</td>';
                $html .= '</tr>';
                $totalSum += $row['total_price'];
                $counter++;
            }

            $html .= '<tr>';
            $html .= '<td colspan="4" align="left"><strong>Нийт:</strong></td>';
            $html .= '<td><strong>' . number_format($totalSum, 0, '.', ',') . '₮</strong></td>';
            $html .= '</tr>';
        } else {
            $html .= '<tr><td colspan="5">Мэдээлэл байхгүй..</td></tr>';
        }
        $html .= '</table>';
    }
    else {
        $html = '<h2>Орлогын тайлан</h2>';
        $html .= '<table border="1" cellpadding="4">';
        $html .= '<tr><th>№</th><th>Огноо</th><th>Нийт дүн</th></tr>';

        $totalSum = 0;
        $counter = 1;
        if ($exportResult->num_rows > 0) {
            while ($row = $exportResult->fetch_assoc()) {
                $html .= '<tr>';
                $html .= '<td>' . $counter . '</td>';
                $html .= '<td>' . $row['created_date'] . '</td>';
                $html .= '<td>' . number_format($row['total_price'], 0, '.', ',') . '₮</td>';
                $html .= '</tr>';
                $totalSum += $row['total_price'];
                $counter++;
            }

            $html .= '<tr>';
            $html .= '<td colspan="2" align="left"><strong>Нийт:</strong></td>';
            $html .= '<td><strong>' . number_format($totalSum, 0, '.', ',') . '₮</strong></td>';
            $html .= '</tr>';
        } else {
            $html .= '<tr><td colspan="3">Мэдээлэл байхгүй..</td></tr>';
        }
        $html .= '</table>';
    }
}
elseif ($reportType == 'salary') {
    $html .= '<h2>Ажилчдын ажлын хөлсийн тайлан</h2>';
    $html .= '<table border="1" cellpadding="4">';
    $html .= '<tr><th>№</th><th>Ажилчдын нэр</th><th>Огноо</th><th>Нийт дүн</th></tr>';

    $totalSum = 0;
    if ($exportResult->num_rows > 0) {
        $counter = 1;
        while ($row = $exportResult->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $counter . '</td>';
            $html .= '<td>' . $row['full_name'] . '</td>';
            $html .= '<td>' . $row['created_date'] . '</td>';
            $html .= '<td>' . number_format($row['total_price'], 0, '.', ',') . '₮</td>';
            $html .= '</tr>';
            $totalSum += $row['total_price'];
            $counter++;
        }

        $html .= '<tr>';
        $html .= '<td colspan="3" align="left"><strong>Нийт:</strong></td>';
        $html .= '<td><strong>' . number_format($totalSum, 0, '.', ',') . '₮</strong></td>';
        $html .= '</tr>';
    } else {
        $html .= '<tr><td colspan="4">Мэдээлэл байхгүй..</td></tr>';
    }
    $html .= '</table>';
}


  // Output the HTML content
  $pdf->writeHTML($html, true, false, true, false, '');

  // Close and output PDF document
  $pdf->Output('report_' . date('YmdHis') . '.pdf', 'I');
  exit();
}

// Handle Excel export
// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
  header('Content-Type: application/vnd.ms-excel');
  header('Content-Disposition: attachment;filename="report_' . date('YmdHis') . '.xls"');
  header('Cache-Control: max-age=0');

  echo '<table border="1">';
  $exportSql = str_replace(" LIMIT $offset, $itemsPerPage", "", $sql);
  $exportResult = $conn->query($exportSql);

  if ($reportType == 'income') {
    if ($currentReport == 'service') {
        echo '<tr><th colspan="5">Үйлчилгээний төрлөөр тайлан: ' . $serviceType . '</th></tr>';
        echo '<tr><th>№</th><th>Огноо</th><th>Үйлчилгээ</th><th>Тоо</th><th>Нийт дүн</th></tr>';

        $totalSum = 0;
        if ($exportResult->num_rows > 0) {
            $counter = 1;
            while ($row = $exportResult->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $counter . '</td>';
                echo '<td>' . $row['job_date'] . '</td>';
                echo '<td>' . $row['service_name'] . '</td>';
                echo '<td>' . $row['service_count'] . '</td>';
                echo '<td>' . number_format($row['total_price'], 0, '.', ',') . '₮</td>';
                echo '</tr>';
                $totalSum += $row['total_price'];
                $counter++;
            }

            echo '<tr>';
            echo '<td colspan="4" align="left"><strong>Нийт:</strong></td>';
            echo '<td><strong>' . number_format($totalSum, 0, '.', ',') . '₮</strong></td>';
            echo '</tr>';
        } else {
            echo '<tr><td colspan="5">Мэдээлэл байхгүй..</td></tr>';
        }
    }
    elseif ($currentReport == 'payment') {
        echo '<tr><th colspan="5">Төлбөрийн төрлөөр тайлан: ' . $paymentType . '</th></tr>';
        echo '<tr><th>№</th><th>Огноо</th><th>Төлбөрийн төрөл</th><th>Тоо</th><th>Нийт дүн</th></tr>';

        $totalSum = 0;
        if ($exportResult->num_rows > 0) {
            $counter = 1;
            while ($row = $exportResult->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $counter . '</td>';
                echo '<td>' . $row['job_date'] . '</td>';
                echo '<td>' . $row['payment'] . '</td>';
                echo '<td>' . $row['payment_count'] . '</td>';
                echo '<td>' . number_format($row['total_price'], 0, '.', ',') . '₮</td>';
                echo '</tr>';
                $totalSum += $row['total_price'];
                $counter++;
            }

            echo '<tr>';
            echo '<td colspan="4" align="left"><strong>Нийт:</strong></td>';
            echo '<td><strong>' . number_format($totalSum, 0, '.', ',') . '₮</strong></td>';
            echo '</tr>';
        } else {
            echo '<tr><td colspan="5">Мэдээлэл байхгүй..</td></tr>';
        }
    }
    else {
        echo '<tr><th colspan="3">Орлогын тайлан</th></tr>';
        echo '<tr><th>№</th><th>Огноо</th><th>Нийт дүн</th></tr>';

        $totalSum = 0;
        if ($exportResult->num_rows > 0) {
            $counter = 1;
            while ($row = $exportResult->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $counter . '</td>';
                echo '<td>' . $row['created_date'] . '</td>';
                echo '<td>' . number_format($row['total_price'], 0, '.', ',') . '₮</td>';
                echo '</tr>';
                $totalSum += $row['total_price'];
                $counter++;
            }

            echo '<tr>';
            echo '<td colspan="2" align="left"><strong>Нийт:</strong></td>';
            echo '<td><strong>' . number_format($totalSum, 0, '.', ',') . '₮</strong></td>';
            echo '</tr>';
        } else {
            echo '<tr><td colspan="3">Мэдээлэл байхгүй..</td></tr>';
        }
    }
  }
  elseif ($reportType == 'salary') {
    echo '<tr><th colspan="4">Ажилчдын ажлын хөлсийн тайлан</th></tr>';
    echo '<tr><th>№</th><th>Ажилчдын нэр</th><th>Огноо</th><th>Нийт дүн</th></tr>';

    $totalSum = 0;
    if ($exportResult->num_rows > 0) {
        $counter = 1;
        while ($row = $exportResult->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $counter . '</td>';
            echo '<td>' . $row['full_name'] . '</td>';
            echo '<td>' . $row['created_date'] . '</td>';
            echo '<td>' . number_format($row['total_price'], 0, '.', ',') . '₮</td>';
            echo '</tr>';
            $totalSum += $row['total_price'];
            $counter++;
        }

        echo '<tr>';
        echo '<td colspan="3" align="left"><strong>Нийт:</strong></td>';
        echo '<td><strong>' . number_format($totalSum, 0, '.', ',') . '₮</strong></td>';
        echo '</tr>';
    } else {
        echo '<tr><td colspan="4">Мэдээлэл байхгүй..</td></tr>';
    }
  }

  echo '</table>';
  exit();
}

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <meta charset="UTF-8">
  <title>Admin</title>
  <link rel="stylesheet" href="../css/reports.css">
  <!-- Boxicons CDN Link -->
  <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
  <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css' rel='stylesheet'>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<style>
  nav .search-box .bx-right-arrow-alt {
    position: absolute;
    height: 40px;
    width: 40px;
    top: 50%;
    transform: translateY(-50%);
    border-radius: 4px;
    line-height: 40px;
    text-align: center;
    font-size: 22px;
    transition: all 0.4 ease;
  }

  nav .search-box select {
    width: 100%;
    height: 100%;
    outline: none;
    margin: 0px 0;
    background: #F5F6FA;
    border: 2px solid #EFEEF1;
    border-radius: 6px;
    font-size: 18px;
  }

  select {
    transition: border-color 0.3s;
  }

  select:hover {
    border-color: #888;
  }

  select:focus {
    border-color: #555;
    background-color: #fff;
  }

  select::after {
    content: '▼';
    position: absolute;
    right: 10px;
    pointer-events: none;
  }

  .home-section nav .search-box2 {
    position: relative;
    height: 50px;
    max-width: 550px;
    width: 10%;
    margin: 0 20px;
  }

  nav .search-box2 input {
    height: 100%;
    width: 100%;
    outline: none;
    background: #F5F6FA;
    border: 2px solid #EFEEF1;
    border-radius: 6px;
    font-size: 18px;
    padding: 0 15px;
  }

  nav .search-box2 .bx-search {
    position: absolute;
    height: 40px;
    width: 40px;
    background: #2697FF;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    border-radius: 4px;
    line-height: 40px;
    text-align: center;
    color: #fff;
    font-size: 22px;
    transition: all 0.4 ease;
    cursor: pointer;
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
        <a href="admin.php">
          <i class='bx bx-grid-alt'></i>
          <span class="links_name">Хянах самбар</span>
        </a>
      </li>
      <li>
        <a href="services.php">
          <i class='bx bx-book-alt'></i>
          <span class="links_name">Үйлчилгээ</span>
        </a>
      </li>
      <li>
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
      </li>
      <li>
        <a href="users.php">
          <i class='bx bx-user'></i>
          <span class="links_name">Хэрэглэгч</span>
        </a>
      </li>
      <li>
        <a href="reports.php" class="active">
          <i class='bx bx-pie-chart-alt-2'></i>
          <span class="links_name">Тайлан</span>
        </a>
      </li>
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
        <span class="dashboard"><b>Тайлан</b></span>
      </div>

      <form style="display: flex;" method="get" action="reports.php">
        <div class="search-box">
          <input type="date" name="start_date" id="start_date" value="<?php echo $startDate; ?>">
          <i class='bx bx-right-arrow-alt bx-flashing' ></i>
        </div>
        <div class="search-box">
          <input type="date" name="end_date" id="end_date" value="<?php echo $endDate; ?>">
        </div>
        <div class="search-box">
          <select name="report_type" id="report_type" onchange="this.form.submit()">
            <option value="income" <?php echo $reportType == 'income' ? 'selected' : ''; ?>>Орлогын тайлан</option>
            <option value="salary" <?php echo $reportType == 'salary' ? 'selected' : ''; ?>>Ажилчдын ажлын хөлсийн тайлан
            </option>
          </select>
        </div>
        <div class="search-box2">
          <button type="submit" id="search" style="background: none; border: none;">
            <i class='bx bx-search'></i>
          </button>
        </div>
      </form>

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
          <div class="title">
            <b>Нийт:</b> <?php echo $totalItems; ?>
          </div>
          <div class="export-buttons">
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>"
              style="width: 70px; height: 30px; margin-bottom:10px; cursor: pointer; background: palevioletred; border-radius: 3px; display: inline-block; text-align: center; line-height: 30px; color: white; text-decoration: none;">
              <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>"
              style="width: 70px; height: 30px; margin-bottom:10px; cursor: pointer; background: lightgreen; border-radius: 3px; display: inline-block; text-align: center; line-height: 30px; color: white; text-decoration: none;">
              <i class="fa-solid fa-file-excel"></i> EXCEL
            </a>
          </div>

          <div class="sales-details">
            <table>
              <thead>
                <tr>
                  <?php if ($currentReport == 'service'): ?>
                    <th><b>№</b></th>
                    <th><b>Огноо</b></th>
                    <th><b>Үйлчилгээ</b></th>
                    <th><b>Тоо</b></th>
                    <th><b>Нийт дүн</b></th>
                  <?php elseif ($currentReport == 'payment'): ?>
                    <th><b>№</b></th>
                    <th><b>Огноо</b></th>
                    <th><b>Төлбөрийн төрөл</b></th>
                    <th><b>Тоо</b></th>
                    <th><b>Нийт дүн</b></th>
                  <?php else: ?>
                    <th><b>№</b></th>
                    <?php if ($reportType == 'salary'): ?>
                      <th><b>Ажилчдын нэр</b></th>
                    <?php endif; ?>
                    <th><b>Огноо</b></th>
                    <th><b>Нийт дүн</b></th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                  $counter = ($page - 1) * $itemsPerPage + 1;
                  while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$counter}</td>";

                    if ($currentReport == 'service') {
                      echo "<td>{$row['job_date']}</td>";
                      echo "<td>{$row['service_name']}</td>";
                      echo "<td>{$row['service_count']}</td>";
                      echo "<td>" . $row['total_price'] . "₮</td>";
                    } elseif ($currentReport == 'payment') {
                      echo "<td>{$row['job_date']}</td>";
                      echo "<td>{$row['payment']}</td>";
                      echo "<td>{$row['payment_count']}</td>";
                      echo "<td>" . $row['total_price'] . "₮</td>";
                    } else {
                      if ($reportType == 'salary') {
                        echo "<td>{$row['full_name']}</td>";
                      }
                      echo "<td>{$row['created_date']}</td>";
                      echo "<td>" . $row['total_price'] . "₮</td>";
                    }

                    echo "</tr>";
                    $counter++;
                  }
                } else {
                  $colspan = 5;
                  if ($currentReport == 'main') {
                    $colspan = ($reportType == 'salary') ? 4 : 3;
                  }
                  echo "<tr><td colspan='{$colspan}'>Мэдээлэл байхгүй..</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>

          <div class="pagination">
            <?php
            // Display previous page link
            if ($page > 1) {
              echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $page - 1])) . '" class="prev">Өмнөх</a>';
            }

            // Display page numbers
            for ($i = 1; $i <= $totalPages; $i++) {
              if ($i == $page) {
                echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '" class="active">' . $i . '</a>';
              } else {
                echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '">' . $i . '</a>';
              }
            }

            // Display next page link
            if ($page < $totalPages) {
              echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $page + 1])) . '" class="next">Дараах</a>';
            }
            ?>
          </div>
        </div>

        <?php if ($reportType == 'income'): ?>
          <div class="top-sales box">
            <div class="title"><b>Үйлчилгээний төрлөөр</b></div>
            <div class="add-service-form">
              <form method="get" action="reports.php">
                <input type="hidden" name="report_type" value="income">
                <input type="hidden" name="start_date" value="<?php echo $startDate; ?>">
                <input type="hidden" name="end_date" value="<?php echo $endDate; ?>">
                <select required name="service_type" id="service_type">
                <option value="Төрөл сонгох" disabled selected >Төрөл сонгох</option>

                  <option value="Бүгд" <?php echo $serviceType == 'Бүгд' ? 'selected' : ''; ?>>Бүгд</option>
                  <option value="Бүтэн угаалга" <?php echo $serviceType == 'Бүтэн угаалга' ? 'selected' : ''; ?>>Бүтэн
                    угаалга</option>
                  <option value="Гадна угаалга" <?php echo $serviceType == 'Гадна угаалга' ? 'selected' : ''; ?>>Гадна
                    угаалга</option>
                  <option value="Ченж угаалга" <?php echo $serviceType == 'Ченж угаалга' ? 'selected' : ''; ?>>Ченж угаалга
                  </option>
                </select>
                <input type="submit" value="Хайх">
              </form>
            </div>
            <br>
            <div class="title"><b>Төлбөрийн төрлөөр</b></div>
            <div class="add-service-form">
              <form method="get" action="reports.php">
                <input type="hidden" name="report_type" value="income">
                <input type="hidden" name="start_date" value="<?php echo $startDate; ?>">
                <input type="hidden" name="end_date" value="<?php echo $endDate; ?>">
                <select name="payment_type" id="payment_type">
                <option value="Төрөл сонгох" disabled selected >Төрөл сонгох</option>
                  <option value="Бүгд" <?php echo $paymentType == 'Бүгд' ? 'selected' : ''; ?>>Бүгд</option>
                  <option value="Бэлэн" <?php echo $paymentType == 'Бэлэн' ? 'selected' : ''; ?>>Бэлэн</option>
                  <option value="Данс" <?php echo $paymentType == 'Данс' ? 'selected' : ''; ?>>Данс</option>
                  <option value="Карт" <?php echo $paymentType == 'Карт' ? 'selected' : ''; ?>>Карт</option>
                </select>
                <input type="submit" value="Хайх">
              </form>
            </div>
          </div>
        <?php endif; ?>
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

    function confirmLogout() {
      return confirm("Та системээс гарахдаа итгэлтэй байна уу?");
    }
  </script>
</body>

</html>
<?php
$conn->close(); // Close the database connection
?>