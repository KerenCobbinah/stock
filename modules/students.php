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
    $stmt = $conn->prepare("DELETE FROM Students WHERE StudentID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: students.php");
    exit;
}

// Fetch all students
$students = $conn->query("SELECT * FROM Students ORDER BY Class, LastName");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Students - School System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
    <h2 class="mb-4">Students</h2>

    <!-- Add / Edit Form -->
    <form method="post" class="card p-3 mb-4">
        <input type="hidden" name="StudentID" value="">
        <div class="row g-3">
            <div class="col-md-3">
                <input type="text" name="FirstName" class="form-control" placeholder="First Name" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="LastName" class="form-control" placeholder="Last Name" required>
            </div>
            <div class="col-md-2">
                <input type="text" name="Class" class="form-control" placeholder="Class (e.g., JHS1)">
            </div>
            <div class="col-md-2">
                <select name="Gender" class="form-control">
                    <option value="">-- Gender --</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="AdmissionNumber" class="form-control" placeholder="Admission Number" required>
            </div>
        </div>
        <button class="btn btn-success mt-3">Save Student</button>
    </form>

    <!-- Display Table -->
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Student</th>
                <th>Class</th>
                <th>Gender</th>
                <th>Admission #</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $students->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['StudentID'] ?></td>
                    <td><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
                    <td><?= htmlspecialchars($row['Class']) ?></td>
                    <td><?= htmlspecialchars($row['Gender']) ?></td>
                    <td><?= htmlspecialchars($row['AdmissionNumber']) ?></td>
                    <td>
                        <a href="?edit=<?= $row['StudentID'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="?delete=<?= $row['StudentID'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this student?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
<?php include __DIR__ . '/../includes/footer.php'; ?>
