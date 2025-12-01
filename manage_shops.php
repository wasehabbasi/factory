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
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 m-0">Shops</h2>
            <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#shopModal">Add Shop</a>
        </div>

        <div class="table-responsive">
            <table id="shopsTable" class="table text-nowrap table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Phone Number</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="shopsTbody"></tbody>
            </table>
        </div>
    </div>

    <!-- Shop Invoices Table -->
    <div class="card p-3 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 m-0">Shop Invoices</h2>
        </div>

        <div class="table-responsive">
            <table id="shopInvoicesTable" class="table text-nowrap table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Customer Name</th>
                        <th>Paandi Name</th>
                        <th>Grand Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="shopInvoicesTbody">
                    <!-- Data loaded dynamically via JS -->
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" style="text-align:right">Total:</th>
                        <th></th> <!-- Grand Total -->
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Shop Modal -->
    <div class="modal fade" id="shopModal" tabindex="-1" aria-labelledby="shopModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-light text-dark">
                <div class="modal-header">
                    <h5 class="modal-title" id="shopModalLabel">Add Shop</h5>
                    <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="shopForm" class="vstack gap-2" enctype="multipart/form-data">
                        <input type="hidden" name="id">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input name="name" class="form-control" placeholder="Enter Name" minlength="3" maxlength="20" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input name="phone_number" class="form-control" minlength="9" maxlength="11" placeholder="03XXXXXXXXX" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" placeholder="Enter Address" required></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Image</label>
                                <input type="file" name="image" class="form-control">
                                <img id="imagePreview" src="" alt="" style="width:100px; margin-top:10px; display:none;">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="saveShopBtn" type="button" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Modal -->
    <div class="modal fade" id="invoiceModal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-light text-dark">
                <div class="modal-header text-white">
                    <h5 class="modal-title" id="invoiceModalLabel">Generate Invoice</h5>
                    <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form id="invoiceForm">
                        <input type="hidden" id="invoiceShopId" name="shop_id">

                        <div class="mb-3">
                            <label>Date</label>
                            <input type="date" class="form-control" name="date" id="invoiceDate"
                                value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label>Customer Name</label>
                            <input type="text" class="form-control" name="customer_name" id="invoiceCustomer" required>
                        </div>

                        <div class="mb-3">
                            <label>Paandi Name</label>
                            <input type="text" class="form-control" name="paandi_name" id="invoicePaandi" required>
                        </div>

                        <table class="table text-nowrap table-hover" id="invoiceItemsTable">
                            <thead>
                                <tr>
                                    <th>Design Number</th>
                                    <th>Product</th>
                                    <th>Cutting (m)</th>
                                    <th>Total Suits</th>
                                    <th>Total Quantity</th>
                                    <th>Rate</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>

                        <button type="button" class="btn btn-sm btn-secondary mb-2" id="addInvoiceRowBtn">+ Add Product</button>
                        <div class="text-end fw-bold">
                            Grand Total: <span id="invoiceGrandTotal">0.00</span>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="saveInvoiceBtn">Create Invoice</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="./assets/js/shop.js"></script>
<?php include 'includes/footer.php'; ?>