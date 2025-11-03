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
// --- DELETE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);

    try {
        $stmt = $conn->prepare("DELETE FROM suppliers WHERE SupplierID=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: suppliers.php?msg=deleted");
        exit;
    } catch (mysqli_sql_exception $e) {
        echo "
        <div class='container mt-4'>
            <div class='alert alert-danger shadow p-4 rounded-3 text-center' 
                 style='background-color:#f8d7da; border-color:#f5c2c7; color:#842029;'>
                <h5 class='fw-bold'>⚠️ Cannot Delete Supplier</h5>
                <p>This supplier has one or more uniform items linked to it. 
                Please delete or reassign those items before removing this supplier.</p>
                <a href='suppliers.php' class='btn btn-success'>Return</a>
            </div>
        </div>";
        include __DIR__ . '/../includes/footer.php';
        exit;
    }
}


// --- READ ---
$result = $conn->query("SELECT * FROM Suppliers ORDER BY SupplierName");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  
<style>
  body {
    background: linear-gradient(135deg, #fac992ff, #fbe7bbff);
    font-family: "Poppins", sans-serif;
    color: #333;
  }

  .table {
    background-color: #f0d8b0ff;
    border-radius: 10px;
    overflow: hidden;
  }

  .table th {
    background-color: #da9236ff !important;
    color: #fff5d1 !important;
  }

  .table-hover tbody tr:hover {
    background-color: #ffe9b6;
  }

  .btn {
    border-radius: 10px;
    font-weight: 500;
  }

  .modal-content {
    border-radius: 15px;
    border: none;
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
    <h3 class="fw-bold text-dark">📦 Supplier Management</h3>
    <button class="btn btn-warning text-dark fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
      <i class="bi bi-plus-circle"></i> Add Supplier
    </button>
  </div>

  <!-- Search Bar -->
  <div class="mb-3">
    <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search suppliers...">
  </div>

  <div class="card shadow-sm rounded-4 border-0">
    <div class="card-body p-0">
      <table class="table table-hover table-bordered align-middle mb-0" id="suppliersTable">
        <thead class="table-dark text-center">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Actions</th>
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
              <button class="btn btn-sm btn-outline-primary me-2" 
                      data-bs-toggle="modal" 
                      data-bs-target="#editModal"
                      data-id="<?= $row['SupplierID'] ?>"
                      data-name="<?= htmlspecialchars($row['SupplierName']) ?>"
                      data-email="<?= htmlspecialchars($row['ContactEmail']) ?>"
                      data-phone="<?= htmlspecialchars($row['Phone']) ?>"
                      data-address="<?= htmlspecialchars($row['Address']) ?>">
                <i class="bi bi-pencil-square"></i>
              </button>

              <button class="btn btn-sm btn-outline-danger" 
                      data-bs-toggle="modal" 
                      data-bs-target="#deleteModal"
                      data-id="<?= $row['SupplierID'] ?>"
                      data-name="<?= htmlspecialchars($row['SupplierName']) ?>">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal: Add Supplier -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg rounded-3">
      <form method="post">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title"><i class="bi bi-person-plus"></i> Add Supplier</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
          <button type="submit" name="add" class="btn btn-warning text-dark fw-semibold">Save</button>
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
          <button type="submit" name="edit" class="btn btn-primary fw-semibold">Update</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Delete Supplier -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg rounded-3">
      <form method="post">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirm Deletion</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="delete_id" id="deleteID">
          <p class="fw-semibold">Are you sure you want to delete <span id="deleteName" class="text-danger"></span>?</p>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-danger fw-semibold">Yes, Delete</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Search filter
  document.getElementById("searchInput").addEventListener("keyup", function() {
    let filter = this.value.toLowerCase();
    document.querySelectorAll("#suppliersTable tbody tr").forEach(row => {
      row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
    });
  });

  // Fill edit modal with data
  const editModal = document.getElementById('editModal');
  editModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    document.getElementById('editID').value = button.getAttribute('data-id');
    document.getElementById('editName').value = button.getAttribute('data-name');
    document.getElementById('editEmail').value = button.getAttribute('data-email');
    document.getElementById('editPhone').value = button.getAttribute('data-phone');
    document.getElementById('editAddress').value = button.getAttribute('data-address');
  });

  // Fill delete modal with data
  const deleteModal = document.getElementById('deleteModal');
  deleteModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    document.getElementById('deleteID').value = button.getAttribute('data-id');
    document.getElementById('deleteName').textContent = button.getAttribute('data-name');
  });
</script>

  
</body>
</html>
<?php include __DIR__ . '/../includes/footer.php'; ?>
