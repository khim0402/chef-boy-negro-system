<?php
session_start();

// Default restriction flag
$restricted = false;

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.html");
    exit();
}

// Role-based access
if ($_SESSION['role'] === 'Admin') {
    $restricted = false;
} elseif ($_SESSION['role'] === 'Manager') {
    $restricted = true;
} elseif ($_SESSION['role'] === 'Cashier') {
    header("Location: pos.html");
    exit();
} else {
    header("Location: login.html");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>ChefBoy Negro | Settings</title>
  <link rel="stylesheet" href="../css/settings.css"/>
</head>
<body>
  <!-- Header -->
  <header class="pos-header">
    <div class="header-left">
      <img src="../images/chef.webp" alt="ChefBoy Logo" class="logo-img"/>
      <span class="brand-name">ChefBoy Negro</span>
    </div>
    <div class="header-right">
      <span id="datetime"></span>
      <button class="logout-btn">Logout</button>
    </div>
  </header>

  <div class="page-wrapper">
    <!-- Sidebar -->
    <nav class="sidebar">
      <a href="dashboard.html" class="nav-link">Dashboard</a>
      <a href="sales.html" class="nav-link">Sales</a>
      <a href="inventory.html" class="nav-link">Inventory</a>
      <a href="forecast.html" class="nav-link">Forecast</a>
      <a href="settings.php" class="nav-link active">Settings</a>
    </nav>

    <!-- Main Content -->
    <main class="settings-main">
      <h2>Manage Users</h2>

      <?php if ($restricted): ?>
        <!-- Manager restriction message -->
        <div id="settings-message" class="error">
          ⚠️ Managers don't have access to this module.
        </div>
      <?php else: ?>
        <!-- Add User Form -->
        <form id="user-form" action="/php/settings-api.php" method="post">
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="You must enter atleast 8 characters password" required >
          </div>

          <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role" required>
              <option value="Cashier">Cashier</option>
              <option value="Manager">Manager</option>
              <option value="Admin">Admin</option>
            </select>
          </div>

          <button type="submit" class="save-btn">Add User</button>
        </form>

        <div id="settings-message"></div>

        <!-- User List -->
        <section class="user-list">
          <h3>Existing Accounts</h3>
          <table class="user-table" id="user-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Created At</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </section>
      <?php endif; ?>
    </main>
  </div>

  <!-- Always load header clock -->
  <script src="../js/header.js"></script>

  <!-- Only load settings.js for Admin -->
  <?php if (!$restricted): ?>
    <script src="../js/settings.js"></script>
  <?php endif; ?>
</body>
</html>
