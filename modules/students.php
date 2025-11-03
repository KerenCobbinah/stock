<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';

// --- CREATE / UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['StudentID'] ?? 0);
    $fname = trim($_POST['FirstName'] ?? '');
    $lname = trim($_POST['LastName'] ?? '');
    $class = trim($_POST['Class'] ?? '');
    $gender = trim($_POST['Gender'] ?? '');
    $admNo = trim($_POST['AdmissionNumber'] ?? '');

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE Students 
            SET FirstName=?, LastName=?, Class=?, Gender=?, AdmissionNumber=? 
            WHERE StudentID=?");
        $stmt->bind_param("sssssi", $fname, $lname, $class, $gender, $admNo, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO Students (FirstName, LastName, Class, Gender, AdmissionNumber) 
            VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $fname, $lname, $class, $gender, $admNo);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: students.php");
    exit;
}

// --- DELETE ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $conn->prepare("DELETE FROM Students WHERE StudentID=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: students.php?msg=deleted");
    } catch (mysqli_sql_exception $e) {
        // Check for foreign key error
        if (str_contains($e->getMessage(), 'foreign key constraint fails')) {
            header("Location: students.php?error=linked");
        } else {
            header("Location: students.php?error=unknown");
        }
    }
    exit;
}


// --- EDIT MODE ---
$editStudent = null;
if (isset($_GET['edit'])) {
    $editID = intval($_GET['edit']);
    $res = $conn->prepare("SELECT * FROM Students WHERE StudentID=?");
    $res->bind_param("i", $editID);
    $res->execute();
    $editStudent = $res->get_result()->fetch_assoc();
    $res->close();
}

// Fetch all students
$students = $conn->query("SELECT * FROM Students ORDER BY Class, LastName");
?>

<div class="container py-4">
    <?php if (isset($_GET['error']) && $_GET['error'] === 'linked'): ?>
  <div class="alert alert-danger text-center fw-bold">
    ⚠️ Cannot delete this student — they have existing stock transactions.
  </div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
  <div class="alert alert-success text-center fw-bold">
    ✅ Student deleted successfully.
  </div>
<?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="fw-bold">🎓 Students</h3>
    <input type="text" id="searchInput" class="form-control w-25" placeholder="🔍 Search students...">
  </div>

  <!-- Add / Edit Form -->
  <div class="card shadow mb-4">
    <div class="card-header text-white" style="background-color:#d6892d;">
      <?= $editStudent ? '✏️ Edit Student' : '➕ Add New Student' ?>
    </div>
    <div class="card-body bg-light">
      <form method="post">
        <input type="hidden" name="StudentID" value="<?= $editStudent['StudentID'] ?? '' ?>">
        <div class="row g-3">
          <div class="col-md-3">
            <input type="text" name="FirstName" class="form-control" placeholder="First Name"
              value="<?= htmlspecialchars($editStudent['FirstName'] ?? '') ?>" required>
          </div>
          <div class="col-md-3">
            <input type="text" name="LastName" class="form-control" placeholder="Last Name"
              value="<?= htmlspecialchars($editStudent['LastName'] ?? '') ?>" required>
          </div>
          <div class="col-md-2">
            <input type="text" name="Class" class="form-control" placeholder="Class (e.g., JHS1)"
              value="<?= htmlspecialchars($editStudent['Class'] ?? '') ?>">
          </div>
          <div class="col-md-2">
            <select name="Gender" class="form-select">
              <option value="">-- Gender --</option>
              <option value="Male" <?= ($editStudent['Gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
              <option value="Female" <?= ($editStudent['Gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
            </select>
          </div>
          <div class="col-md-2">
            <input type="text" name="AdmissionNumber" class="form-control" placeholder="Admission Number"
              value="<?= htmlspecialchars($editStudent['AdmissionNumber'] ?? '') ?>" required>
          </div>
        </div>
        <button class="btn btn-primary mt-3">
          <i class="bi bi-save"></i> <?= $editStudent ? 'Update Student' : 'Save Student' ?>
        </button>
        <?php if ($editStudent): ?>
          <a href="students.php" class="btn btn-secondary mt-3">Cancel</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- Display Table -->
  <div class="card shadow">
    <div class="card-body bg-light">
      <div class="table-container">
      <table class="table table-hover table-bordered align-middle" id="studentsTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Student</th>
            <th>Class</th>
            <th>Gender</th>
            <th>Admission #</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $students->fetch_assoc()): ?>
            <tr>
              <td><?= $row['StudentID'] ?></td>
              <td><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
              <td><?= htmlspecialchars($row['Class']) ?></td>
              <td>
                <?php if ($row['Gender'] === 'Male'): ?>
                  <span class="badge bg-primary">Male</span>
                <?php elseif ($row['Gender'] === 'Female'): ?>
                  <span class="badge bg-warning text-dark">Female</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['AdmissionNumber']) ?></td>
              <td class="text-center">
                <a href="?edit=<?= $row['StudentID'] ?>" class="btn btn-sm btn-warning text-white me-2">
                  <i class="bi bi-pencil-square"></i> Edit
                </a>
                <a href="?delete=<?= $row['StudentID'] ?>" class="btn btn-sm btn-danger"
                  onclick="return confirm('Delete this student?');">
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
  let rows = document.querySelectorAll("#studentsTable tbody tr");
  rows.forEach(row => {
    let text = row.innerText.toLowerCase();
    row.style.display = text.includes(filter) ? "" : "none";
  });
});
</script>
<script>
  // Auto-fade alerts after 4 seconds
  document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
      setTimeout(() => {
        alert.classList.add('fade');
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 1000); // Remove from DOM after fade
      }, 4000);
    });
  });
</script>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
</body>
</html>
<style>
body {
  background: linear-gradient(135deg, #fbe8c7, #fff3da);
  color: #212529;
  font-family: "Poppins", sans-serif;
}
.table-container {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  width: 100%;
}
h3.fw-bold {
  color: #3d2b1f;
  font-size: 1.7rem;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}

.table th {
  background-color: #d6892d !important;
  color: #fff !important;
  text-align: center;
}

.table td {
  background-color: #fffdf8;
  color: #333;
}

.table-hover tbody tr:hover {
  background-color: #fff1c2;
}

.card {
  border-radius: 15px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.form-control, .form-select {
  border-radius: 10px;
  padding: 10px;
}

.btn {
  border-radius: 10px;
  font-weight: 500;
  border: none;
}

#searchInput {
  border-radius: 20px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

#searchInput:focus {
  box-shadow: 0 0 8px rgba(214,137,45,0.8);
}
</style>



<?php include __DIR__ . '/../includes/footer.php'; ?>
