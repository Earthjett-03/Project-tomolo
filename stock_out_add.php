<?php
include 'connection.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ดึงรายการสินค้าในสต็อก
$sql = "SELECT product_id, product_name, selling_price, 
               stock_in_sub_unit, base_unit, sub_unit, unit_conversion_rate
        FROM products 
        WHERE stock_in_sub_unit > 0
        ORDER BY product_name ASC";
$result = $conn->query($sql);
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

<div class="container mt-4">
    <h2 class="fw-bold mb-4">เพิ่มบิลขายสินค้า</h2>
    <form action="stock_out_save.php" method="POST" id="sale-form">

        <div class="mb-3 col-md-4">
            <label for="sale_date" class="form-label">วันที่ขาย</label>
            <input type="date" id="sale_date" name="sale_date" class="form-control" required>
        </div>

        <table class="table table-bordered">
            <thead class="table-dark text-center">
                <tr>
                    <th>สินค้า</th>
                    <th style="width: 15%;">หน่วยที่ขาย</th>
                    <th>ราคาขาย</th>
                    <th>จำนวนคงเหลือ</th>
                    <th style="width: 12%;">จำนวนที่ขาย</th>
                    <th>ราคารวม</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="itemBody">
                <tr>
                    <td>
                        <select name="product_id[]" class="form-select product-select" required>
                            <option value="">-- เลือกสินค้า --</option>
                            <?php mysqli_data_seek($result, 0); ?>
                            <?php while ($p = $result->fetch_assoc()): ?>
                                <option value="<?= $p['product_id'] ?>" 
                                        data-price="<?= $p['selling_price'] ?>" 
                                        data-stock-sub-unit="<?= $p['stock_in_sub_unit'] ?>"
                                        data-base-unit="<?= htmlspecialchars($p['base_unit']) ?>"
                                        data-sub-unit="<?= htmlspecialchars($p['sub_unit']) ?>"
                                        data-conv-rate="<?= $p['unit_conversion_rate'] ?>">
                                    <?= htmlspecialchars($p['product_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </td>
                    <td>
                        <select name="sale_unit[]" class="form-select sale-unit" required></select>
                    </td>
                    <td><input type="text" class="form-control price text-end" readonly></td>
                    <td><input type="text" class="form-control stock text-center" readonly></td>
                    <td><input type="number" name="quantity[]" class="form-control quantity text-center" min="1" required></td>
                    <td><input type="text" class="form-control row-total text-end" readonly></td>
                    <td><button type="button" class="btn btn-danger btn-remove">-</button></td>
                </tr>
            </tbody>
        </table>

        <button type="button" id="btnAdd" class="btn btn-secondary">+ เพิ่มแถว</button>
        <button type="submit" class="btn btn-success">บันทึกการขาย</button>
        <a href="warehouse_page.php" class="btn btn-outline-secondary">ยกเลิก</a>

        <div class="mt-3 text-end">
            <h4>ราคารวมทั้งหมด: <span id="totalAmount" class="text-success">0.00</span> บาท</h4>
        </div>
    </form>
</div>

<script>
function addRowListeners(row) {
    const productSelect = row.querySelector('.product-select');
    const unitSelect = row.querySelector('.sale-unit');
    const quantityInput = row.querySelector('.quantity');
    const removeBtn = row.querySelector('.btn-remove');

    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const tr = this.closest('tr');
        
        const price = parseFloat(selectedOption.dataset.price || 0);
        const stockSubUnit = parseFloat(selectedOption.dataset.stockSubUnit || 0);
        const baseUnit = selectedOption.dataset.baseUnit;
        const subUnit = selectedOption.dataset.subUnit;
        const convRate = parseFloat(selectedOption.dataset.convRate || 1);

        tr.querySelector('.price').value = price.toFixed(2);

        // สร้างตัวเลือกหน่วยขาย
        unitSelect.innerHTML = '';
        if (convRate > 1 && subUnit) {
            // มี 2 หน่วย
            unitSelect.add(new Option(baseUnit, baseUnit));
            unitSelect.add(new Option(subUnit, subUnit));
        } else {
            // มีหน่วยเดียว
            unitSelect.add(new Option(baseUnit, baseUnit));
        }
        
        updateStockDisplay(tr);
    });

    unitSelect.addEventListener('change', function() {
        updateStockDisplay(this.closest('tr'));
    });

    quantityInput.addEventListener('input', updateTotals);

    removeBtn.addEventListener('click', () => {
        row.remove();
        updateTotals();
    });
}

function updateStockDisplay(tr) {
    const selectedOption = tr.querySelector('.product-select').options[tr.querySelector('.product-select').selectedIndex];
    const stockSubUnit = parseFloat(selectedOption.dataset.stockSubUnit || 0);
    const baseUnit = selectedOption.dataset.baseUnit;
    const subUnit = selectedOption.dataset.subUnit;
    const convRate = parseFloat(selectedOption.dataset.convRate || 1);
    
    let stockDisplay = '';
    if (convRate > 1 && subUnit) {
        const baseUnitStock = Math.floor(stockSubUnit / convRate);
        const subUnitStock = stockSubUnit % convRate;
        stockDisplay = `${baseUnitStock} ${baseUnit} / ${subUnitStock.toFixed(2)} ${subUnit}`;
    } else {
        stockDisplay = `${stockSubUnit} ${baseUnit}`;
    }
    tr.querySelector('.stock').value = stockDisplay;
}

function updateTotals() {
    let totalAmount = 0;
    document.querySelectorAll('#itemBody tr').forEach(row => {
        const price = parseFloat(row.querySelector('.price').value) || 0;
        const quantity = parseInt(row.querySelector('.quantity').value) || 0;
        const selectedUnit = row.querySelector('.sale-unit').value;
        
        const selectedOption = row.querySelector('.product-select').options[row.querySelector('.product-select').selectedIndex];
        const baseUnit = selectedOption.dataset.baseUnit;
        const convRate = parseFloat(selectedOption.dataset.convRate || 1);

        // คำนวณราคารวมตามหน่วยที่ขาย
        let multiplier = (selectedUnit === baseUnit && convRate > 1) ? convRate : 1;
        const rowTotal = price * quantity * multiplier;

        row.querySelector('.row-total').value = rowTotal.toFixed(2);
        totalAmount += rowTotal;
    });
    document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
}

document.getElementById('btnAdd').addEventListener('click', () => {
    const tbody = document.getElementById('itemBody');
    const firstRow = tbody.querySelector('tr');
    const newRow = firstRow.cloneNode(true);
    newRow.querySelectorAll('input').forEach(input => input.value = '');
    newRow.querySelector('select').selectedIndex = 0;
    tbody.appendChild(newRow);
    addRowListeners(newRow);
});

document.querySelectorAll('#itemBody tr').forEach(addRowListeners);

</script>
</body>
</html>
