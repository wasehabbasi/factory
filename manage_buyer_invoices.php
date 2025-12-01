<?php
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="content">
  <div class="card p-3 table-responsive">
    <h2>Buyer Invoices</h2>

    <div class="mb-3 d-flex gap-2">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#buyerInvoiceModal">+ Add Invoice</button>
    </div>
    
    <table id="buyerInvoicesTable" class="table text-nowrap table-hover" style="width:100%">
      <thead>
        <tr>
          <th>#</th>
          <th>Invoice No</th>
          <th>Buyer</th>
          <th>Product</th>
          <th>Design No</th>
          <th>Qty</th>
          <th>Rate</th>
          <th>Total</th>
          <th>Nag</th>
          <th>Paid</th>
          <th>Balance</th>
          <th>Payment Date</th>
          <th>Remarks</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="buyerInvoiceTbody"></tbody>
      <tfoot>
        <tr>
          <th colspan="5" class="text-end">Total:</th>
          <th id="buyerTotalQty">0.00</th>
          <th id="buyerTotalRate">0.00</th>
          <th id="buyerTotalAmount">0.00</th>
          <th id="buyerTotalNag">0.00</th>
          <th id="buyerTotalPaid">0.00</th>
          <th id="buyerTotalBalance">0.00</th>
          <th colspan="3"></th>
        </tr>
      </tfoot>
    </table>

  </div>
</main>

<!-- Modal -->
<div class="modal fade" id="buyerInvoiceModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content bg-light text-dark">
      <form id="buyerInvoiceForm" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title">Buyer Invoice</h5>
          <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="buyer_invoice_id">

          <div class="mb-2">
            <label class="form-label">Buyer</label>
            <select name="buyer_id" id="buyerSelect" class="form-control" required></select>
          </div>

          <div class="mb-2">
            <label class="form-label">Design Number</label>
            <select name="design_number" id="buyer_design_number" class="form-control">
              <option value="">Select Design Number</option>
              <!-- Options will be filled by JS -->
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label">Product</label>
            <input type="text" name="product_id" id="product_id" class="form-control" required readonly>
          </div>

          <div class="mb-2">
            <label class="form-label">Lot Number</label>
            <input type="text" name="lot_number" id="buyer_lot_number" class="form-control" required readonly>
          </div>

          <div class="mb-2">
            <label class="form-label">Warehouse</label>
            <select name="warehouse_id" id="warehouseSelect" class="form-control" required></select>
          </div>


          <div class="row">
            <div class="col-md-4 mb-2">
              <label class="form-label">Qty</label>
              <input type="number" name="qty" id="buyer_qty" class="form-control" required>
            </div>
            <div class="col-md-4 mb-2">
              <label class="form-label">Rate</label>
              <input type="number" step="0.01" name="rate" id="buyer_rate" class="form-control" required>
            </div>
            <div class="col-md-4 mb-2">
              <label class="form-label">Total</label>
              <input type="number" step="0.01" name="total_amount" id="buyer_total_amount" class="form-control" required>
            </div>
          </div>

          <div class="col-md-4">
            <label for="nag" class="form-label">Nag</label>
            <input type="number" step="0.01" class="form-control" id="nag" name="nag" placeholder="Enter Nag amount">
          </div>

          <div class="row">
            <div class="col-md-6 mb-2">
              <label class="form-label">Amount Paid</label>
              <input type="number" step="0.01" name="amount_paid" id="buyer_amount_paid" class="form-control" required>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Payment Date</label>
              <input type="date" name="payment_date" id="buyer_payment_date" class="form-control">
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label">Remarks</label>
            <textarea name="remarks" id="buyer_remarks" class="form-control" rows="2"></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="./assets/js/buyers_invoices.js"></script>