<?php
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="content">
  <div class="card p-3 table-responsive">
    <h2>Vendor Invoices</h2>

    <div class="mb-3 d-flex gap-2">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#invoiceModal">+ Add Invoice</button>
      <a href="invoices.php?action=export" class="btn btn-success">Export to CSV</a>
    </div>

    <table id="invoicesTable" class="table text-nowrap table-hover" style="width:100%">
      <thead>
        <tr>
          <th>#</th>
          <th>Payment Date</th> <!-- moved here -->
          <th>Vendor</th>
          <th>Lot No</th>
          <th>Issue Meter</th>
          <th>Rejection</th>
          <th>Safi Meter</th>
          <th>Rate</th>
          <th>Total Amount</th>
          <th>Paid</th>
          <th>Balance</th>
          <th>Remarks</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="invoiceTbody"></tbody>
      <tfoot>
        <tr>
          <th colspan="4" class="text-end">Total:</th> <!-- colspan adjusted -->
          <th id="totalIssue">0.00</th>
          <th id="totalRejection">0.00</th>
          <th id="totalSafi">0.00</th>
          <th></th>
          <th id="totalAmount">0.00</th>
          <th id="totalPaid">0.00</th>
          <th id="totalBalance">0.00</th>
          <th colspan="2"></th>
        </tr>
      </tfoot>

    </table>
  </div>
</main>

<!-- Invoice Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content bg-light text-dark">
      <form id="invoiceForm" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title">Vendor Invoice</h5>
          <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="invoiceId">

          <div class="mb-2">
            <label class="form-label">Vendor</label>
            <select name="vendor_id" id="vendorSelect" class="form-control" required></select>
          </div>

          <div class="mb-2">
            <label class="form-label">Lot Number</label>
            <select name="lot_number" id="lot_number" class="form-control" required>
              <option value="">Select Lot</option>
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label">Safi Meter</label>
            <input type="text" name="safi_meter" id="safi_meter" class="form-control" readonly>
          </div>

          <div class="mb-2">
            <label class="form-label">Rate</label>
            <input type="number" step="0.01" name="rate" id="rate" class="form-control" readonly>
          </div>

          <div class="row">
            <div class="col-md-6 mb-2">
              <label class="form-label">Total Amount</label>
              <input type="number" step="0.01" name="total_amount" id="total_amount" class="form-control" required readonly>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Amount Paid</label>
              <input type="number" step="0.01" name="amount_paid" id="amount_paid" class="form-control" required>
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label">Payment Date</label>
            <input type="date" name="payment_date" id="payment_date" class="form-control">
          </div>

          <div class="mb-2">
            <label class="form-label">Remarks</label>
            <textarea name="remarks" id="remarks" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="saveInvoiceBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="./assets/js/invoices.js"></script>