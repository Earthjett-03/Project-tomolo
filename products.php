<?php
include 'connection.php';
session_start();

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();  
}

// ดึงข้อมูลสำหรับ Filter
$categories = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$suppliers = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name ASC");

// รับค่าจากฟอร์มค้นหา
$search_term = $_GET['search_term'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$supplier_id = $_GET['supplier_id'] ?? '';

// ดึงข้อมูลสินค้า + ประเภท + ซัพพลายเออร์
$sql = "SELECT p.product_id, p.product_name, c.category_name, s.supplier_name,
               p.base_unit, p.sub_unit, p.unit_conversion_rate,
               p.selling_price, p.stock_in_sub_unit, p.reorder_level, p.image_path
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id";

$conditions = [];
$params = [];
$types = '';

if (!empty($search_term)) {
    $conditions[] = "p.product_name LIKE ?";
    $params[] = "%" . $search_term . "%";
    $types .= 's';
}
if (!empty($category_id)) {
    $conditions[] = "p.category_id = ?";
    $params[] = $category_id;
    $types .= 'i';
}
if (!empty($supplier_id)) {
    $conditions[] = "p.supplier_id = ?";
    $params[] = $supplier_id;
    $types .= 'i';
}

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY p.product_id ASC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการคลังสินค้า | Warehouse System</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    
    <style>
        body {
            
            background-color: #f4f6f9;
        }
        /* ลบ linear-gradient ออกเพื่อให้เป็นสีดำตามมาตรฐาน bg-dark ที่คุณต้องการ */
        /* .navbar { background: linear-gradient(...) } */

        .card-box {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            background: white;
        }
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .table tbody td {
            vertical-align: middle;
            font-size: 0.95rem;
        }
        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .badge-soft-danger {
            background-color: #fce8e6;
            color: #d9534f;
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.8rem;
        }
        .badge-soft-success {
            background-color: #d1e7dd;
            color: #0f5132;
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.8rem;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            transition: 0.2s;
        }
        .search-section {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.03);
            margin-bottom: 20px;
        }
    </style>
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

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-dark">จัดการสินค้า</h3>
            <p class="text-muted small mb-0">รายการสินค้าทั้งหมดในระบบคลังสินค้า</p>
        </div>
        <a href="add_product.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg"></i> เพิ่มสินค้าใหม่
        </a>
    </div>

    <div class="search-section">
        <form method="get">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label text-muted small">ค้นหา</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search_term" class="form-control border-start-0 ps-0" placeholder="ระบุชื่อสินค้า..." value="<?= htmlspecialchars($search_term) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small">ประเภทสินค้า</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- ทั้งหมด --</option>
                        <?php mysqli_data_seek($categories, 0); while($c = $categories->fetch_assoc()): ?>
                            <option value="<?= $c['category_id'] ?>" <?= ($category_id == $c['category_id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['category_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small">ซัพพลายเออร์</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">-- ทั้งหมด --</option>
                        <?php mysqli_data_seek($suppliers, 0); while($s = $suppliers->fetch_assoc()): ?>
                            <option value="<?= $s['supplier_id'] ?>" <?= ($supplier_id == $s['supplier_id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['supplier_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> กรอง</button>
                        <a href="products.php" class="btn btn-light border w-50" title="ล้างค่า"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="card-box p-0 overflow-hidden mb-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4" width="5%">ID</th>
                        <th width="8%">รูปภาพ</th>
                        <th width="20%">ชื่อสินค้า</th>
                        <th width="12%">ประเภท</th>
                        <th width="12%">ซัพพลายเออร์</th>
                        <th width="12%" class="text-end">ราคาขาย</th>
                        <th width="15%">คงเหลือ/สถานะ</th>
                        <th width="10%" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php 
                                // คำนวณสต็อก
                                $baseUnitStock = floor($row['stock_in_sub_unit'] / $row['unit_conversion_rate']);
                                $subUnitStock = fmod($row['stock_in_sub_unit'], $row['unit_conversion_rate']);
                                
                                // แสดงผลสต็อก
                                $stockText = "";
                                if ($row['unit_conversion_rate'] > 1 && !empty($row['sub_unit'])) {
                                    $stockText = "{$baseUnitStock} {$row['base_unit']}";
                                    if($subUnitStock > 0) $stockText .= " <span class='text-muted small'>({$subUnitStock} {$row['sub_unit']})</span>";
                                } else {
                                    $stockText = "{$row['stock_in_sub_unit']} {$row['base_unit']}";
                                }

                                // ตรวจสถานะสินค้าใกล้หมด
                                $isLowStock = $row['stock_in_sub_unit'] <= $row['reorder_level'];
                            ?>
                            <tr class="<?= $isLowStock ? 'bg-light' : '' ?>">
                                <td class="ps-4 text-muted">#<?= $row['product_id'] ?></td>
                                <td>
                                    <?php if (!empty($row['image_path'])): ?>
                                        <img src="<?= $row['image_path'] ?>" alt="img" class="product-img">
                                    <?php else: ?>
                                        <div class="product-img d-flex align-items-center justify-content-center bg-light text-muted">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['product_name']) ?></div>
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        1 <?= $row['base_unit'] ?> = <?= $row['unit_conversion_rate'] . ' ' . $row['sub_unit'] ?>
                                    </small>
                                </td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal"><?= htmlspecialchars($row['category_name'] ?? '-') ?></span></td>
                                <td class="text-muted small"><?= htmlspecialchars($row['supplier_name'] ?? '-') ?></td>
                                <td class="text-end fw-bold text-primary"><?= number_format($row['selling_price'], 2) ?></td>
                                <td>
                                    <div><?= $stockText ?></div>
                                    <?php if($isLowStock): ?>
                                        <span class="badge-soft-danger mt-1 d-inline-block">
                                            <i class="bi bi-exclamation-circle-fill"></i> ใกล้หมด
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-soft-success mt-1 d-inline-block">
                                            <i class="bi bi-check-circle-fill"></i> ปกติ
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="product_edit.php?id=<?= $row['product_id'] ?>" class="btn btn-outline-warning btn-sm" title="แก้ไข">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="product_delete.php?id=<?= $row['product_id'] ?>" 
                                           onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบ <?= htmlspecialchars($row['product_name']) ?> ?');" 
                                           class="btn btn-outline-danger btn-sm" title="ลบ">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                ไม่พบข้อมูลสินค้าที่ค้นหา
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top bg-light text-end text-muted small">
            แสดงทั้งหมด <?= $result->num_rows ?> รายการ
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>