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
                <h2 class="h5 m-0">Factories</h2>
                <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#factoryModal" id="addFactoryBtn">Add Factory</a>
            </div>
            <div class="table-responsive">
                <table id="factoriesTable" class="table text-nowrap table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="factoriesTbody"></tbody>
                </table>
            </div>
        </div>

        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 m-0">Factory Invoices</h2>
            </div>
            <div class="table-responsive">
                <table id="factoryInvoicesTable" class="table text-nowrap table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Factory Name</th>
                            <th>Product</th>
                            <th>Lot No</th>
                            <th>Total Meter</th>
                            <th>Rate/Meter</th>
                            <th>Total Amount</th>
                            <th>Shortage</th>
                            <th>Remaining Amount</th>
                            <th>Advance</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="factoryInvoicesTbody"></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" style="text-align:right">Total:</th>
                            <th></th> 
                            <th></th> 
                            <th></th> 
                            <th></th> 
                            <th></th> 
                            <th></th> 
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="modal fade" id="factoryModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-light text-dark">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Factory</h5>
                        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <form id="factoryForm" class="vstack gap-2" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="id">

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Name</label>
                                    <input
                                        name="name"
                                        type="text"
                                        class="form-control"
                                        minlength="3"
                                        maxlength="50"
                                        required
                                        placeholder="Enter factory name">
                                    <div class="invalid-feedback">Name must be between 3 and 50 characters.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input
                                        name="phone"
                                        type="text"
                                        class="form-control"
                                        minlength="7"
                                        maxlength="11"
                                        pattern="[0-9+()-\s]+"
                                        placeholder="Enter phone number">
                                    <div class="invalid-feedback">Please enter a valid phone number (7-11 digits).</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Address</label>
                                    <input
                                        name="address"
                                        type="text"
                                        class="form-control"
                                        minlength="5"
                                        maxlength="100"
                                        required
                                        placeholder="Enter factory address">
                                    <div class="invalid-feedback">Address must be between 5 and 100 characters.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Image (optional)</label>
                                    <input
                                        type="file"
                                        name="image_file"
                                        class="form-control"
                                        accept="image/*">
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button id="saveFactoryBtn" type="button" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="factoryInvoiceModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content bg-dark text-white">
                    <div class="modal-header">
                        <h5 class="modal-title">Generate Factory Invoice</h5>
                        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="factoryInvoiceForm">
                            <input type="hidden" name="factory_id" id="factory_id">
                            <input type="hidden" name="hidden_product_id" id="hidden_product_id">
                            <input type="hidden" name="edit" id="edit">
                            <input type="hidden" name="invoice_id" id="invoice_id">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Product</label>
                                    <select name="product_id" id="product_id" class="form-select" required></select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Lot Number</label>
                                    <input type="text" name="lot_number" id="lot_number" class="form-control" readonly>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Total Meter</label>
                                    <input type="number" id="total_meter" step="0.01" name="total_meter" class="form-control" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Per Meter Rate</label>
                                    <input type="number" id="per_meter_rate" step="0.01" name="per_meter_rate" class="form-control" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Shortage (Amount)</label>
                                    <input type="number" step="0.01" name="rejection" id="rejection" class="form-control" value="0">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Amount</label>
                                    <input type="number" step="0.01" name="advance_adjusted" id="advance_adjusted" class="form-control" value="0">
                                </div>

                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-success" id="saveFactoryInvoiceBtn">Save Invoice</button>
                    </div>
                </div>
            </div>
        </div>

</main>

<script src="./assets/js/factory.js"></script>
<?php
include 'includes/footer.php';
?>