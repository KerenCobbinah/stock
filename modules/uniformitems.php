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
    $price = floatval($_POST['Price'] ?? 0);
    $supplier = intval($_POST['SupplierID'] ?? 0);

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE UniformItems 
            SET ItemName=?, Size=?, Gender=?, Price=?, SupplierID=? 
            WHERE ItemID=?");
        $stmt->bind_param("sssddi", $name, $size, $gender, $price, $supplier, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO UniformItems (ItemName, Size, Gender, Price, SupplierID) 
            VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssdi", $name, $size, $gender, $price, $supplier);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: uniformitems.php");
    exit;
}

// --- DELETE ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM UniformItems WHERE ItemID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: uniformitems.php");
    exit;
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

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold">👕 Uniform Items</h3>
        <input type="text" id="searchInput" class="form-control w-25" placeholder="🔍 Search items...">
    </div>

    <!-- Add / Edit Form -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <?= $editItem ? '✏️ Edit Item' : '➕ Add New Item' ?>
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="ItemID" value="<?= $editItem['ItemID'] ?? '' ?>">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="ItemName" class="form-control" 
                               placeholder="Item Name" 
                               value="<?= htmlspecialchars($editItem['ItemName'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Size" class="form-control" placeholder="Size (S, M, L)" 
                               value="<?= htmlspecialchars($editItem['Size'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="Gender" class="form-select" required>
                            <option value="">-- Gender --</option>
                            <option value="Boys"   <?= ($editItem['Gender'] ?? '') === 'Boys' ? 'selected' : '' ?>>Boys</option>
                            <option value="Girls"  <?= ($editItem['Gender'] ?? '') === 'Girls' ? 'selected' : '' ?>>Girls</option>
                            <option value="Unisex" <?= ($editItem['Gender'] ?? '') === 'Unisex' ? 'selected' : '' ?>>Unisex</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="Price" class="form-control" placeholder="Price"
                               value="<?= htmlspecialchars($editItem['Price'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <select name="SupplierID" class="form-select" required>
                            <option value="">-- Supplier --</option>
                            <?php while($s = $suppliers->fetch_assoc()): ?>
                                <option value="<?= $s['SupplierID'] ?>" 
                                    <?= ($editItem['SupplierID'] ?? '') == $s['SupplierID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['SupplierName']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <button class="btn btn-success mt-3">
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
        <div class="card-body">
            <table class="table table-hover align-middle" id="itemsTable">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Item</th>
                        <th>Size</th>
                        <th>Gender</th>
                        <th>Price</th>
                        <th>Supplier</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $items->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['ItemID'] ?></td>
                            <td><?= htmlspecialchars($row['ItemName']) ?></td>
                            <td><?= htmlspecialchars($row['Size']) ?></td>
                            <td>
                                <?php if ($row['Gender'] === 'Boys'): ?>
                                    <span class="badge bg-primary">Boys</span>
                                <?php elseif ($row['Gender'] === 'Girls'): ?>
                                    <span class="badge bg-pink text-white">Girls</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Unisex</span>
                                <?php endif; ?>
                            </td>
                            <td>₵<?= number_format($row['Price'], 2) ?></td>
                            <td><?= htmlspecialchars($row['SupplierName'] ?? 'N/A') ?></td>
<td class="text-center">
    <a href="?edit=<?= $row['ItemID'] ?>" class="btn btn-primary me-2">
        <i class="bi bi-pencil-square"></i> Edit
    </a>
    <a href="?delete=<?= $row['ItemID'] ?>" class="btn btn-danger"
       onclick="return confirm('Delete this item?');">
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

<!-- Search Script -->
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
