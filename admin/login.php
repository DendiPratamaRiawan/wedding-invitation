<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

send_security_headers(true);
if (current_admin()) {
    header('Location: index.php');
    exit;
}
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Masuk · Admin Undangan</title>
<link rel="stylesheet" href="../css/admin.css">
<script src="../js/admin-login.js" defer></script>
</head>
<body class="auth-page">
<main class="auth-card">
  <span class="brand__moon brand__moon--lg" aria-hidden="true"></span>
  <h1>Admin Undangan</h1>
  <form id="login-form" class="form-stack" novalidate>
    <label>Username <input name="username" required autocomplete="username" autofocus></label>
    <label>Password <input name="password" type="password" required autocomplete="current-password"></label>
    <p class="notice error" id="login-error" role="alert" hidden></p>
    <button class="btn btn-primary" type="submit">Masuk</button>
  </form>
</main>
</body>
</html>
