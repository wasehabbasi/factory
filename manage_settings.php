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
    <div id="view">
        <div class="card p-3">
            <h2 class="h5 mb-3">System Settings</h2>
            
            <form id="settingsForm" class="vstack gap-3" enctype="multipart/form-data">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Site Email</label>
                        <input type="email" name="site_email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Logo</label>
                        <input type="file" name="logo_file" class="form-control">
                        <div id="logoPreview" class="mt-2"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Favicon</label>
                        <input type="file" name="favicon_file" class="form-control">
                        <div id="faviconPreview" class="mt-2"></div>
                    </div>
                </div>

                <div>
                    <button type="button" id="saveSettingsBtn" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>

        <!-- Change Password -->
        <div class="card p-3 mt-4">
            <h2 class="h5 mb-3">Change Password</h2>
            <form id="passwordForm" class="vstack gap-2">
                <div>
                    <label class="form-label">Old Password</label>
                    <input type="password" name="old_password" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <button type="button" id="changePasswordBtn" class="btn btn-warning mt-2">Update Password</button>
            </form>
        </div>
    </div>
</main>

<script src="./assets/js/settings.js"></script>
<?php
include 'includes/footer.php';
?>

