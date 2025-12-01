<?php
include 'connection.php';
session_start();

// ตรวจสอบว่าล็อกอินหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ถ้ากดบันทึก
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name   = $_POST['product_name'];
    $category_id    = $_POST['category_id'];
    $selling_price  = $_POST['selling_price'];
    $reorder_level  = $_POST['reorder_level'];
    // รับค่าใหม่สำหรับหน่วย
    $base_unit = $_POST['base_unit'];
    $sub_unit = !empty($_POST['sub_unit']) ? $_POST['sub_unit'] : null;
    $unit_conversion_rate = $_POST['unit_conversion_rate'];
    
    // อัพโหลดรูป
    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $image_path = $targetFilePath;
        }
    }

    // บันทึกลง DB ด้วยคอลัมน์ใหม่
    $stmt = $conn->prepare("INSERT INTO products 
        (product_name, category_id, selling_price, reorder_level, image_path, base_unit, sub_unit, unit_conversion_rate) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sidssssd", 
        $product_name, $category_id, $selling_price, $reorder_level, $image_path, $base_unit, $sub_unit, $unit_conversion_rate
    );

    if ($stmt->execute()) {
        header("Location: products.php?success=1");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มสินค้าใหม่ | Warehouse System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f4f6f9;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0 !important;
        }
        .form-label {
            font-weight: 500;
            color: #495057;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        .form-control, .form-select {
            border-left: none;
            padding: 0.6rem 1rem;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: none;
            border-color: #ced4da;
        }
        /* Style เพื่อให้ input มีขอบครบเมื่อไม่มี group-text */
        .form-control-simple {
            border-left: 1px solid #ced4da;
        }
        
        /* Image Preview Box */
        .image-upload-wrap {
            border: 2px dashed #d1d3e2;
            position: relative;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            background: #fff;
            cursor: pointer;
            transition: all 0.3s;
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .image-upload-wrap:hover {
            background-color: #f8f9fa;
            border-color: #4e73df;
        }
        .preview-img {
            max-width: 100%;
            max-height: 100%;
            display: none;
            border-radius: 8px;
        }
        .upload-content {
            color: #858796;
        }
        .unit-summary-box {
            background-color: #e3f2fd;
            border: 1px solid #90caf9;
            color: #0d47a1;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-top: 10px;
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

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <form method="post" enctype="multipart/form-data" id="addProductForm">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 text-primary"><i class="bi bi-plus-circle-fill"></i> เพิ่มสินค้าใหม่</h4>
                        <a href="products.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <label class="form-label d-block mb-2">รูปภาพสินค้า</label>
                                <div class="image-upload-wrap" onclick="document.getElementById('fileInput').click()">
                                    <div class="upload-content" id="uploadContent">
                                        <i class="bi bi-cloud-arrow-up fs-1"></i>
                                        <p class="mt-2 mb-0">คลิกเพื่ออัปโหลดรูปภาพ</p>
                                    </div>
                                    <img id="imagePreview" class="preview-img" src="#" alt="Preview">
                                </div>
                                <input type="file" name="image" id="fileInput" class="d-none" accept="image/*" onchange="previewImage(this)">
                                <div class="text-center mt-2 text-muted small">* รองรับไฟล์ JPG, PNG</div>
                            </div>

                            <div class="col-md-8">
                                <h5 class="text-secondary mb-3 border-bottom pb-2"><i class="bi bi-info-circle"></i> ข้อมูลทั่วไป</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label">ชื่อสินค้า <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text border"><i class="bi bi-tag"></i></span>
                                            <input type="text" name="product_name" class="form-control" placeholder="ระบุชื่อสินค้า..." required>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text border"><i class="bi bi-folder"></i></span>
                                            <select name="category_id" class="form-select" required>
                                                <option value="">-- เลือกหมวดหมู่ --</option>
                                                <?php
                                                $cat = $conn->query("SELECT category_id, category_name FROM categories");
                                                while ($row = $cat->fetch_assoc()) {
                                                    echo "<option value='{$row['category_id']}'>{$row['category_name']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <h5 class="text-secondary mt-4 mb-3 border-bottom pb-2"><i class="bi bi-boxes"></i> หน่วยนับและราคา</h5>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">หน่วยหลัก (หน่วยใหญ่)</label>
                                        <input type="text" class="form-control form-control-simple rounded" id="base_unit" name="base_unit" placeholder="เช่น ถุง" required oninput="updateSummary()">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">หน่วยย่อย (หน่วยเล็ก)</label>
                                        <input type="text" class="form-control form-control-simple rounded" id="sub_unit" name="sub_unit" placeholder="เช่น กก" oninput="updateSummary()">
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label class="form-label">อัตราส่วน (1 หน่วยหลัก มีกี่หน่วยย่อย)</label>
                                        <input type="number" class="form-control form-control-simple rounded" id="unit_conversion_rate" name="unit_conversion_rate" value="1" step="0.01" required oninput="updateSummary()">
                                    </div>

                                    <div class="col-12">
                                        <div class="unit-summary-box" id="unitSummary">
                                            <i class="bi bi-lightbulb-fill"></i> กรุณากรอกหน่วยนับและอัตราส่วนเพื่อดูสรุป
                                        </div>
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <label class="form-label">ราคาขาย <span class="text-muted small">(ต่อหน่วยย่อย)</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text border">฿</span>
                                            <input type="number" step="0.01" name="selling_price" class="form-control" placeholder="0.00" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <label class="form-label">จุดสั่งซื้อใหม่(จุดเตือนเมื่อของใกล้หมด) <span class="text-muted small">(Alert)</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text border"><i class="bi bi-bell"></i></span>
                                            <input type="number" name="reorder_level" class="form-control" placeholder="จำนวนขั้นต่ำ" required>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div> </div> <div class="card-footer bg-white p-3 text-end">
                        <a href="products.php" class="btn btn-light btn-lg px-4 border">ยกเลิก</a>
                        <button type="submit" name="update" id="update-btn" class="btn btn-success btn-lg px-4 shadow-sm">
                                    <span class="me-1">💾</span> บันทึกการแก้ไข
                                </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // ฟังก์ชันแสดงตัวอย่างรูปภาพ
    function previewImage(input) {
        var preview = document.getElementById('imagePreview');
        var content = document.getElementById('uploadContent');
        
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                content.style.display = 'none';
            }
            
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.style.display = 'none';
            content.style.display = 'block';
        }
    }

    // ฟังก์ชันสรุปหน่วยนับอัตโนมัติ
    function updateSummary() {
        const baseUnit = document.getElementById('base_unit').value;
        const subUnit = document.getElementById('sub_unit').value;
        const rate = document.getElementById('unit_conversion_rate').value;
        const summaryBox = document.getElementById('unitSummary');

        if(baseUnit && subUnit && rate) {
            if(rate == 1) {
                summaryBox.innerHTML = `<i class="bi bi-check-circle-fill"></i> สินค้านี้ขายเป็น <strong>${baseUnit}</strong> (ไม่มีหน่วยย่อย)`;
            } else {
                summaryBox.innerHTML = `<i class="bi bi-arrow-repeat"></i> สรุป: 1 <strong>${baseUnit}</strong> ประกอบด้วย ${rate} <strong>${subUnit}</strong>`;
            }
        } else if (baseUnit && !subUnit) {
             summaryBox.innerHTML = `<i class="bi bi-check-circle-fill"></i> สินค้านี้ขายเป็น <strong>${baseUnit}</strong> เท่านั้น`;
        } else {
            summaryBox.innerHTML = `<i class="bi bi-lightbulb-fill"></i> กรอกข้อมูลให้ครบเพื่อดูสรุปโครงสร้างหน่วยสินค้า`;
        }
    }
</script>

</body>
</html>