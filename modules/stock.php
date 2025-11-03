<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';

// ----------------- Helpers / DB operations -----------------
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

    $stmt = $conn->prepare("INSERT INTO Stock (ItemID, Quantity) VALUES (?, 0)");
    $stmt->bind_param("i", $itemID);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();
    return intval($newId);
}

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
        $stmt = $conn->prepare("INSERT INTO StockTransactions (StockID, TransactionType, Quantity, DateAdded) VALUES (?,?,?,NOW())");
        $stmt->bind_param("isi", $stockID, $type, $qty);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function issue_stock($conn, $stockID, $studentID, $qty) {
    if ($qty <= 0) throw new Exception("Quantity must be greater than zero.");

    $conn->begin_transaction();
    try {
        // lock row
        $stmt = $conn->prepare("SELECT Quantity FROM Stock WHERE StockID = ? FOR UPDATE");
        $stmt->bind_param("i", $stockID);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) throw new Exception("Stock row not found.");
        $current = intval($row['Quantity']);
        if ($current < $qty) throw new Exception("Insufficient stock (have $current, requested $qty).");

        $stmt = $conn->prepare("UPDATE Stock SET Quantity = Quantity - ? WHERE StockID = ?");
        $stmt->bind_param("ii", $qty, $stockID);
        $stmt->execute();
        $stmt->close();

        $type = 'OUT';
        $stmt = $conn->prepare("INSERT INTO StockTransactions (StockID, TransactionType, Quantity, StudentID, DateAdded) VALUES (?,?,?,?,NOW())");
        $stmt->bind_param("isii", $stockID, $type, $qty, $studentID);
        $stmt->execute();
        $transactionId = $stmt->insert_id;
        $stmt->close();

        $conn->commit();
        return $transactionId;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// ----------------- Request handlers -----------------
$error = null;
$success = null;

// Provide modal details via Ajax: ?action=details&stockid=ID
if (isset($_GET['action']) && $_GET['action'] === 'details' && isset($_GET['stockid'])) {
    $stockID = intval($_GET['stockid']);

    // fetch item info & stock row
    $stmt = $conn->prepare("
        SELECT st.StockID, st.Quantity, ui.ItemID, ui.ItemName, ui.Size, ui.Gender
        FROM Stock st
        JOIN UniformItems ui ON st.ItemID = ui.ItemID
        WHERE st.StockID = ?");
    $stmt->bind_param("i", $stockID);
    $stmt->execute();
    $res = $stmt->get_result();
    $item = $res->fetch_assoc();
    $stmt->close();

    if (!$item) {
        echo "<div class='p-3'>Item not found.</div>";
        exit;
    }

    // transactions for this stock item
    $stmt = $conn->prepare("
        SELECT tr.TransactionID, tr.TransactionType, tr.Quantity, tr.DateAdded, tr.StudentID,
               CONCAT(s.FirstName, ' ', s.LastName) AS StudentName
        FROM StockTransactions tr
        LEFT JOIN Students s ON tr.StudentID = s.StudentID
        WHERE tr.StockID = ?
        ORDER BY tr.DateAdded DESC
        LIMIT 200
    ");
    $stmt->bind_param("i", $stockID);
    $stmt->execute();
    $trs = $stmt->get_result();
    $stmt->close();

    // Build modal HTML (returned to JS)
    ob_start();
    ?>
    <div class="p-3">
      <h5 class="mb-2"><?= htmlspecialchars($item['ItemName']) ?> <small class="text-muted">(<?= htmlspecialchars($item['Size'] . ', ' . $item['Gender']) ?>)</small></h5>
      <p><strong>Stock ID:</strong> <?= $item['StockID'] ?> &nbsp; <strong>Total:</strong> <?= $item['Quantity'] ?></p>

      <div class="table-responsive" style="max-height:360px; overflow:auto;">
        <table class="table table-hover table-sm">
          <thead style="background-color:#da9236ff; color:#fff;">
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Qty</th>
              <th>Student</th>
              <th class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($r = $trs->fetch_assoc()): ?>
              <tr>
                <td><?= date("M d, Y H:i", strtotime($r['DateAdded'])) ?></td>
                <td>
                  <?php if ($r['TransactionType'] === 'IN'): ?>
                    <span class="badge" style="background-color:#e6f4ea; color:#2a5a36;">IN</span>
                  <?php else: ?>
                    <span class="badge" style="background-color:#fde3d6; color:#7a2b00;">OUT</span>
                  <?php endif; ?>
                </td>
                <td><?= intval($r['Quantity']) ?></td>
                <td><?= $r['TransactionType'] === 'OUT' ? htmlspecialchars($r['StudentName'] ?? 'Unknown') : '-' ?></td>
                <td class="text-center">
                  <button class="btn btn-sm" style="background-color:#e38500ff; color:#fff;"
                          data-transaction-id="<?= $r['TransactionID'] ?>"
                          data-stock-id="<?= $stockID ?>"
                          data-bs-toggle="modal" data-bs-target="#confirmDeleteTransactionModal">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
    $html = ob_get_clean();
    echo $html;
    exit;
}

// POST actions: add (restock), issue, deleteStock, deleteTransaction
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $item = intval($_POST['ItemID'] ?? 0);
            $qty = intval($_POST['Quantity'] ?? 0);
            restock($conn, $item, $qty);
            header("Location: stock.php?msg=restocked");
            exit;
        }

        if ($action === 'issue') {
            $stockID = intval($_POST['StockID'] ?? 0);
            $studentID = intval($_POST['StudentID'] ?? 0);
            $qty = intval($_POST['Quantity'] ?? 0);
            $txid = issue_stock($conn, $stockID, $studentID, $qty);
            header("Location: receipt.php?id=" . intval($txid));
            exit;
        }

        if ($action === 'deleteStock') {
            $id = intval($_POST['StockID'] ?? 0);
            // ensure no transactions exist (or restrict deletion)
            $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM StockTransactions WHERE StockID = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $c = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (intval($c['cnt']) > 0) throw new Exception("Cannot delete stock record: related transactions exist.");
            $stmt = $conn->prepare("DELETE FROM Stock WHERE StockID = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            header("Location: stock.php?msg=deleted");
            exit;
        }

        if ($action === 'deleteTransaction') {
            $txid = intval($_POST['TransactionID'] ?? 0);

            // We'll remove transaction record and **reverse its effect** on stock quantity.
            // Fetch transaction
            $stmt = $conn->prepare("SELECT StockID, TransactionType, Quantity FROM StockTransactions WHERE TransactionID = ?");
            $stmt->bind_param("i", $txid);
            $stmt->execute();
            $res = $stmt->get_result();
            $tr = $res->fetch_assoc();
            $stmt->close();
            if (!$tr) throw new Exception("Transaction not found.");

            $conn->begin_transaction();
            try {
                // reverse quantity
                if ($tr['TransactionType'] === 'IN') {
                    // subtract quantity
                    $stmt = $conn->prepare("UPDATE Stock SET Quantity = Quantity - ? WHERE StockID = ?");
                    $stmt->bind_param("ii", $tr['Quantity'], $tr['StockID']);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // OUT => add back
                    $stmt = $conn->prepare("UPDATE Stock SET Quantity = Quantity + ? WHERE StockID = ?");
                    $stmt->bind_param("ii", $tr['Quantity'], $tr['StockID']);
                    $stmt->execute();
                    $stmt->close();
                }

                // delete transaction
                $stmt = $conn->prepare("DELETE FROM StockTransactions WHERE TransactionID = ?");
                $stmt->bind_param("i", $txid);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                header("Location: stock.php?msg=tx_deleted");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
        }
    } catch (Exception $ex) {
        $error = $ex->getMessage();
    }
}

// messages from GET
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'restocked') $success = "Stock restocked successfully.";
    if ($_GET['msg'] === 'deleted') $success = "Stock record deleted.";
    if ($_GET['msg'] === 'tx_deleted') $success = "Transaction deleted and stock adjusted.";
}

