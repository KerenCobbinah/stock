<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';

/**
 * Helpers
 */
function ensure_stock_row_for_item($conn, $itemID) {
    $stmt = $conn->prepare("SELECT StockID FROM Stock WHERE ItemID = ?");
    $stmt->bind_param("i", $itemID);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $stmt->close();
        return intval($row['StockID']);
    }
    $stmt->close();

    // create new stock row
    $stmt = $conn->prepare("INSERT INTO Stock (ItemID, Quantity) VALUES (?, 0)");
    $stmt->bind_param("i", $itemID);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();
    return intval($newId);
}

/**
 * RESTOCK
 */
function restock($conn, $itemID, $qty) {
    if ($qty <= 0) throw new Exception("Quantity must be greater than zero.");
    $stockID = ensure_stock_row_for_item($conn, $itemID);

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE Stock SET Quantity = Quantity + ? WHERE StockID = ?");
        $stmt->bind_param("ii", $qty, $stockID);
        $stmt->execute();
        $stmt->close();

        $type = 'IN';
        $stmt = $conn->prepare("INSERT INTO StockTransactions (StockID, TransactionType, Quantity) VALUES (?,?,?)");
        $stmt->bind_param("isi", $stockID, $type, $qty);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * ISSUE
 */
function issue_stock($conn, $stockID, $studentID, $qty) {
    if ($qty <= 0) throw new Exception("Quantity must be greater than zero.");

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT Quantity FROM Stock WHERE StockID = ? FOR UPDATE");
        $stmt->bind_param("i", $stockID);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        if (!$row) throw new Exception("Stock row not found.");

        $current = intval($row['Quantity']);
        if ($current < $qty) {
            throw new Exception("Insufficient stock (have $current, requested $qty).");
        }

        $stmt = $conn->prepare("UPDATE Stock SET Quantity = Quantity - ? WHERE StockID = ?");
        $stmt->bind_param("ii", $qty, $stockID);
        $stmt->execute();
        $stmt->close();

        $type = 'OUT';
        $stmt = $conn->prepare("INSERT INTO StockTransactions (StockID, TransactionType, Quantity, StudentID) VALUES (?,?,?,?)");
        $stmt->bind_param("isii", $stockID, $type, $qty, $studentID);
        $stmt->execute();
        $newTransactionID = $stmt->insert_id;
        $stmt->close();

        $conn->commit();
        return $newTransactionID; // return transaction ID
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// Messages
$error = null;
$success = null;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'issue') {
        $stockID = intval($_POST['StockID'] ?? 0);
        $qty = intval($_POST['Quantity'] ?? 0);
        $studentID = intval($_POST['StudentID'] ?? 0);
        try {
            $transactionID = issue_stock($conn, $stockID, $studentID, $qty);
            header("Location: receipt.php?id=" . $transactionID); // 🔹 redirect to receipt
            exit;
        } catch (Exception $ex) { $error = $ex->getMessage(); }
    }
    if ($action === 'add') {
        $item = intval($_POST['ItemID'] ?? 0);
        $qty = intval($_POST['Quantity'] ?? 0);
        try {
            restock($conn, $item, $qty);
            header("Location: stock.php?msg=restocked");
            exit;
        } catch (Exception $ex) { $error = $ex->getMessage(); }
    }
}

// Delete stock
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $check = $conn->prepare("SELECT COUNT(*) AS cnt FROM StockTransactions WHERE StockID = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $res = $check->get_result();
    $c = $res->fetch_assoc();
    $check->close();
    if (intval($c['cnt']) > 0) {
        $error = "Cannot delete stock record: there are related transactions.";
    } else {
        $stmt = $conn->prepare("DELETE FROM Stock WHERE StockID=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: stock.php?msg=deleted");
        exit;
    }
}

// Show GET messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'restocked') $success = "Stock restocked successfully.";
    if ($_GET['msg'] === 'deleted') $success = "Stock deleted successfully.";
}

