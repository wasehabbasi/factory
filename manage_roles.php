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

$modules = $conn->query("SELECT * FROM modules ORDER BY name ASC");
?>

<main class="content">
    <div id="view">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 m-0">Roles</h2>
                <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#roleModal">Add Role</a>
            </div>
            <div class="table-responsive">
                <table id="rolesTable" class="table text-nowrap table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Role Name</th>
                            <th>Description</th>
                            <th>Modules</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rolesTbody"></tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="roleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-light text-dark">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Role</h5>
                        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="roleForm" class="vstack gap-2">
                            <input type="hidden" name="id" />
                            <div class="mb-2">
                                <label class="form-label">Role Name</label>
                                <input name="name" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Description</label>
                                <input name="description" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Assign Modules</label><br>
                                <?php while($m = $modules->fetch_assoc()): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="modules[]" value="<?= $m['id'] ?>">
                                        <label class="form-check-label"><?= $m['name'] ?></label>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button id="saveRoleBtn" type="button" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </div>
</main>

<script src="./assets/js/roles.js"></script>
<?php include 'includes/footer.php'; ?>