// ----------------- Fetching data -----------------
$stock = $conn->query("SELECT st.*, ui.ItemName, ui.Size, ui.Gender FROM Stock st JOIN UniformItems ui ON st.ItemID = ui.ItemID ORDER BY ui.ItemName");
$items = $conn->query("SELECT ItemID, ItemName, Size, Gender FROM UniformItems ORDER BY ItemName");
$students = $conn->query("SELECT StudentID, FirstName, LastName, Class FROM Students ORDER BY FirstName");
$transactions = $conn->query("
    SELECT tr.*, ui.ItemName, ui.Size, ui.Gender, CONCAT(s.FirstName, ' ', s.LastName) AS StudentName
    FROM StockTransactions tr
    JOIN Stock st ON tr.StockID = st.StockID
    JOIN UniformItems ui ON st.ItemID = ui.ItemID
    LEFT JOIN Students s ON tr.StudentID = s.StudentID
    ORDER BY tr.DateAdded DESC
    LIMIT 200
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Stock - School System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
/* Theme colors (from Students page) */
:root{
  --bg-start: #fac992ff;
  --bg-end:   #fbe7bbff;
  --header:   #da9236ff;
  --table-bg: #f0d8b0ff;
  --table-hover:#e4cf88ff;
  --text-dark: #3e2723;
  --accent: #e38500ff;
}

/* Page */
body {
  background: linear-gradient(135deg, var(--bg-start), var(--bg-end));
  color: var(--text-dark);
  font-family: "Poppins", sans-serif;
}

/* Headings */
h2 {
  color: var(--text-dark);
  font-weight: 700;
  text-shadow: 1px 1px 0 rgba(255,255,255,0.6);
}

/* Cards */
.card {
  border-radius: 15px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

/* Themed headers */
.card-header {
  background-color: var(--header);
  color: #ebd4a3ff;
  font-weight: 600;
}

/* Forms and tables background */
.bg-light {
  background-color: var(--table-bg) !important;
}

/* Table styles */
.table thead {
  background-color: var(--header);
  color: #ebd4a3ff;
}
.table tbody tr:hover {
  background-color: var(--table-hover);
}
.table td, .table th {
  vertical-align: middle;
}

/* Buttons */
.btn-accent {
  background-color: var(--accent);
  color: #fff;
  border: none;
}
.btn-accent:hover { filter: brightness(0.95); }

/* Search input */
.search-input {
  border-radius: 25px;
  padding-left: 38px;
  height: 40px;
}

/* small utilities */
.badge-low { background-color: #f6e0b3; color: #7a4b00; }

/* Modal scrollbar */
.modal-body .table { margin-bottom:0; }
</style>
</head>
<body class="container py-4">

  <h2 class="mb-4"><i class="bi bi-box-seam me-2"></i> Stock Management</h2>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <!-- Add / Restock -->
  <div class="card mb-4">
    <div class="card-header"><i class="bi bi-box-arrow-in-down me-2"></i> Add / Restock</div>
    <div class="card-body bg-light">
      <form method="post" class="row g-3 align-items-end">
        <input type="hidden" name="action" value="add">
        <div class="col-md-6">
          <label class="form-label">Item</label>
          <select name="ItemID" class="form-select" required>
            <option value="">-- Select Item --</option>
            <?php while ($i = $items->fetch_assoc()): ?>
              <option value="<?= $i['ItemID'] ?>"><?= htmlspecialchars($i['ItemName'] . " (" . $i['Size'] . ", " . $i['Gender'] . ")") ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Quantity</label>
          <input type="number" name="Quantity" class="form-control" min="1" required>
        </div>
        <div class="col-md-3">
          <button class="btn btn-accent w-100"><i class="bi bi-plus-circle me-1"></i> Save Stock</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Issue Stock -->
  <div class="card mb-4">
    <div class="card-header" style="background-color:#e38500ff; color:#fff;"><i class="bi bi-box-arrow-up me-2"></i> Issue Stock</div>
    <div class="card-body bg-light">
      <form method="post" class="row g-3 align-items-end">
        <input type="hidden" name="action" value="issue">
        <div class="col-md-4">
          <label class="form-label">Stock</label>
          <select name="StockID" class="form-select" required>
            <option value="">-- Select Stock --</option>
            <?php
            $stockList = $conn->query("SELECT st.StockID, ui.ItemName, ui.Size, ui.Gender, st.Quantity FROM Stock st JOIN UniformItems ui ON st.ItemID = ui.ItemID ORDER BY ui.ItemName");
            while ($s = $stockList->fetch_assoc()): ?>
              <option value="<?= $s['StockID'] ?>"><?= htmlspecialchars($s['ItemName'] . " (" . $s['Size'] . ", " . $s['Gender'] . ") - Left: " . $s['Quantity']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Quantity</label>
          <input type="number" name="Quantity" min="1" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Student</label>
          <select name="StudentID" class="form-select" required>
            <option value="">-- Select Student --</option>
            <?php while ($st = $students->fetch_assoc()): ?>
              <option value="<?= $st['StudentID'] ?>"><?= htmlspecialchars($st['FirstName'] . " " . $st['LastName'] . " (" . $st['Class'] . ")") ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-1">
          <button class="btn btn-danger w-100" title="Issue"><i class="bi bi-send"></i></button>
        </div>
      </form>
    </div>
  </div>

  <!-- Current Stock -->
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-box me-2"></i> Current Stock</span>
      <div style="width:320px;" class="position-relative">
        <i class="bi bi-search" style="position:absolute; left:12px; top:10px; color:#7a4b00;"></i>
        <input id="stockSearch" class="form-control search-input" placeholder="Search stock...">
      </div>
    </div>
    <div class="card-body bg-light">
      <div class="table-responsive">
        <table class="table table-hover align-middle" id="stockTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Item</th>
              <th>Quantity Left</th>
              <th>Last Updated</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $stock->fetch_assoc()):
              $low = intval($row['Quantity']) <= 5; ?>
              <tr class="<?= $low ? 'table-warning' : '' ?>">
                <td><?= $row['StockID'] ?></td>
                <td><?= htmlspecialchars($row['ItemName'] . " (" . $row['Size'] . ", " . $row['Gender'] . ")") ?></td>
                <td>
                  <?= $row['Quantity'] ?> <?= $low ? '<span class="badge badge-low ms-2">Low</span>' : '' ?>
                </td>
                <td><?= !empty($row['LastUpdated']) ? date("M d, Y H:i", strtotime($row['LastUpdated'])) : '-' ?></td>
                <td>
                  <button class="btn btn-sm btn-accent me-2 view-details-btn" data-stockid="<?= $row['StockID'] ?>">
                    <i class="bi bi-eye"></i> View Details
                  </button>

                  <button class="btn btn-sm btn-outline-danger delete-stock-btn" data-stockid="<?= $row['StockID'] ?>">
                    <i class="bi bi-trash"></i> Delete
                  </button>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Transaction History -->
  <div class="card mb-5">
    <div class="card-header d-flex justify-content-between align-items-center" style="background-color:#2b4d6b;">
      <span><i class="bi bi-clock-history me-2"></i> Transaction History</span>
      <div style="width:320px;" class="position-relative">
        <i class="bi bi-search" style="position:absolute; left:12px; top:10px; color:#fff;"></i>
        <input id="transactionSearch" class="form-control search-input" placeholder="Search transactions...">
      </div>
    </div>
    <div class="card-body bg-light">
      <div class="table-responsive">
        <table class="table table-hover align-middle" id="transactionTable">
          <thead>
            <tr>
              <th>Date</th>
              <th>Item</th>
              <th>Type</th>
              <th>Quantity</th>
              <th>Issued To</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($tr = $transactions->fetch_assoc()): ?>
              <tr>
                <td><?= date("M d, Y H:i", strtotime($tr['DateAdded'])) ?></td>
                <td><?= htmlspecialchars($tr['ItemName'] . " (" . $tr['Size'] . ", " . $tr['Gender'] . ")") ?></td>
                <td><?= $tr['TransactionType'] === 'IN' ? '<span class="badge text-dark" style="background-color:#f0f7ec;">IN</span>' : '<span class="badge text-dark" style="background-color:#fde9df;">OUT</span>' ?></td>
                <td><?= intval($tr['Quantity']) ?></td>
                <td><?= $tr['TransactionType'] === 'OUT' ? htmlspecialchars($tr['StudentName'] ?? 'Unknown') : '-' ?></td>
                <td>
                  <button class="btn btn-sm btn-outline-danger delete-transaction-btn" data-transaction-id="<?= $tr['TransactionID'] ?>" data-stock-id="<?= $tr['StockID'] ?>">
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

<!-- Modals -->
<!-- View Details Modal -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color:var(--header); color:#ebd4a3ff;">
        <h5 class="modal-title"><i class="bi bi-eye me-2"></i> Item Details & Transactions</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewDetailsBody">
        <div class="p-3 text-center text-muted">Loading...</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Confirm Delete Stock Modal -->
<div class="modal fade" id="confirmDeleteStockModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#a64b00; color:#fff;">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i> Confirm Delete Stock</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this stock record? <strong>This cannot be undone.</strong></p>
        <div id="deleteStockInfo" class="small text-muted"></div>
      </div>
      <div class="modal-footer">
        <form method="post" id="deleteStockForm">
          <input type="hidden" name="action" value="deleteStock">
          <input type="hidden" name="StockID" id="deleteStockID">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete Stock</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Confirm Delete Transaction Modal -->
<div class="modal fade" id="confirmDeleteTransactionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#a64b00; color:#fff;">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i> Confirm Delete Transaction</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this transaction? <strong>Stock will be adjusted accordingly.</strong></p>
        <div id="deleteTxInfo" class="small text-muted"></div>
      </div>
      <div class="modal-footer">
        <form method="post" id="deleteTxForm">
          <input type="hidden" name="action" value="deleteTransaction">
          <input type="hidden" name="TransactionID" id="deleteTransactionID">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete Transaction</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// helper: fetch and show details modal
document.querySelectorAll('.view-details-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const stockId = this.dataset.stockid;
    const modalBody = document.getElementById('viewDetailsBody');
    modalBody.innerHTML = '<div class="p-3 text-center text-muted">Loading...</div>';
    fetch('stock.php?action=details&stockid=' + encodeURIComponent(stockId))
      .then(r => r.text())
      .then(html => modalBody.innerHTML = html)
      .catch(() => modalBody.innerHTML = '<div class="p-3 text-danger">Failed to load details.</div>');
    new bootstrap.Modal(document.getElementById('viewDetailsModal')).show();
  });
});

