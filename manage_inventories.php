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
                <h2 class="h5 m-0">Inventory</h2>
                <!-- <div>
                    <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inventoryModal">Add Inventory</a> 
                </div> -->
            </div>

            <div class="table-responsive">
                <table id="inventoriesTable" class="table text-nowrap table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <!-- <th>SKU</th> -->
                            <th>Name</th>
                            <!-- <th>Warehouse</th> -->
                            <th>Qty</th>
                            <th>Measurement</th>
                            <th>Lot Number</th>
                            <th>Vendor</th>
                            <th>Cost</th>
                            <!-- <th>Price</th> -->
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventoriesTbody"></tbody>
                </table>
            </div>
        </div>

        <!-- 🔹 Add / Edit Inventory Modal -->
        <div class="modal fade" id="inventoryModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-light text-dark">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Inventory</h5>
                        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="inventoryForm" class="vstack gap-2">
                            <input type="hidden" name="id">

                            <div class="row g-2">
                                <!-- <div class="col-md-6">
                                    <label class="form-label">SKU</label>
                                    <input name="sku" class="form-control">
                                </div> -->
                                <div class="col-md-6">
                                    <label class="form-label">Name</label>
                                    <input name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Warehouse</label>
                                    <select name="warehouse_id" class="form-control" id="warehouseSelect" required>
                                        <option value="">Select Warehouse</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Unit</label>
                                    <input name="unit" class="form-control" value="pcs" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Lot Number</label>
                                    <input type="number" name="lot_number" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Vendor</label>
                                    <select name="vendor_id" class="form-control" id="vendorSelect" required>
                                        <option value="">Select Vendor</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Cost</label>
                                    <input type="number" step="0.01" name="cost" class="form-control" required>
                                </div>
                                <!-- <div class="col-md-6">
                                    <label class="form-label">Price</label>
                                    <input type="number" step="0.01" name="price" class="form-control" required>
                                </div> -->
                                <div class="col-md-6">
                                    <label class="form-label">Type</label>
                                    <input name="type" class="form-control" required>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button id="saveInventoryBtn" type="button" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🔹 Send Inventory Modal -->
        <div class="modal fade" id="sendInventoryModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-light text-dark">
                    <div class="modal-header">
                        <h5 class="modal-title">Send Inventory</h5>
                        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="sendInventoryForm" class="vstack gap-2">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="date" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Factory</label>
                                    <select name="factory_id" class="form-control" required></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Vendor</label>
                                    <select name="vendor_id" class="form-control" required></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Lot Number</label>
                                    <input type="number" name="lot_number" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Quantity (meters)</label>
                                    <input type="number" step="0.01" name="quantity" class="form-control" required>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button id="sendInventoryBtn" type="button" class="btn btn-success">Send</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🔹 Receive Inventory Modal -->
        <div class="modal fade" id="receiveInventoryModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-light text-dark">
                    <div class="modal-header">
                        <h5 class="modal-title">Receive Inventory</h5>
                        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="receiveInventoryForm" class="vstack gap-2">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="date" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Factory</label>
                                    <select name="factory_id" class="form-control" required></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Vendor</label>
                                    <select name="vendor_id" class="form-control" required></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Lot Number</label>
                                    <input type="number" name="lot_number" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Send Quantity</label>
                                    <input type="number" step="0.01" name="send_quantity" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Receive Quantity</label>
                                    <input type="number" step="0.01" name="receive_quantity" class="form-control" required>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button id="receiveInventoryBtn" type="button" class="btn btn-warning">Receive</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<script src="./assets/js/inventory.js"></script>
<?php include 'includes/footer.php'; ?>