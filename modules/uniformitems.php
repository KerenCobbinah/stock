<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';

// --- CREATE / UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['ItemID'] ?? 0);
    $name = trim($_POST['ItemName'] ?? '');
    $size = trim($_POST['Size'] ?? '');
    $gender = trim($_POST['Gender'] ?? '');
    $class = trim($_POST['Class'] ?? '');
    $price = floatval($_POST['Price'] ?? 0);
    $supplier = intval($_POST['SupplierID'] ?? 0);

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE UniformItems 
            SET ItemName=?, Size=?, Gender=?, Class=?, Price=?, SupplierID=? 
            WHERE ItemID=?");
        $stmt->bind_param("ssssddi", $name, $size, $gender, $class, $price, $supplier, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO UniformItems (ItemName, Size, Gender, Class, Price, SupplierID) 
            VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssdi", $name, $size, $gender, $class, $price, $supplier);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: uniformitems.php");
    exit;
}

// --- DELETE ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    try {
        $stmt = $conn->prepare("DELETE FROM UniformItems WHERE ItemID=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: uniformitems.php?msg=deleted");
        exit;
    } catch (mysqli_sql_exception $e) {
        if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
            echo "
            <div class='container mt-5'>
              <div class='alert alert-warning shadow p-4 rounded-3 text-center'>
                <h5 class='fw-bold'>⚠️ Cannot Delete This Item</h5>
                <p>This item is linked to stock records. Please delete or adjust the related stock entries before removing it.</p>
                <a href='uniformitems.php' class='btn btn-success'>Return to Items</a>
              </div>
            </div>";
            include __DIR__ . '/../includes/footer.php';
            exit;
        } else {
            throw $e;
        }
    }
}

// --- EDIT MODE ---
$editItem = null;
if (isset($_GET['edit'])) {
    $editID = intval($_GET['edit']);
    $res = $conn->prepare("SELECT * FROM UniformItems WHERE ItemID=?");
    $res->bind_param("i", $editID);
    $res->execute();
    $editItem = $res->get_result()->fetch_assoc();
    $res->close();
}

