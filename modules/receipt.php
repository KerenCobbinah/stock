<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';

$id = intval($_GET['id'] ?? 0);

$receipt = $conn->query("
    SELECT tr.DateAdded, tr.Quantity, tr.TransactionType,
           ui.ItemName, ui.Size, ui.Gender,
           CONCAT(s.FirstName, ' ', s.LastName) AS StudentName, s.Class
    FROM StockTransactions tr
    JOIN Stock st ON tr.StockID = st.StockID
    JOIN UniformItems ui ON st.ItemID = ui.ItemID
    LEFT JOIN Students s ON tr.StudentID = s.StudentID
    WHERE tr.TransactionID = $id
")->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .receipt { max-width: 600px; margin: 40px auto; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .school-name { font-size: 22px; font-weight: bold; text-align: center; margin-bottom: 10px; }
        .receipt-title { text-align: center; font-size: 18px; margin-bottom: 20px; }
        .table th { width: 40%; }
        .btn-print { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="school-name">🏫 School Uniform Management</div>
        <div class="receipt-title">Stock Issue Receipt</div>

        <table class="table table-bordered">
            <tr><th>Date</th><td><?= $receipt['DateAdded'] ?></td></tr>
            <tr><th>Student</th><td><?= htmlspecialchars($receipt['StudentName']." (".$receipt['Class'].")") ?></td></tr>
            <tr><th>Item</th><td><?= htmlspecialchars($receipt['ItemName']." (".$receipt['Size'].", ".$receipt['Gender'].")") ?></td></tr>
            <tr><th>Quantity</th><td><?= $receipt['Quantity'] ?></td></tr>
            <tr><th>Type</th><td><?= $receipt['TransactionType'] ?></td></tr>
        </table>

        <div class="text-center btn-print">
            <button class="btn btn-primary" onclick="window.print()">🖨 Print Receipt</button>
            <a href="stock.php" class="btn btn-secondary">⬅ Back</a>
        </div>
    </div>
</body>
</html>
<?php include __DIR__ . '/../includes/footer.php'; ?>