// Fetch data
$stock = $conn->query("SELECT st.*, ui.ItemName, ui.Size, ui.Gender 
    FROM Stock st
    JOIN UniformItems ui ON st.ItemID = ui.ItemID
    ORDER BY ui.ItemName");

$items = $conn->query("SELECT ItemID, ItemName, Size, Gender FROM UniformItems ORDER BY ItemName");
$students = $conn->query("SELECT StudentID, FirstName, LastName, Class FROM Students ORDER BY FirstName");
$transactions = $conn->query("SELECT tr.*, ui.ItemName, ui.Size, ui.Gender, 
    CONCAT(s.FirstName, ' ', s.LastName) AS StudentName
    FROM StockTransactions tr
    JOIN Stock st ON tr.StockID = st.StockID
    JOIN UniformItems ui ON st.ItemID = ui.ItemID
    LEFT JOIN Students s ON tr.StudentID = s.StudentID
    ORDER BY tr.DateAdded DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stock - School System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        h2, h4 { font-weight: 600; }
        .card { border-radius: 15px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        .table th { text-align: center; }
        .badge { font-size: 0.85rem; }
    </style>
</head>
<body class="container py-4">
    <h2 class="mb-4"><i class="bi bi-box-seam"></i> Stock Management</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Add Stock -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <i class="bi bi-box-arrow-in-down"></i> Add / Restock
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Item</label>
                        <select name="ItemID" class="form-select" required>
                            <option value="">-- Select Item --</option>
                            <?php while($i = $items->fetch_assoc()): ?>
                                <option value="<?= $i['ItemID'] ?>">
                                    <?= htmlspecialchars($i['ItemName']." (".$i['Size'].", ".$i['Gender'].")") ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" min="1" name="Quantity" class="form-control" required>
                    </div>
                </div>
                <button class="btn btn-success mt-3"><i class="bi bi-plus-circle"></i> Save Stock</button>
            </form>
        </div>
    </div>

    <!-- Issue Stock -->
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <i class="bi bi-box-arrow-up"></i> Issue Stock
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="issue">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Stock</label>
                        <select name="StockID" class="form-select" required>
                            <option value="">-- Select Stock --</option>
                            <?php 
                            $stockList = $conn->query("SELECT st.StockID, ui.ItemName, ui.Size, ui.Gender, st.Quantity 
                                                       FROM Stock st 
                                                       JOIN UniformItems ui ON st.ItemID = ui.ItemID 
                                                       ORDER BY ui.ItemName");
                            while($s = $stockList->fetch_assoc()): ?>
                                <option value="<?= $s['StockID'] ?>">
                                    <?= htmlspecialchars($s['ItemName']." (".$s['Size'].", ".$s['Gender'].") - Left: ".$s['Quantity']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" min="1" name="Quantity" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Student</label>
                        <select name="StudentID" class="form-select" required>
                            <option value="">-- Select Student --</option>
                            <?php while($st = $students->fetch_assoc()): ?>
                                <option value="<?= $st['StudentID'] ?>">
                                    <?= htmlspecialchars($st['FirstName']." ".$st['LastName']." (".$st['Class'].")") ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <button class="btn btn-danger mt-3"><i class="bi bi-box-arrow-up"></i> Issue Stock</button>
            </form>
        </div>
    </div>

    <!-- Current Stock -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <i class="bi bi-box"></i> Current Stock
        </div>
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Item</th>
                        <th>Quantity Left</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $stock->fetch_assoc()): 
                        $low = intval($row['Quantity']) <= 5;
                    ?>
                        <tr class="<?= $low ? 'table-warning' : '' ?>">
                            <td><?= $row['StockID'] ?></td>
                            <td><?= htmlspecialchars($row['ItemName']." (".$row['Size'].", ".$row['Gender'].")") ?></td>
                            <td>
                                <?= $row['Quantity'] ?>
                                <?php if($low): ?><span class="badge bg-warning text-dark ms-2">Low</span><?php endif; ?>
                            </td>
                            <td><?= date("M d, Y H:i", strtotime($row['LastUpdated'])) ?></td>
                            <td>
                                <a href="?delete=<?= $row['StockID'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete this stock record?');">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Transactions -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-clock-history"></i> Transaction History
        </div>
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Issued To</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($tr = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?= date("M d, Y H:i", strtotime($tr['DateAdded'])) ?></td>
                            <td><?= htmlspecialchars($tr['ItemName']." (".$tr['Size'].", ".$tr['Gender'].")") ?></td>
                            <td>
                                <?php if($tr['TransactionType'] == 'IN'): ?>
                                    <span class="badge bg-success">IN</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">OUT</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $tr['Quantity'] ?></td>
                            <td><?= $tr['TransactionType'] == 'OUT' ? htmlspecialchars($tr['StudentName'] ?? 'Unknown') : '-' ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php include __DIR__ . '/../includes/footer.php'; ?>
