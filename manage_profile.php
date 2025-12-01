<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/topbar.php';
?>

<main class="content">
  <div class="container-fluid py-4">
    <div class="card shadow-sm border-0">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 mb-0">
            <i class="bi bi-person-circle me-2"></i> My Profile
          </h2>
          <button type="button"
            class="btn btn-sm btn-outline-primary"
            data-bs-toggle="modal"
            data-bs-target="#editProfileModal">
            <i class="bi bi-pencil-square me-1"></i> Edit
          </button>
        </div>

        <!-- Profile Info -->
        <div id="profileView" class="row g-3">
          <!-- JS se user details inject hongi -->
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow bg-dark text-white">
      <div class="modal-header bg-dark">
        <h5 class="modal-title" id="editProfileLabel">
          <i class="bi bi-pencil-square me-2"></i> Edit Profile
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="profileForm" class="vstack gap-3">
          <div>
            <label class="form-label fw-semibold">Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div>
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div>
            <label class="form-label fw-semibold">Role</label>
            <select name="role_id" class="form-select bg-white text-dark" required>
              <option value="">-- Select Role --</option>
              <?php
              $userRoleId = $row['role_id']; // current user ka role_id
              $roles = $conn->query("SELECT id, name FROM roles");
              while ($r = $roles->fetch_assoc()) {
                $selected = ($r['id'] == $userRoleId) ? "selected" : "";
                echo "<option value='{$r['id']}' $selected>{$r['name']}</option>";
              }
              ?>
            </select>
          </div>


          <hr class="text-muted">

          <h6 class="fw-bold">Change Password</h6>
          <div>
            <label class="form-label">Old Password</label>
            <input type="password" name="old_password" class="form-control" placeholder="Enter current password">
          </div>
          <div>
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" placeholder="Enter new password">
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          Cancel
        </button>
        <button type="button" id="saveProfileBtn" class="btn btn-primary">
          <i class="bi bi-save me-1"></i> Save Changes
        </button>
      </div>
    </div>
  </div>
</div>

<script src="./assets/js/profile.js"></script>
<?php
include 'includes/footer.php';
?>