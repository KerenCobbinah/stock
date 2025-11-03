<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Hardcoded credentials
    $valid_username = "admin";
    $valid_password = "admin123";

    if ($username === $valid_username && $password === $valid_password) {
        $_SESSION['user'] = [
            'id' => 1,
            'username' => $valid_username,
            'role' => 'admin'
        ];
        header("Location: /schooluniformdb/index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login - Stock System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #cf7e05ff, #f9efbcff);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: "Poppins", sans-serif;
      color: #333;
    }

    .card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 15px;
      padding: 35px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.25);
    }

    h4 {
      color: #000000ff;
      font-weight: 700;
      text-align: center;
      margin-bottom: 25px;
    }

    .form-label {
      color: #222;
      font-weight: 500;
    }

    .form-control {
      border-radius: 10px;
      border: 1px solid #ccc;
      padding: 10px;
      font-size: 16px;
      color: #000;
      background-color: #fff;
    }

    .form-control:focus {
      border-color: #d2a419ff;
      box-shadow: 0 0 8px rgba(129, 97, 22, 0.3);
    }

    .btn-primary {
      background-color: #d29119ff;
      border: none;
      border-radius: 10px;
      padding: 12px;
      font-weight: 600;
      font-size: 16px;
      transition: all 0.2s ease;
    }

    .btn-primary:hover {
      background-color: #5e3b05ff;
      transform: scale(1.03);
    }

    .alert {
      font-size: 0.95rem;
    }
  </style>
</head>

<body>
  <div class="container" style="max-width:480px;">
    <div class="card">
      <h4>🎓 ST. MARY'S PREPARATORY/JHS <br> STOCK MANAGEMENT SYSTEM</h4>
      <?php if ($error): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required autocomplete="off">
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required autocomplete="off">
        </div>
        <button class="btn btn-primary w-100">Login</button>
      </form>
    </div>
  </div>
</body>
</html>
