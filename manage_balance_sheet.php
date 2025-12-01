<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main class="content">
  <div class="card p-3 table-responsive">
    <h2 style="float: left;">Balance Sheet</h2>
    <table class="table text-nowrap table-hover" id="balanceTable" style="width:100%">
      <thead>
        <tr>
          <th>Date</th>
          <th>Vendor</th>
          <th>Lot</th>
          <th>Measurement</th>
          <th>Product</th>
          <th>Width</th>
          <th>Thaan</th>
          <th>Issue Meter</th>
          <th>Net Gazana</th>
          <th>Fresh Gazana</th>
          <th>Rate</th>
          <th>L-KMI</th>
          <th>Rejection</th>
          <th>Shortage</th>
          <th>Remaining</th>
          <th>Final Remarks</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="balanceTbody"></tbody>
      <tfoot>
        <tr>
          <th colspan="5" class="text-end">Total</th>
          <th></th> <!-- width -->
          <th></th> <!-- thaan -->
          <th></th> <!-- issue -->
          <th></th> <!-- net -->
          <th></th> <!-- fresh -->
          <th></th> <!-- rate -->
          <th></th> <!-- l_kmi -->
          <th></th> <!-- rejection -->
          <th></th> <!-- shortage -->
          <th></th> <!-- remaining -->
          <th colspan="2"></th>
        </tr>
      </tfoot>
    </table>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="./assets/js/balance_sheet.js"></script>