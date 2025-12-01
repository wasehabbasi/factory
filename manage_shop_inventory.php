<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main class="content">
    <div class="card p-3">
        <h2>Shop Inventory Management</h2>

        <div class="mb-3 d-flex gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transferModal">+ Send Inventory to Shop</button>
        </div>

        <h5>Warehouse → Shop Transfers</h5>
        <table id="shopTransferTable" class="table text-nowrap table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Warehouse</th>
                    <th>Product Name</th>
                    <th>Measurement</th>
                    <th>Shop</th>
                    <th>Design No</th>
                    <th>Nag</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody id="transferTbody"></tbody>
            <tfoot>
                <tr>
                    <th colspan="8" class="text-end">Total Quantity:</th>
                    <th id="transferTotalQty">0.00</th>
                </tr>
            </tfoot>
        </table>

        <hr>

        <h5 class="mt-4">Shopwise Inventory Summary</h5>
        <table id="shopSummaryTable" class="table text-nowrap table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Shop</th>
                    <th>Total Quantity (m)</th>
                </tr>
            </thead>
            <tbody id="shopSummaryTbody"></tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-end">Grand Total:</th>
                    <th id="shopSummaryTotal">0.00</th>
                </tr>
            </tfoot>
        </table>

        <hr>

        <h5 class="mt-4">Shop Inventory (Unique by Product + Measurement + Shop + Design + Nag)</h5>
        <table id="shopInventoryTable" class="table text-nowrap table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Shop</th>
                    <th>Product</th>
                    <th>Measurement</th>
                    <th>Design No</th>
                    <th>Nag</th>
                    <th>Quantity</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody id="shopInventoryTbody"></tbody>
            <tfoot>
                <tr>
                    <th colspan="6" class="text-end">Grand Total:</th>
                    <th id="shopInventoryTotal">0.00</th>
                </tr>
            </tfoot>
        </table>

    </div>
</main>

<!-- Transfer Modal -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <form id="transferForm">
                <div class="modal-header">
                    <h5>Send Inventory to Shop</h5>
                </div>
                <div class="modal-body">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control" required>

                    <label>Warehouse</label>
                    <select name="warehouse_id" id="warehouseSelect" class="form-control" required></select>

                    <label>Shop</label>
                    <select name="shop_id" id="shopSelect" class="form-control" required></select>

                    <label>Design Number</label>
                    <select name="design_number" id="designSelect" class="form-control" required></select>

                    <label>Product</label>
                    <input type="text" name="product_name" id="productName" class="form-control" readonly>

                    <input type="hidden" name="product_id" id="productId">

                    <label>Lot Number</label>
                    <input type="text" name="lot_number" id="lotNumber" class="form-control" readonly>

                    <label>Nag</label>
                    <input type="number" name="nag" id="nagField" class="form-control">

                    <label>Measurement</label>
                    <input type="text" name="measurement" id="measurementField" class="form-control" readonly>

                    <label>Quantity (m)</label>
                    <input type="number" step="0.1" name="qty" class="form-control" required>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Invoice Modal (single-item invoice for a transfer) -->
<div class="modal fade" id="invoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <form id="invoiceForm">
                <div class="modal-header">
                    <h5>Create Invoice</h5>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="transfer_id" id="invoice_transfer_id">

                    <div class="mb-2">
                        <label class="form-label">Customer Name</label>
                        <input type="text" name="customer_name" id="invoice_customer" class="form-control" required>
                    </div>


                    <div class="mb-2">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" id="invoice_date" class="form-control" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Available Qty</label>
                        <input type="number" id="invoice_available" class="form-control" readonly>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Qty to Invoice</label>
                        <input type="number" step="0.1" name="qty" id="invoice_qty" class="form-control" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Detail</label>
                        <input type="text" name="detail" id="invoice_detail" class="form-control" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Rate (per meter)</label>
                        <input type="number" step="0.01" name="rate" id="invoice_rate" class="form-control" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Total Price</label>
                        <input type="number" step="0.01" name="total_price" id="invoice_total" class="form-control" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php include 'includes/footer.php'; ?>
<script src="./assets/js/shop_inventory.js"></script>