// Fetch all items
$items = $conn->query("SELECT u.*, s.SupplierName 
    FROM UniformItems u 
    LEFT JOIN Suppliers s ON u.SupplierID = s.SupplierID 
    ORDER BY u.ItemName");

// Fetch suppliers
$suppliers = $conn->query("SELECT SupplierID, SupplierName FROM Suppliers ORDER BY SupplierName");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Uniform Items</title>
<style>
body {
  background: linear-gradient(135deg, #fac992ff, #fbe7bbff);
  color: #333;
  font-family: "Poppins", sans-serif;
  overflow-x: hidden; /* prevents page-wide side scroll */
}

h3 {
  color: #000;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
}

/* Table responsiveness fix */
.table-container {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  width: 100%;
}

.table th {
  background-color: #da9236ff !important;
  color: #fff !important;
  text-align: center;
  position: sticky;
  top: 0;
  z-index: 2;
}

.table-hover tbody tr:hover {
  background-color: #e4cf88ff;
}

.form-control, .form-select {
  border-radius: 10px;
  padding: 10px;
}

.btn {
  border-radius: 10px;
  font-weight: 500;
}

#searchInput {
  border-radius: 20px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

#searchInput:focus {
  box-shadow: 0 0 8px rgba(135, 87, 15, 0.84);
}
</style>
</head>
<body>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="fw-bold">👕 Uniform Items</h3>
    <input type="text" id="searchInput" class="form-control w-25" placeholder="🔍 Search items...">
  </div>

  <!-- Add / Edit Form -->
  <div class="card shadow mb-4">
    <div class="card-header text-white" style="background-color:#da9236ff;">
      <?= $editItem ? '✏️ Edit Item' : '➕ Add New Item' ?>
    </div>
    <div class="card-body" style="background-color:#f0d8b0ff;">
      <form method="post">
        <input type="hidden" name="ItemID" value="<?= $editItem['ItemID'] ?? '' ?>">
        <div class="row g-3">
          <div class="col-md-3">
            <input type="text" name="ItemName" class="form-control" placeholder="Item Name"
              value="<?= htmlspecialchars($editItem['ItemName'] ?? '') ?>" required>
          </div>
          <div class="col-md-2">
            <input type="text" name="Size" class="form-control" placeholder="Size (S, M, L)"
              value="<?= htmlspecialchars($editItem['Size'] ?? '') ?>">
          </div>
          <div class="col-md-2">
            <select name="Gender" class="form-select" required>
              <option value="">-- Gender --</option>
              <option value="Boys" <?= ($editItem['Gender'] ?? '') === 'Boys' ? 'selected' : '' ?>>Boys</option>
              <option value="Girls" <?= ($editItem['Gender'] ?? '') === 'Girls' ? 'selected' : '' ?>>Girls</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="Class" class="form-select" required>
              <option value="">-- Class --</option>
              <option value="JHS" <?= ($editItem['Class'] ?? '') === 'JHS' ? 'selected' : '' ?>>JHS</option>
              <option value="LOWER PRIMARY" <?= ($editItem['Class'] ?? '') === 'LOWER PRIMARY' ? 'selected' : '' ?>>LOWER PRIMARY</option>
              <option value="UPPER PRIMARY" <?= ($editItem['Class'] ?? '') === 'UPPER PRIMARY' ? 'selected' : '' ?>>UPPER PRIMARY</option>
              <option value="NURSERY" <?= ($editItem['Class'] ?? '') === 'NURSERY' ? 'selected' : '' ?>>NURSERY</option>
              <option value="CRECHE" <?= ($editItem['Class'] ?? '') === 'CRECHE' ? 'selected' : '' ?>>CRECHE</option>
            </select>
          </div>
          <div class="col-md-2">
            <input type="number" step="5" name="Price" class="form-control" placeholder="Price"
              value="<?= htmlspecialchars($editItem['Price'] ?? '') ?>" required>
          </div>
          <div class="col-md-3">
            <select name="SupplierID" class="form-select" required>
              <option value="">-- Supplier --</option>
              <?php while ($s = $suppliers->fetch_assoc()): ?>
                <option value="<?= $s['SupplierID'] ?>"
                  <?= ($editItem['SupplierID'] ?? '') == $s['SupplierID'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s['SupplierName']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>
        <button class="btn mt-3 text-white" style="background-color:#e38500ff;">
          <i class="bi bi-save"></i> <?= $editItem ? 'Update Item' : 'Save Item' ?>
        </button>
        <?php if ($editItem): ?>
          <a href="uniformitems.php" class="btn btn-secondary mt-3">Cancel</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- Display Table -->
  <div class="card shadow">
    <div class="card-body table-container" style="background-color:#f0d8b0ff;">
      <table class="table table-hover table-bordered align-middle" id="itemsTable">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Item</th>
            <th>Size</th>
            <th>Gender</th>
            <th>Class</th>
            <th>Price</th>
            <th>Supplier</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $items->fetch_assoc()): ?>
            <tr>
              <td><?= $row['ItemID'] ?></td>
              <td><?= htmlspecialchars($row['ItemName']) ?></td>
              <td><?= htmlspecialchars($row['Size']) ?></td>
              <td>
                <?php if ($row['Gender'] === 'Boys'): ?>
                  <span class="badge bg-primary">Boys</span>
                <?php elseif ($row['Gender'] === 'Girls'): ?>
                  <span class="badge text-white" style="background-color:#e38500ff;">Girls</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Unisex</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['Class']) ?></td>
              <td>₵<?= number_format($row['Price'], 2) ?></td>
              <td><?= htmlspecialchars($row['SupplierName'] ?? 'N/A') ?></td>
              <td class="text-center">
                <a href="?edit=<?= $row['ItemID'] ?>" class="btn btn-sm text-white me-2" style="background-color:#da9236ff;">
                  <i class="bi bi-pencil-square"></i> Edit
                </a>
                <a href="?delete=<?= $row['ItemID'] ?>" class="btn btn-sm text-white"
                  style="background-color:#e38500ff;" onclick="return confirm('Delete this item?');">
                  <i class="bi bi-trash"></i> Delete
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
document.getElementById("searchInput").addEventListener("keyup", function() {
  let filter = this.value.toLowerCase();
  let rows = document.querySelectorAll("#itemsTable tbody tr");
  rows.forEach(row => {
    let text = row.innerText.toLowerCase();
    row.style.display = text.includes(filter) ? "" : "none";
  });
});
</script>

</body>
</html>

<?php include __DIR__ . '/../includes/footer.php'; ?>
