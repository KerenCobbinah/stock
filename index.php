<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
include __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper py-5">
  <div class="container">

    <!-- Header -->
    <div class="text-center mb-5">
      <h1 class="fw-bold text-white">🎓 Dashboard</h1>
      
    </div>

    <!-- Dashboard Grid -->
    <div class="row g-4 justify-content-center">

      <!-- Suppliers -->
      <div class="col-sm-6 col-lg-4">
        <div class="card glass-card text-center h-100 p-4">
          <div class="mb-3">
            <i class="bi bi-truck fs-1 text-primary"></i>
          </div>
          <h5 class="fw-semibold">Uniform Suppliers</h5>
          <p class="text-muted small">Manage vendor records and details.</p>
          <a href="modules/suppliers.php" class="btn btn-primary rounded-pill px-4">Open</a>
        </div>
      </div>

      <!-- Uniform Items -->
      <div class="col-sm-6 col-lg-4">
        <div class="card glass-card text-center h-100 p-4">
          <div class="mb-3">
            <i class="bi bi-bag-check fs-1 text-success"></i>
          </div>
          <h5 class="fw-semibold">Uniform Items</h5>
          <p class="text-muted small">Link uniforms with suppliers and update prices.</p>
          <a href="modules/uniformitems.php" class="btn btn-success rounded-pill px-4">Open</a>
        </div>
      </div>

      <!-- Students -->
      <div class="col-sm-6 col-lg-4">
        <div class="card glass-card text-center h-100 p-4">
          <div class="mb-3">
            <i class="bi bi-people fs-1 text-warning"></i>
          </div>
          <h5 class="fw-semibold">Students</h5>
          <p class="text-muted small">Register and manage student details.</p>
          <a href="modules/students.php" class="btn btn-warning rounded-pill px-4 text-white">Open</a>
        </div>
      </div>

      <!-- Stock -->
      <div class="col-sm-6 col-lg-4">
        <div class="card glass-card text-center h-100 p-4">
          <div class="mb-3">
            <i class="bi bi-box-seam fs-1 text-danger"></i>
          </div>
          <h5 class="fw-semibold">UniformStock</h5>
          <p class="text-muted small">Track available uniform inventory.</p>
          <a href="modules/stock.php" class="btn btn-danger rounded-pill px-4">Open</a>
        </div>
      </div>


    </div>

  </div>
</div>

<!-- Styles -->
<style>
  body {
    background: linear-gradient(135deg,  #ffe7b0ff, #9e6e09ff, #fc7cd1ff );
    background-size: 400% 400%;
    animation: gradientShift 15s ease infinite;
    font-family: 'Poppins', sans-serif;
  }

  @keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }

  .dashboard-wrapper {
    min-height: 100vh;
  }

  .glass-card {
    background: rgba(255, 255, 255, 0.12);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
  }

  .glass-card:hover {
    transform: translateY(-8px) scale(1.03);
    box-shadow: 0 8px 40px rgba(0, 0, 0, 0.4);
  }

  h2 {
    text-shadow: 2px 2px 8px rgba(0,0,0,0.4);
  }

  .btn {
    font-weight: 500;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
  }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
