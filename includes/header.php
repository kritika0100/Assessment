<?php
require_once __DIR__ . '/bootstrap.php';

$u = current_user();
$f = flash_get();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>

  <title><?= h($title ?? 'Donora Charity System') ?></title>

  <!-- Main CSS -->
  <link rel="stylesheet" href="../assets/css/style.css"/>

  <!-- Main JavaScript -->
  <script defer src="../assets/js/script.js"></script>
</head>

<body>

<header class="topbar">
  <div class="wrap topbar__inner">

    <!-- Brand Logo -->
    <a class="brand" href="index.php">
      <span class="brand__mark"></span>
      <span class="brand__text">Donora</span>
      <span class="brand__sub">Donation & Charity System</span>
    </a>

    <!-- Navigation -->
    <nav class="nav">
      <a href="index.php#home">Home</a>
      <a href="index.php#events">Events</a>
      <a href="index.php#about">About</a>

      <?php if ($u): ?>

        <?php if (($u['role'] ?? '') === 'admin'): ?>
          <a class="pill" href="admin.php">Admin Dashboard</a>
        <?php else: ?>
          <a class="pill" href="dashboard.php">My Dashboard</a>
        <?php endif; ?>

        <a class="pill pill--ghost" href="logout.php">Logout</a>

      <?php else: ?>

        <a class="pill" href="auth.php">Login</a>

      <?php endif; ?>
    </nav>

  </div>
</header>

<main class="main">
  <div class="wrap">

    <!-- Flash Message -->
    <?php if ($f): ?>
      <div class="alert alert--<?= h($f['type']) ?>">
        <?= h($f['message']) ?>
      </div>
    <?php endif; ?>
