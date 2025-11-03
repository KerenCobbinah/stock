<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Hardcoded exact credentials (hidden, not shown in UI)
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
<html>
<head>
  <meta charset="utf-8">
  <title>Login - Uniform System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body>
<div class="container py-5" style="max-width:520px; margin-top:80px;">
  <div class="card p-4 shadow-lg">
    <h4 class="mb-3"><CENTER>STOCK MANAGEMENT SYSTEM</CENTER></h4>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required autocomplete="off";>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required autocomplete="off";>
      </div>
      <button class="btn btn-primary w-100">Login</button>
    </form>
  </div>
</div>
</body>
</html>
