<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/topbar.php';
include 'db/db.php';

// Load roles to populate dropdown
$rolesRes = $conn->query("SELECT id, name FROM roles ORDER BY name ASC");
$roles = [];
while ($r = $rolesRes->fetch_assoc()) $roles[] = $r;
?>

<main class="content">
    <div id="view">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 m-0">Users</h2>
                <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">Add User</a>
            </div>

            <div class="table-responsive">
                <table id="usersTable" class="table text-nowrap table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTbody"></tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="userModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-light text-dark">
                    <div class="modal-header">
                        <h5 class="modal-title">Add User</h5>
                        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="userForm" class="vstack gap-2" enctype="multipart/form-data">
                            <input type="hidden" name="id" />
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Name</label>
                                    <input name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Role</label>
                                    <select name="role_id" class="form-select" required>
                                        <option value="">-- Select Role --</option>
                                        <?php foreach($roles as $role): ?>
                                            <option value="<?= htmlspecialchars($role['id']) ?>"><?= htmlspecialchars($role['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active">active</option>
                                        <option value="inactive">inactive</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Password <small class="text-muted">(leave blank to keep)</small></label>
                                    <input type="password" name="password" class="form-control">
                                </div>
                                <div class="col-12">
                                    <small class="text-muted">When editing: leave Password blank to keep current password.</small>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button id="saveUserBtn" type="button" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<script src="./assets/js/user.js"></script>
<?php
include 'includes/footer.php';
?>