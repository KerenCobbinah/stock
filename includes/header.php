<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>School Uniform System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/schooluniformdb/index.php">Uniform System</a>
    <div class="d-flex">
      <?php if (!empty($_SESSION['user'])): ?>
        <span class="navbar-text me-3">Hello, <?= htmlspecialchars($_SESSION['user']['username']) ?></span>
        <a href="/schooluniformdb/auth/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<div class="container my-4">