// delete stock modal
document.querySelectorAll('.delete-stock-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const stockId = this.dataset.stockid;
    document.getElementById('deleteStockID').value = stockId;
    document.getElementById('deleteStockInfo').textContent = 'Stock ID: ' + stockId;
    new bootstrap.Modal(document.getElementById('confirmDeleteStockModal')).show();
  });
});

// delete transaction from row in transaction table
document.querySelectorAll('.delete-transaction-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const txId = this.dataset.transactionId;
    const stockId = this.dataset.stockId;
    document.getElementById('deleteTransactionID').value = txId;
    document.getElementById('deleteTxInfo').textContent = 'Transaction ID: ' + txId + ' (Stock ID: ' + stockId + ')';
    new bootstrap.Modal(document.getElementById('confirmDeleteTransactionModal')).show();
  });
});

// delete transaction buttons inside details modal (delegated)
document.addEventListener('click', function(e) {
  const target = e.target.closest('button[data-transaction-id]');
  if (!target) return;
  const txId = target.dataset.transactionId;
  const stockId = target.dataset.stockId;
  document.getElementById('deleteTransactionID').value = txId;
  document.getElementById('deleteTxInfo').textContent = 'Transaction ID: ' + txId + ' (Stock ID: ' + stockId + ')';
  new bootstrap.Modal(document.getElementById('confirmDeleteTransactionModal')).show();
});

// search utility
function addSearch(tableID, inputID) {
  const input = document.getElementById(inputID);
  input.addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll(`#${tableID} tbody tr`).forEach(r => {
      r.style.display = r.innerText.toLowerCase().includes(filter) ? '' : 'none';
    });
  });
}
addSearch('stockTable', 'stockSearch');
addSearch('transactionTable', 'transactionSearch');

// Auto-hide alerts after 4s
setTimeout(() => {
  document.querySelectorAll('.alert').forEach(a => a.remove());
}, 4000);
</script>
</body>
</html>

<?php include __DIR__ . '/../includes/footer.php'; ?>
