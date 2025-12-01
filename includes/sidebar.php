<?php
session_start();
include "./db/db.php";

// --- Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role_id = $_SESSION['role_id']; // role_id from login

// --- Fetch site settings
$sql = "SELECT site_name, logo_url FROM settings LIMIT 1";
$result = $conn->query($sql);
$settings = $result->fetch_assoc();

// --- Fetch modules allowed for this role
$modules_sql = "
    SELECT m.id AS module_id, m.name AS module_name, m.slug, m.icons AS module_icons
    FROM role_permissions rp
    LEFT JOIN modules m ON rp.module_id = m.id
    WHERE rp.role_id = ?
    ORDER BY m.sort_order ASC;
";
$stmt = $conn->prepare($modules_sql);
$stmt->bind_param("i", $role_id);
$stmt->execute();
$modules_result = $stmt->get_result();
$modules = [];
while ($row = $modules_result->fetch_assoc()) {
    $modules[] = $row;
}

// --- Get current file name (e.g., manage_invoices.php)
$current_page = basename($_SERVER['PHP_SELF']);
?>
<button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>

<aside class="sidebar">
  <div class="brand">
    <?php if (!empty($settings['logo_url'])): ?>
      <img src="<?php echo htmlspecialchars($settings['logo_url']); ?>" alt="Logo" style="max-height:40px; color: #f87171">
    <?php elseif (!empty($settings['site_name'])): ?>
      <?php echo htmlspecialchars($settings['site_name']); ?>
    <?php else: ?>
      Logo
    <?php endif; ?>
  </div>

  <nav class="menu">
    <a href="/index.php" class="menu-item <?php echo $current_page === 'index.php' ? 'active' : ''; ?>"> <i class="fa-solid fa-gauge"></i> Dashboard</a>

    <?php foreach ($modules as $mod): ?>
      <?php 
        $file = "manage_" . strtolower($mod['slug']) . ".php";
        $is_active = ($current_page === $file) ? 'active' : '';
      ?>
      <a href="/<?php echo $file; ?>" class="menu-item <?php echo $is_active; ?>">
        <?php echo $mod['module_icons']; ?>
        <?php echo htmlspecialchars($mod['module_name']); ?>
      </a>
    <?php endforeach; ?>
  </nav>
</aside>

<script src="../assets/js/app.js"></script>