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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 m-0">Warehouses</h2>
                <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#warehouseModal">Add Warehouse</a>
            </div>
            <div class="table-responsive">
                <table id="warehouseTable" class="table text-nowrap table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="warehousesTbody"></tbody>
                </table>
            </div>
        </div>

        <!-- Add Warehouse Modal -->
        <div class="modal fade" id="warehouseModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-light text-dark">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Warehouse</h5>
                        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <form id="warehouseForm">
                            <input type="hidden" name="id">

                            <!-- Warehouse Name -->
                            <div class="mb-2">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control"
                                    minlength="3" maxlength="30"
                                    placeholder="Warehouse Name" required>
                            </div>

                            <!-- Warehouse Address -->
                            <div class="mb-2">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" 
                                    minlength="5" maxlength="50"
                                    placeholder="Warehouse Address" required>
                            </div>

                            <!-- Warehouse Phone -->
                            <div class="mb-2">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control"
                                    minlength="11" maxlength="11"
                                    pattern="[0-9]{11}"
                                    placeholder="03XXXXXXXXX">
                                <div class="form-text text-light small">
                                    Enter 11-digit Pakistani number (e.g. 03001234567)
                                </div>
                            </div>

                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button id="saveWarehouseBtn" type="button" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </div>


</main>

<script src="./assets/js/warehouse.js"></script>
<?php
include 'includes/footer.php';
