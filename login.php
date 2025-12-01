<?php
include "./db/db.php";

// Site settings
$sql = "SELECT site_name, logo_url FROM settings LIMIT 1";
$result = $conn->query($sql);
$settings = $result ? $result->fetch_assoc() : [];
$site_name = !empty($settings['site_name']) ? $settings['site_name'] : "Inventory Management System";
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?php echo htmlspecialchars($site_name); ?> — Login</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="./assets/css/login.css" rel="stylesheet">
</head>

<body>
  <div class="login-wrap">
    <!-- LEFT SIDE -->
    <div class="info">
      <h2>Welcome back to <?php echo htmlspecialchars($site_name); ?></h2>
      <p>Sign in to manage vendors, warehouses, purchases, invoices, and view balance sheets — all from a tidy dashboard.</p>
      <div class="features" aria-hidden="true">
        <div class="feature"><i class="fa-solid fa-shield-halved"></i> Secure</div>
        <div class="feature"><i class="fa-solid fa-boxes-stacked"></i> Inventory</div>
        <div class="feature"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</div>
        <div class="feature"><i class="fa-solid fa-chart-line"></i> Reports</div>
      </div>
      <p class="small-muted">If you don't have an account talk to your system administrator.</p>
    </div>

    <!-- RIGHT SIDE -->
    <div class="card-login">
      <div class="brand">
        <div class="logo">
          <?php if (!empty($settings['logo_url'])): ?>
            <img src="<?php echo htmlspecialchars($settings['logo_url']); ?>" alt="Logo" style="max-height:45px; max-width: 45px;">
          <?php else: ?>
            <i class="fa-solid fa-box"></i>
          <?php endif; ?>
        </div>
        <div>
          <h3><?php echo htmlspecialchars($site_name); ?></h3>
          <div class="sub">User Portal</div>
        </div>
      </div>

      <form id="loginForm" novalidate>
        <div class="mb-3">
          <label for="username">Email or Username</label>
          <input id="username" name="username" type="text" class="form-control" placeholder="you@example.com" required>
        </div>

        <div class="mb-3">
          <label for="password">Password</label>
          <div class="input-group">
            <input id="password" name="password" type="password" class="form-control" placeholder="••••••••" required aria-describedby="showPassBtn">
            <button type="button" id="showPassBtn" class="input-group-text show-pass-btn">
              <i id="showIcon" class="fa-regular fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="rememberMe">
            <label class="form-check-label" for="rememberMe" style="font-size:13px">Remember me</label>
          </div>
          <a href="#" style="font-size:13px;text-decoration:none">Forgot password?</a>
        </div>

        <div class="d-grid mb-2">
          <button class="btn btn-primary" type="submit">Sign In</button>
        </div>

        <div id="message" class="small-muted mt-2"></div>
      </form>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="./assets/js/login.js"></script>
</body>
</html>
