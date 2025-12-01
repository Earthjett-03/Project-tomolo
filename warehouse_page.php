<?php
include 'connection.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// รับค่าจากฟอร์มค้นหา
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$search_term = $_GET['search_term'] ?? '';

$params = [];
$types = '';

// สร้าง SQL พื้นฐานด้วย UNION ALL เพื่อรวมบิลซื้อและขาย
$sql = "
    SELECT * FROM (
        (
            SELECT 
                p.purchase_id AS bill_id,
                p.purchase_number AS bill_number,
                p.purchase_date AS bill_date,
                p.total_amount,
                s.supplier_name AS party_name,
                'บิลซื้อ (Purchase)' AS type,
                'purchase_detail.php' AS detail_page,
                'id' AS param_name
            FROM purchases p
            LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
        )
        UNION ALL
        (
            SELECT 
                s.sale_id AS bill_id,
                s.sale_number AS bill_number,
                s.sale_date AS bill_date,
                s.total_amount,
                'ลูกค้าทั่วไป' AS party_name,
                'บิลขาย (Sale)' AS type,
                'sale_detail.php' AS detail_page,
                'sale_id' AS param_name
            FROM sales s
        )
    ) AS combined_bills
    WHERE 1=1
";

// เพิ่มเงื่อนไขการค้นหา
if ($start_date) {
    $sql .= " AND bill_date >= ?";
    $params[] = $start_date;
    $types .= 's';
}
if ($end_date) {
    $sql .= " AND bill_date <= ?";
    $params[] = $end_date;
    $types .= 's';
}
if ($search_term) {
    $sql .= " AND (bill_number LIKE ? OR party_name LIKE ?)";
    $like_term = "%" . $search_term . "%";
    $params[] = $like_term;
    $params[] = $like_term;
    $types .= 'ss';
}

$sql .= " ORDER BY bill_date DESC, bill_id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// ✅ แยกข้อมูลที่ได้จากการค้นหา (หรือข้อมูลทั้งหมด) ไปยังตัวแปรสำหรับแต่ละแท็บ
$bills_in = [];
$bills_out = [];
$total_in = 0;
$total_out = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['type'] == 'บิลซื้อ (Purchase)') {
            $bills_in[] = $row;
            $total_in += $row['total_amount'];
        } else {
            $bills_out[] = $row;
            $total_out += $row['total_amount'];
        }
    }
}

// ปิด statement ที่ไม่ใช้แล้ว
$stmt->close();
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


<style>
body { background-color: #f8f9fa; }
.card-bill {
  margin-top: 20px;
  transition: transform 0.2s, box-shadow 0.2s;
  border-radius: 15px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.card-bill:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 15px rgba(0,0,0,0.2);
}
.card-header {
  background: linear-gradient(#23231A);
  color: white;
  font-weight: 600;
  border-radius: 15px 15px 0 0;
}
.badge-supplier, .badge-customer { background-color: #6c757d; }
</style>
</head>
<body>

<!-- เมนูบน -->
<!-- แถบเมนูด้านบน -->


      

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

<div class="container mt-4 mb-5">
  <h2 class="fw-bold mb-4"> รายการบิลสินค้า</h2>

<!-- ฟอร์มค้นหา -->
    <form method="GET" class="card card-body mb-4">
        <div class="row g-3">
            <div class="col-md-3"><input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>"></div>
            <div class="col-md-3"><input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>"></div>
        <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search_term" class="form-control" placeholder="ค้นหาเลขที่บิล, ซัพพลายเออร์..." value="<?= htmlspecialchars($search_term) ?>">
                    <button class="btn btn-primary" type="submit">ค้นหา</button>
                </div>
            </div>
            <div class="col-md-2 text-end">
        <a href="warehouse_page.php" class="btn btn-dark w-100">-</a>
      </div>
        </div>
        
    </form>

  <!-- แท็บเลือกดู -->
  <ul class="nav nav-tabs" id="billTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="in-tab" data-bs-toggle="tab" data-bs-target="#in" type="button" role="tab">
         บิลรับสินค้าเข้า
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="out-tab" data-bs-toggle="tab" data-bs-target="#out" type="button" role="tab">
         บิลขายสินค้าออก
      </button>
    </li>
  </ul>

  <div class="tab-content mt-3" id="billTabsContent">

    <!-- บิลรับสินค้าเข้า -->
    <div class="tab-pane fade show active" id="in" role="tabpanel">
  <a href="stock_in_add.php" class="btn btn-success mb-3">+ เพิ่มบิลรับสินค้า</a>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>วันที่</th>
                    <th>เลขที่บิล</th>
                    <th>ซัพพลายเออร์</th>
                    <th>ยอดรวม (บาท)</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($bills_in)): ?>
                    <?php foreach ($bills_in as $row): ?>
                        <tr>
                            <td><?= date("d/m/Y", strtotime($row['bill_date'])) ?></td>
                            <td><?= htmlspecialchars($row['bill_number']) ?></td>
                            <td><?= htmlspecialchars($row['party_name']) ?></td>
                            <td class="text-end"><?= number_format($row['total_amount'], 2) ?></td>
                            <td class="text-center">
                                <a href="<?= $row['detail_page'] ?>?<?= $row['param_name'] ?>=<?= $row['bill_id'] ?>" class="btn btn-sm btn-info">ดูรายละเอียด</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">ไม่พบข้อมูลบิลรับสินค้า</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-light">
                    <th colspan="3" class="text-end">ยอดรวมบิลรับเข้าที่แสดง:</th>
                    <th class="text-end"><?= number_format($total_in, 2) ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- บิลขายสินค้าออก -->
    <div class="tab-pane fade" id="out" role="tabpanel">
  <a href="stock_out_add.php" class="btn btn-danger mb-3">+ เพิ่มบิลขายสินค้า</a>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>วันที่</th>
                    <th>เลขที่บิล</th>
                    <th>ยอดรวม (บาท)</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($bills_out)): ?>
                    <?php foreach ($bills_out as $row): ?>
                        <tr>
                            <td><?= date("d/m/Y", strtotime($row['bill_date'])) ?></td>
                            <td><?= htmlspecialchars($row['bill_number']) ?></td>
                            <td class="text-end"><?= number_format($row['total_amount'], 2) ?></td>
                            <td class="text-center">
                                <a href="<?= $row['detail_page'] ?>?<?= $row['param_name'] ?>=<?= $row['bill_id'] ?>" class="btn btn-sm btn-info">ดูรายละเอียด</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">ไม่พบข้อมูลบิลขายสินค้า</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-light">
                    <th colspan="2" class="text-end">ยอดรวมบิลขายออกที่แสดง:</th>
                    <th class="text-end"><?= number_format($total_out, 2) ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
            </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
