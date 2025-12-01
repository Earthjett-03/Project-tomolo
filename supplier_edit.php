<?php
include 'connection.php';
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่ามี id ถูกส่งมาหรือไม่
if (!isset($_GET['id'])) {
    header("Location: suppliers.php");
    exit();
}

$supplier_id = $_GET['id'];

// ดึงข้อมูลซัพพลายเออร์ตาม id
$sql = "SELECT * FROM suppliers WHERE supplier_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('ไม่พบข้อมูลซัพพลายเออร์นี้'); window.location='suppliers.php';</script>";
    exit();
}

$supplier = $result->fetch_assoc();

// เมื่อกดบันทึก
if (isset($_POST['update'])) {
    $name = $_POST['supplier_name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];

    $sql_update = "UPDATE suppliers SET supplier_name = ?, address = ?, phone = ? WHERE supplier_id = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("sssi", $name, $address, $phone, $supplier_id);

    if ($stmt->execute()) {
        echo "<script>alert('อัปเดตข้อมูลเรียบร้อย'); window.location='suppliers.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาด กรุณาลองใหม่');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลซัพพลายเออร์</title>
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

<div class="container mt-5">
  <div class="card shadow">
    <div class="card-header bg-warning text-white">
      <h4>แก้ไขข้อมูลซัพพลายเออร์</h4>
    </div>
    <div class="card-body">
      <form method="POST">
        <div class="mb-3">
          <label class="form-label">ชื่อซัพพลายเออร์</label>
          <input type="text" name="supplier_name" class="form-control" value="<?= $supplier['supplier_name'] ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">ที่อยู่</label>
          <textarea name="address" class="form-control" rows="3"><?= $supplier['address'] ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">เบอร์โทร</label>
          <input type="text" name="phone" class="form-control" value="<?= $supplier['phone'] ?>">
        </div>
        <button type="submit" name="update" class="btn btn-success">บันทึกการแก้ไข</button>
        <a href="suppliers.php" class="btn btn-secondary">ยกเลิก</a>
      </form>
    </div>
  </div>
</div>

</body>
</html>
