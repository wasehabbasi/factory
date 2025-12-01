<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main class="content">
  <div class="card p-3">
    <h2>Records</h2>
    <div class="mb-3">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendModal">+ Send Inventory</button>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#receiveModal">+ Receive Inventory</button>
    </div>

    <!-- Send Inventory Table -->
    <h5>Send Inventory</h5>
    <table id="sendTable" class="table text-nowrap table-hover">
      <thead>
        <tr>
          <th>#</th>
          <th>Date</th>
          <th>Factory</th>
          <th>Vendor</th>
          <th>Lot Number</th>
          <th>Quantity</th>
        </tr>
      </thead>
      <tbody id="sendTbody"></tbody>
      <tfoot>
        <tr>
          <th colspan="5" class="text-end">Total Quantity:</th>
          <th id="sendTotalQty">0</th>
        </tr>
      </tfoot>
    </table>

    <!-- Receive Inventory Table -->
    <h5>Receive Inventory</h5>
    <table id="receiveTable" class="table text-nowrap table-hover">
      <thead>
        <tr>
          <th>#</th>
          <th>Date</th>
          <th>Factory</th>
          <th>Vendor</th>
          <th>Warehouse</th>
          <th>Lot</th>
          <th>Send Qty</th>
          <th>Receive Qty</th>
          <th>Design Number</th>
          <th>Nag</th>
          <th>Shortage</th>
          <th>Rejection</th>
          <th>L-KMI</th>
        </tr>
      </thead>
      <tbody id="receiveTbody"></tbody>
      <tfoot>
        <tr>
          <th colspan="6" class="text-end">Totals:</th>
          <th id="totalSendQty">0</th>
          <th id="totalReceiveQty">0</th>
          <th></th>
          <th></th>
          <th id="totalShortage">0</th>
          <th id="totalRejection">0</th>
          <th id="totalLKMI">0</th>
        </tr>
      </tfoot>
    </table>

    <!-- Warehouse Stock Table -->
    <h5 class="mt-4">Warehouse Inventory (Unique by Warehouse + Design Number)</h5>
    <table id="warehouseTable" class="table text-nowrap table-hover">
      <thead>
        <tr>
          <th>#</th>
          <th>Warehouse</th>
          <th>Factory</th>
          <th>Vendor</th>
          <th>Product</th>
          <th>Measurement</th>
          <th>Design No</th>
          <th>Nag</th>
          <th>Quantity</th>
        </tr>
      </thead>
      <tbody id="warehouseTbody"></tbody>
      <tfoot>
        <tr>
          <th></th>
          <th></th>
          <th></th>
          <th></th>
          <th></th>
          <th></th>
          <th></th>
          <th class="text-end">Grand Total:</th>
          <th id="warehouseTotal">0.00</th>
        </tr>
      </tfoot>
    </table>


    <!-- Warehouse Inventory Summary Table -->
    <h5 class="mt-4">Warehouse Inventory Summary (Unique by Warehouse)</h5>
    <table id="warehouseSummaryTable" class="table text-nowrap table-hover">
      <thead>
        <tr>
          <th>#</th>
          <th>Warehouse</th>
          <th>Total Quantity</th>
        </tr>
      </thead>
      <tbody id="warehouseSummaryTbody"></tbody>
      <tfoot>
        <tr>
          <th colspan="2" class="text-end">Grand Total:</th>
          <th id="warehouseSummaryTotal">0.00</th>
        </tr>
      </tfoot>
    </table>

  </div>
</main>

<!-- Send Modal -->
<div class="modal fade" id="sendModal">
  <div class="modal-dialog">
    <div class="modal-content bg-dark">
      <form id="sendForm">
        <div class="modal-header">
          <h5>Send Inventory</h5>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id">
          <label>Date</label><input type="date" name="date" class="form-control" required>

          <label>Factory</label>
          <select name="factory_id" id="sendFactorySelect" class="form-control" required></select>

          <label>Vendor</label>
          <select name="vendor_id" id="sendVendorSelect" class="form-control" required></select>

          <label>Lot Number</label>
          <select name="lot_number" id="sendLotNumber" class="form-control" required></select>

          <label>Warehouse</label>
          <select name="warehouse_id" id="sendWarehouseSelect" class="form-control" required></select>

          <label>Quantity (m)</label><input type="number" step="0.1" name="quantity" class="form-control" required>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Receive Modal -->
<div class="modal fade" id="receiveModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content bg-dark">
      <form id="receiveForm">
        <div class="modal-header">
          <h5>Receive Inventory</h5>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id">
          <label>Date</label><input type="date" name="date" class="form-control" required>

          <label>Factory</label>
          <select name="factory_id" id="receiveFactorySelect" class="form-control" required></select>

          <label>Vendor</label>
          <select name="vendor_id" id="receiveVendorSelect" class="form-control" required></select>

          <label>Warehouse</label>
          <select name="warehouse_id" id="receiveWarehouseSelect" class="form-control" required></select>

          <div class="mb-3">
            <label for="lot_number" class="form-label">Lot Number</label>
            <select id="lot_number" name="lot_number" class="form-select" required></select>
          </div>

          <label>Send Qty</label><input type="number" step="0.1" name="send_quantity" id="send_quantity" class="form-control" required readonly>
          <label>Receive Qty</label><input type="number" step="0.1" name="receive_quantity" id="receive_quantity" class="form-control" required>

          <label>Design Number</label>
          <select id="design_number" name="design_number" class="form-select" required></select>


          <label>Nag</label>
          <input type="number" name="nag" class="form-control" placeholder="Enter nag count" required>


          <label>Shortage</label><input type="number" step="0.1" name="shortage" class="form-control" value="0">
          <label>Rejection</label><input type="number" step="0.1" name="rejection" class="form-control" value="0">
          <label>L-KMI</label><input type="text" name="l_kmi" class="form-control" placeholder="Enter L-KMI reference">
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>


<?php include 'includes/footer.php'; ?>
<script src="./assets/js/factory_record.js"></script>