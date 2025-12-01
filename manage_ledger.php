<?php
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="content">
    <div class="card p-3 table-responsive">
        <h2 class="h5 m-0">Vendor ledger</h2>

        <table id="ledgerTable" class="table text-nowrap table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vendor</th>
                    <th>Lot No</th>
                    <th>Total Amount</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Cumulative Balance</th>
                    <th>Cumulative Paid</th>
                    <th>Payment Date</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody id="ledgerTbody"></tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total:</th>
                    <th id="totalAmount">0.00</th>
                    <th id="totalPaid">0.00</th>
                    <th id="totalBalance">0.00</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </tfoot>

        </table>
    </div>

    <div class="card p-3 table-responsive">
        <h2 class="h5 m-0">Buyer Ledger</h2>

        <table id="buyerLedgerTable" class="table text-nowrap table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Invoice No</th>
                    <th>Buyer</th>
                    <th>Product</th>
                    <th>Lot No</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Total</th>
                    <th>Nag</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Payment Date</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody id="buyerLedgerTbody"></tbody>
            <tfoot>
                <tr>
                    <th></th> <!-- 1 -->
                    <th></th> <!-- 2 -->
                    <th></th> <!-- 3 -->
                    <th></th> <!-- 4 -->
                    <th>Total:</th> <!-- 5 -->
                    <th id="buyerTotalQty">0.00</th>
                    <th id="buyerTotalRate">0.00</th>
                    <th id="buyerTotalAmount">0.00</th>
                    <th id="buyerTotalNag">0.00</th>
                    <th id="buyerTotalPaid">0.00</th>
                    <th id="buyerTotalBalance">0.00</th>
                    <th></th> <!-- 12 -->
                    <th></th> <!-- 13 -->
                </tr>
            </tfoot>

        </table>

    </div>

    <div class="card p-3 table-responsive">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 m-0">Shop Ledger</h2>
        </div>

        <div class="table-responsive">
            <table id="shopLedgerTable" class="table text-nowrap table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Customer Name</th>
                        <th>Paandi Name</th>
                        <th>Grand Total</th>
                    </tr>
                </thead>
                <tbody id="shopLedgerTbody"></tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Total:</th>
                        <th id="shopTotalGrand">0.00</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card p-3 table-responsive">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 m-0">Factory Ledger</h2>
        </div>
        <div class="table-responsive">
            <table id="factoryLedgerTable" class="table text-nowrap table-hover">
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
                    </tr>
                </thead>
                <tbody id="factoryLedgerTbody"></tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Total:</th>
                        <th id="factoryTotalMeter">0.00</th>
                        <th></th>
                        <th id="factoryTotalAmount">0.00</th>
                        <th id="factoryTotalRejection">0.00</th>
                        <th id="factoryTotalNet">0.00</th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>

        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="./assets/js/ledger.js"></script>