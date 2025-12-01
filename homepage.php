<?php
include 'connection.php';
session_start();

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();  
}

// ยอดขายรวมของเดือนปัจจุบัน
$current_month_sales = $conn->query("SELECT IFNULL(SUM(total_amount), 0) AS total FROM sales WHERE DATE_FORMAT(sale_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')")->fetch_assoc()['total'];

// ยอดซื้อรวมของเดือนปัจจุบัน
$current_month_purchases = $conn->query("SELECT IFNULL(SUM(total_amount), 0) AS total FROM purchases WHERE DATE_FORMAT(purchase_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')")->fetch_assoc()['total'];

// เดือนปัจจุบันสำหรับแสดงผล
$current_month_thai = date('m/Y');


?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการคลังสินค้า | Warehouse System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    
</head>
<body>

      

 <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#"><i class="bi bi-box-seam-fill"></i> Warehouse System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto"> 
                <li class="nav-item"><a class="nav-link" href="homepage.php">หน้าแรก</a></li>
                <li class="nav-item"><a class="nav-link" href="categories.php">ประเภทสินค้า</a></li>          
                <li class="nav-item"><a class="nav-link" href="suppliers.php">ซัพพลายเออร์</a></li>
                <li class="nav-item"><a class="nav-link" href="products.php">สินค้า</a></li>
                <li class="nav-item"><a class="nav-link" href="warehouse_page.php">รายงานบิลสินค้า</a></li>
                <li class="nav-item"><a class="nav-link" href="report.php">รายงาน</a></li>
                
                <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></li>
            </ul>
        </div>
    </div>
</nav>

  <!-- เนื้อหาหลัก -->
  <div class="container my-5">
    <h1 class="mb-4">ระบบจัดการคลังสินค้า</h1>

    <div class="row mb-5">
      <div class="col-md-6 mb-3">
        <div class="card bg-success text-white shadow-sm">
          <div class="card-body">
            <h5 class="card-title">ยอดขายรวมเดือน <?= $current_month_thai ?></h5>
            <p class="card-text fs-3"><?= number_format($current_month_sales, 2) ?> บาท</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 mb-3">
        <div class="card bg-danger text-white shadow-sm">
          <div class="card-body">
            <h5 class="card-title">ยอดซื้อรวมเดือน <?= $current_month_thai ?></h5>
            <p class="card-text fs-3"><?= number_format($current_month_purchases, 2) ?> บาท</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ตารางสินค้าใกล้หมด -->
    <h3 class="mt-4">สินค้าใกล้หมด</h3>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>ชื่อสินค้า</th>
          <th>จำนวนคงเหลือ</th>
          <th>ซัพพลายเออร์</th>
        </tr>
      </thead>
      <tbody>
        <?php
      // สมมติว่าตาราง products มีคอลัมน์ตามที่แนะนำไปแล้ว
      // stock_in_sub_unit, base_unit, sub_unit, unit_conversion_rate
      $sql = "SELECT 
                p.product_id, 
                p.product_name, 
                p.stock_in_sub_unit, 
                p.reorder_level,
                p.base_unit,
                p.sub_unit,
                p.unit_conversion_rate,
                s.supplier_name
              FROM products p -- เปลี่ยนจาก LEFT JOIN เป็น INNER JOIN
              INNER JOIN suppliers s ON p.supplier_id = s.supplier_id
              WHERE p.stock_in_sub_unit <= p.reorder_level AND p.supplier_id IS NOT NULL
              ORDER BY p.stock_in_sub_unit ASC";

      $result = $conn->query($sql);

      if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              $displayStock = '';
              // แปลงสต็อกเป็นหน่วยที่เข้าใจง่าย
              if ($row['unit_conversion_rate'] && $row['unit_conversion_rate'] > 1) {
                  $baseUnitStock = floor($row['stock_in_sub_unit'] / $row['unit_conversion_rate']);
                  $subUnitStock = fmod($row['stock_in_sub_unit'], $row['unit_conversion_rate']);
                  $displayStock = "{$baseUnitStock} {$row['base_unit']} / {$subUnitStock} {$row['sub_unit']}";
              } else {
                  $displayStock = "{$row['stock_in_sub_unit']} {$row['base_unit']}";
              }

              echo "<tr>                      
                      <td>{$row['product_name']}</td>
                      <td>{$displayStock}</td>
                      <td>{$row['supplier_name']}</td>
                    </tr>";
          }
      } else {
          echo "<tr><td colspan='3' class='text-center text-muted'>ไม่มีสินค้าใกล้หมด</td></tr>";
      }
        ?>
      </tbody>
    </table>
  </div>

  <!-- Footer 
 <footer class="bg-dark text-white text-center p-3 mt-5">
    © 2025 ระบบจัดการคลังสินค้า - ร้านวัสดุก่อสร้าง
    </footer>
-->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
