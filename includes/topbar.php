<main class="content">
  <header class="topbar d-flex align-items-center justify-content-between">
    <button id="toggleSidebar" class="btn btn-sm btn-outline-light d-lg-none">☰</button>
    <h1 class="h4 m-0"><?= $site_name; ?></h1>
    <div class="user"><?= $_SESSION['username'] ?> | <a href="logout.php" style="color:#f87171;text-decoration:none;">Logout</a></div>
  </header>
