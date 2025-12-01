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
                <h2 class="h5 m-0">Purchases</h2>
                <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#purchaseModal">Add Purchase</a>
            </div>
            <div id="purchaseTable" class="table-responsive">
                <table id="purchasesTable" class="table text-nowrap table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th>Rate</th>
                            <th>Lot Number</th>
                            <th>Measurement</th>
                            <th>Product</th>
                            <th>Width</th>
                            <th>Thaan</th>
                            <th>Issue Meter</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="purchaseTbody"></tbody>

                    <!-- ✅ Totals Row -->
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total:</th>
                            <th id="totalRate">0.00</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th id="totalWidth">0.00</th>
                            <th id="totalThaan">0.00</th>
                            <th id="totalIssue">0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>

            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="purchaseModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-light text-dark">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Purchase</h5>
                        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="purchaseForm" class="vstack gap-2">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="purchase_date" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Vendor</label>
                                    <select name="vendor_id" class="form-control" id="vendorSelect" required></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Rate</label>
                                    <input type="number" step="0.01" name="rate" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Lot Number</label>
                                    <input type="number" name="lot_number" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Measurement</label>
                                    <input type="text" name="measurement" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Product</label>
                                    <input type="text" name="product_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Width</label>
                                    <input type="number" name="width" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Thaan</label>
                                    <input type="number" name="thaan" class="form-control" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Issue Meter</label>
                                    <input type="number" name="issue_meter" class="form-control" required>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button id="savePurchaseBtn" type="button" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </div>

</main>

<script src="./assets/js/purchase.js"></script>
<?php
include 'includes/footer.php';
?>