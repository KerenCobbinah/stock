<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';

// --- CREATE ---
if (isset($_POST['add'])) {
    $name  = trim($_POST['SupplierName']);
    $email = trim($_POST['ContactEmail']);
    $phone = trim($_POST['Phone']);
    $addr  = trim($_POST['Address']);

    $stmt = $conn->prepare("INSERT INTO Suppliers (SupplierName, ContactEmail, Phone, Address) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $name, $email, $phone, $addr);
    $stmt->execute();
    $stmt->close();

    header("Location: suppliers.php");
    exit;
}

// --- UPDATE ---
if (isset($_POST['edit'])) {
    $id    = intval($_POST['SupplierID']);
    $name  = trim($_POST['SupplierName']);
    $email = trim($_POST['ContactEmail']);
    $phone = trim($_POST['Phone']);
    $addr  = trim($_POST['Address']);

    $stmt = $conn->prepare("UPDATE Suppliers 
                            SET SupplierName=?, ContactEmail=?, Phone=?, Address=? 
                            WHERE SupplierID=?");
    $stmt->bind_param("ssssi", $name, $email, $phone, $addr, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: suppliers.php");
    exit;
}

// --- DELETE ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM Suppliers WHERE SupplierID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: suppliers.php");
    exit;
}

// --- READ ---
$result = $conn->query("SELECT * FROM Suppliers ORDER BY SupplierName");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="fw-bold">📦 Suppliers</h3>
  <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
    <i class="bi bi-plus-circle"></i> Add Supplier
  </button>
</div>

<!-- Search Bar -->
<div class="mb-3">
  <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search suppliers...">
</div>

<table class="table table-hover table-bordered align-middle" id="suppliersTable">
  <thead class="table-dark">
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Email</th>
      <th>Phone</th>
      <th>Address</th>
      <th class="text-center">Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?= $row['SupplierID'] ?></td>
      <td><?= htmlspecialchars($row['SupplierName']) ?></td>
      <td><?= htmlspecialchars($row['ContactEmail']) ?></td>
      <td><?= htmlspecialchars($row['Phone']) ?></td>
      <td><?= htmlspecialchars($row['Address']) ?></td>
      <td class="text-center">
        <button class="btn btn-sm btn-outline-primary" 
                data-bs-toggle="modal" 
                data-bs-target="#editModal"
                data-id="<?= $row['SupplierID'] ?>"
                data-name="<?= htmlspecialchars($row['SupplierName']) ?>"
                data-email="<?= htmlspecialchars($row['ContactEmail']) ?>"
                data-phone="<?= htmlspecialchars($row['Phone']) ?>"
                data-address="<?= htmlspecialchars($row['Address']) ?>">
          <i class="bi bi-pencil-square"></i> Edit
        </button>
        <a class="btn btn-sm btn-outline-danger" 
           href="suppliers.php?delete=<?= $row['SupplierID'] ?>" 
           onclick="return confirm('Delete this supplier?')">
          <i class="bi bi-trash"></i> Delete
        </a>
      </td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<!-- Modal: Add Supplier -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg rounded-3">
      <form method="post">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="bi bi-person-plus"></i> Add Supplier</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="SupplierName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="ContactEmail" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="Phone" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="Address" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add" class="btn btn-success">
            <i class="bi bi-save"></i> Save
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Edit Supplier -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg rounded-3">
      <form method="post">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Supplier</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="SupplierID" id="editID">
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="SupplierName" id="editName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="ContactEmail" id="editEmail" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="Phone" id="editPhone" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="Address" id="editAddress" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="edit" class="btn btn-primary">
            <i class="bi bi-save"></i> Update
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Search + Edit Fill Script -->
<script>
  // Search filter
  document.getElementById("searchInput").addEventListener("keyup", function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll("#suppliersTable tbody tr");
    rows.forEach(row => {
      let text = row.innerText.toLowerCase();
      row.style.display = text.includes(filter) ? "" : "none";
    });
  });

  // Fill edit modal with data
  var editModal = document.getElementById('editModal');
  editModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    document.getElementById('editID').value = button.getAttribute('data-id');
    document.getElementById('editName').value = button.getAttribute('data-name');
    document.getElementById('editEmail').value = button.getAttribute('data-email');
    document.getElementById('editPhone').value = button.getAttribute('data-phone');
    document.getElementById('editAddress').value = button.getAttribute('data-address');
  });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
