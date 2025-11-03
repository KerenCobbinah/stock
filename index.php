<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <h2 class="mb-4 text-center fw-bold" style="color: #004aad;">⭐ School Uniform Management Dashboard ⭐</h2>

  <div class="row g-4">
    <!-- Suppliers -->
    <div class="col-md-4">
      <div class="card h-100 shadow-lg border-0 rounded-4 bg-light dashboard-card">
        <div class="card-body text-center">
          <div class="mb-3">
            <i class="bi bi-truck fs-1 text-primary" aria-label="Suppliers"></i>
          </div>
          <h5 class="card-title">Suppliers</h5>
          <p class="card-text text-muted">Manage vendor records.</p>
          <a class="btn btn-primary w-100" href="modules/suppliers.php">Open</a>
        </div>
      </div>
    </div>

    <!-- Uniform Items -->
    <div class="col-md-4">
      <div class="card h-100 shadow-lg border-0 rounded-4 bg-light dashboard-card">
        <div class="card-body text-center">
          <div class="mb-3">
            <i class="bi bi-bag-check fs-1 text-success" aria-label="Uniform Items"></i>
          </div>
          <h5 class="card-title">Uniform Items</h5>
          <p class="card-text text-muted">Items linked to suppliers.</p>
          <a class="btn btn-success w-100" href="modules/uniformitems.php">Open</a>
        </div>
      </div>
    </div>

    <!-- Students -->
    <div class="col-md-4">
      <div class="card h-100 shadow-lg border-0 rounded-4 bg-light dashboard-card">
        <div class="card-body text-center">
          <div class="mb-3">
            <i class="bi bi-people fs-1 text-warning" aria-label="Students"></i>
          </div>
          <h5 class="card-title">Students</h5>
          <p class="card-text text-muted">Register & update students.</p>
          <a class="btn btn-warning w-100" href="modules/students.php">Open</a>
        </div>
      </div>
    </div>

    <!-- Stock -->
    <div class="col-md-6">
      <div class="card h-100 shadow-lg border-0 rounded-4 bg-light dashboard-card">
        <div class="card-body text-center">
          <div class="mb-3">
            <i class="bi bi-box-seam fs-1 text-danger" aria-label="Stock"></i>
          </div>
          <h5 class="card-title">Stock</h5>
          <p class="card-text text-muted">Track available inventory.</p>
          <a class="btn btn-danger w-100" href="modules/stock.php">Open</a>
        </div>
      </div>
    </div>

    <!-- Books -->
    <div class="col-md-6">
      <div class="card h-100 shadow-lg border-0 rounded-4 bg-light dashboard-card">
        <div class="card-body text-center">
          <div class="mb-3">
            <i class="bi bi-book fs-1 text-info" aria-label="Books"></i>
          </div>
          <h5 class="card-title">Books</h5>
          <p class="card-text text-muted">Manage book inventory.</p>
          <a class="btn btn-info w-100" href="modules/books_stock.php">Open</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Custom Styles -->
<style>
  body {
    background: url('img/snowflake-bg.jpg') center/cover no-repeat;
    background-color: #f0f4f7;
    color: #e8eef4;
  }

  .container {
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
  }

  h2 {
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
  }

  .dashboard-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #D98880 !important;
    background-color: #8d2e5dff !important;
  }

  .dashboard-card:hover {
    transform: translateY(-8px) scale(1.03);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
  }

  .card.bg-light {
    background-color: rgba(255, 255, 255, 0.85) !important;
  }

  .btn:hover {
    opacity: 0.9;
    transform: scale(1.02);
  }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
