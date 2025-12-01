document.addEventListener("DOMContentLoaded", () => {
  loadLedger();
  loadBuyerledger();
  loadShopLedger();
  loadFactoryLedger();
});

async function loadLedger() {
  try {
    const res = await fetch("ledger.php?action=list");
    const d = await res.json();
    if (!d.success) return;

    let rows = "";
    let totalAmount = 0, totalPaid = 0, totalBalance = 0;

    const cumulative = {};
    const totalMap = {};

    // Sort for proper cumulative order
    d.data.sort((a, b) => {
      if (a.vendor_id !== b.vendor_id) return a.vendor_id - b.vendor_id;
      if (a.lot_number !== b.lot_number) return a.lot_number - b.lot_number;
      return a.id - b.id;
    });

    d.data.forEach((inv, i) => {
      const total = parseFloat(inv.total_amount || 0);
      const paid = parseFloat(inv.amount_paid || 0);
      const key = inv.vendor_id + "_" + inv.lot_number;

      if (!totalMap[key]) {
        totalAmount += total;
        totalMap[key] = true;
      }

      if (!cumulative[key]) {
        cumulative[key] = {
          cumulativeBalance: total,
          cumulativePaid: 0
        };
      }

      const rowCumulativeBalance = cumulative[key].cumulativeBalance - paid;
      const rowCumulativePaid = cumulative[key].cumulativePaid + paid;

      cumulative[key].cumulativeBalance = rowCumulativeBalance;
      cumulative[key].cumulativePaid = rowCumulativePaid;

      totalPaid += paid;
      totalBalance += (total - paid);

      rows += `
      <tr>
        <td>${i + 1}</td>
        <td>${escapeHtml(inv.vendor_name)}</td>
        <td>${escapeHtml(inv.lot_number ?? '-')}</td>
        <td>${total.toFixed(2)}</td>
        <td>${paid.toFixed(2)}</td>
        <td>${(total - paid).toFixed(2)}</td>
        <td>${rowCumulativeBalance.toFixed(2)}</td>
        <td>${rowCumulativePaid.toFixed(2)}</td>
        <td>${formatDate(inv.payment_date) || '-'}</td>
        <td>${escapeHtml(inv.remarks ?? '-')}</td>
      </tr>`;
    });

    const table = $("#ledgerTable");

    if ($.fn.DataTable.isDataTable(table)) {
      table.DataTable().clear().destroy();
    }

    document.getElementById("ledgerTbody").innerHTML = rows;

    // ✅ Initialize DataTable with footer totals
    table.DataTable({
      dom: 'Bfrtip',
      responsive: true,
      pageLength: 10,
      lengthMenu: [10, 25, 50, 100],
      buttons: [
        { extend: 'csvHtml5', text: 'CSV', className: 'btn btn-sm btn-primary', footer: true },
        { extend: 'excelHtml5', text: 'Excel', className: 'btn btn-sm btn-success', footer: true },
        { extend: 'pdfHtml5', text: 'PDF', className: 'btn btn-sm btn-danger', footer: true, orientation: 'landscape', pageSize: 'A4' },
        {
          extend: 'print',
          text: 'Print',
          className: 'btn btn-sm btn-warning',
          footer: true,
          customize: function (win) {
            $(win.document.body)
              .find('tfoot th')
              .css({
                'font-weight': 'bold',
                'background-color': '#f8f9fa',
                'color': '#000'
              });
          }
        }
      ],
      language: {
        lengthMenu: "Show _MENU_ entries per page",
        zeroRecords: "No records found",
        info: "Showing _START_ to _END_ of _TOTAL_ entries",
        infoEmpty: "No entries available",
        search: "Search:",
      },
      footerCallback: function (row, data, start, end, display) {
        const api = this.api();
        const intVal = i => (typeof i === 'string' ? parseFloat(i.replace(/[\$,]/g, '')) || 0 : (typeof i === 'number' ? i : 0));

        let totalAmtMap = {}; // unique vendor+lot
        let totalAmt = 0;

        // loop through currently displayed rows
        api.rows({ search: 'applied' }).data().each(function (d) {
          const vendor = d[1]; // Vendor column
          const lot = d[2];    // Lot column
          const amt = intVal(d[3]); // Total Amount column
          const key = vendor + "_" + lot;

          if (!totalAmtMap[key]) {
            totalAmt += amt;
            totalAmtMap[key] = true;
          }
        });

        // Paid total (normal sum)
        const totalPaid = api.column(4, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);

        // Balance total = Total Amount (unique) - Paid
        const totalBal = totalAmt - totalPaid;

        // Update footer
        $(api.column(3).footer()).html(totalAmt.toFixed(2));
        $(api.column(4).footer()).html(totalPaid.toFixed(2));
        $(api.column(5).footer()).html(totalBal.toFixed(2));
      }


    });

  } catch (err) {
    console.error(err);
    alert("Could not load ledgers");
  }
}


function escapeHtml(text) {
  if (text === null || text === undefined) return '';
  return String(text)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

async function loadBuyerledger() {
  try {
    const res = await fetch("ledger.php?action=buyer_list");
    const d = await res.json();
    if (!d.success) throw new Error("Failed to load ledger");

    let rows = "";
    let totalQty = 0, totalRate = 0, totalAmount = 0, totalPaid = 0, totalBalance = 0, totalNag = 0;

    d.data.forEach((inv, i) => {
      const qty = parseFloat(inv.qty || 0);
      const rate = parseFloat(inv.rate || 0);
      const total = parseFloat(inv.total_amount || 0);
      const nag = parseFloat(inv.nag || 0);
      const paid = parseFloat(inv.amount_paid || 0);
      const balance = parseFloat(inv.balance ?? (total - paid));

      totalQty += qty;
      totalRate += rate;
      totalAmount += total;
      totalNag += nag;
      totalPaid += paid;
      totalBalance += balance;

      rows += `
        <tr>
          <td>${i + 1}</td>
          <td>${inv.invoice_no || '-'}</td>
          <td>${inv.buyer_name || '-'}</td>
          <td>${inv.product_name || '-'}</td>
          <td>${inv.lot_number || '-'}</td>
          <td>${qty.toFixed(2)}</td>
          <td>${rate.toFixed(2)}</td>
          <td>${total.toFixed(2)}</td>
          <td>${nag.toFixed(2)}</td>
          <td>${paid.toFixed(2)}</td>
          <td>${balance.toFixed(2)}</td>
          <td>${formatDate(inv.payment_date) || '-'}</td>
          <td>${inv.remarks || '-'}</td>
        </tr>`;
    });

    document.getElementById("buyerLedgerTbody").innerHTML = rows;

    // ✅ Totals in footer
    document.getElementById("buyerTotalQty").textContent = totalQty.toFixed(2);
    document.getElementById("buyerTotalRate").textContent = totalRate.toFixed(2);
    document.getElementById("buyerTotalAmount").textContent = totalAmount.toFixed(2);
    document.getElementById("buyerTotalNag").textContent = totalNag.toFixed(2);
    document.getElementById("buyerTotalPaid").textContent = totalPaid.toFixed(2);
    document.getElementById("buyerTotalBalance").textContent = totalBalance.toFixed(2);

    // ✅ Re-initialize DataTable
    if ($.fn.DataTable.isDataTable("#buyerLedgerTable")) {
      $("#buyerLedgerTable").DataTable().destroy();
    }

    $("#buyerLedgerTable").DataTable({
      dom: 'Bfrtip',
      responsive: true,
      pageLength: 5,
      lengthMenu: [5, 10, 25, 50, 100],
      paging: true,
      buttons: [
        { extend: 'csvHtml5', text: 'CSV', className: 'btn btn-sm btn-primary', footer: true },
        { extend: 'excelHtml5', text: 'Excel', className: 'btn btn-sm btn-success', footer: true },
        { extend: 'pdfHtml5', text: 'PDF', className: 'btn btn-sm btn-danger', footer: true },
        {
          extend: 'print',
          text: 'Print',
          className: 'btn btn-sm btn-warning',
          footer: true,
          customize: function (win) {
            $(win.document.body)
              .css('font-size', '12px')
              .prepend('<h3 style="text-align:center;">Buyer Ledger Report</h3>');

            // Add total row at bottom
            const footer = `
              <tfoot>
                <tr>
                  <th colspan="5" style="text-align:right;">Totals:</th>
                  <th>${totalQty.toFixed(2)}</th>
                  <th>${totalRate.toFixed(2)}</th>
                  <th>${totalAmount.toFixed(2)}</th>
                  <th>${totalNag.toFixed(2)}</th>
                  <th>${totalPaid.toFixed(2)}</th>
                  <th>${totalBalance.toFixed(2)}</th>
                  <th></th><th></th>
                </tr>
              </tfoot>`;
            $(win.document.body).find('table').append(footer);
          }
        }
      ],
      language: {
        lengthMenu: "Show _MENU_ entries per page",
        zeroRecords: "No records found",
        info: "Showing _START_ to _END_ of _TOTAL_ entries",
        infoEmpty: "No entries available",
        search: "Search:",
      },
      footerCallback: function (row, data, start, end, display) {
        const api = this.api();
        const intVal = i => typeof i === "string" ? parseFloat(i.replace(/[\$,]/g, '')) || 0 : (typeof i === "number" ? i : 0);

        // Unique total amount map by buyer + lot
        const totalMap = {};
        let totalAmountUnique = 0;

        api.rows({ search: 'applied' }).data().each(function (d) {
          const buyer = d[2]; // Buyer column
          const lot = d[4];   // Lot number column
          const key = buyer + "_" + lot;
          const amount = intVal(d[7]); // Total Amount column

          if (!totalMap[key]) {
            totalAmountUnique += amount;
            totalMap[key] = true;
          }
        });

        // Other totals (normal sum)
        const totalQty = api.column(5, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
        const totalRate = api.column(6, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
        const totalNag = api.column(8, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
        const totalPaid = api.column(9, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);

        // Balance = Total Amount (unique) - Paid
        const totalBalance = totalAmountUnique - totalPaid;

        // Update footer
        $(api.column(5).footer()).html(totalQty.toFixed(2));
        $(api.column(6).footer()).html(totalRate.toFixed(2));
        $(api.column(7).footer()).html(totalAmountUnique.toFixed(2));
        $(api.column(8).footer()).html(totalNag.toFixed(2));
        $(api.column(9).footer()).html(totalPaid.toFixed(2));
        $(api.column(10).footer()).html(totalBalance.toFixed(2));
      }

    });

  } catch (err) {
    console.error(err);
    alert(err.message);
  }
}

async function loadShopLedger() {
  try {
    const res = await fetch('ledger.php?action=get_shop_ledger');
    const response = await res.json();

    const tbody = document.getElementById('shopLedgerTbody');
    tbody.innerHTML = '';

    const data = response && Array.isArray(response.data) ? response.data : [];

    if (data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="5" class="text-center">No ledger found</td></tr>`;
      return;
    }

    let totalGrand = 0;
    data.forEach((inv, i) => {
      const grand = parseFloat(inv.grand_total || 0);
      totalGrand += grand;

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${i + 1}</td>
        <td>${formatDate(inv.date) || ''}</td>
        <td>${inv.customer_name || ''}</td>
        <td>${inv.paandi_name || ''}</td>
        <td>${grand.toFixed(2)}</td>
      `;
      tbody.appendChild(tr);
    });

    // ✅ Update footer total
    document.getElementById('shopTotalGrand').textContent = totalGrand.toFixed(2);

    // ✅ Reinitialize DataTable
    if ($.fn.DataTable.isDataTable('#shopLedgerTable')) {
      $('#shopLedgerTable').DataTable().destroy();
    }

    $('#shopLedgerTable').DataTable({
      dom: 'Bfrtip',
      responsive: true,
      pageLength: 5,
      lengthMenu: [5, 10, 25, 50, 100],
      paging: true,
      buttons: [
        { extend: 'csvHtml5', text: 'CSV', className: 'btn btn-sm btn-primary', footer: true },
        { extend: 'excelHtml5', text: 'Excel', className: 'btn btn-sm btn-success', footer: true },
        { extend: 'pdfHtml5', text: 'PDF', className: 'btn btn-sm btn-danger', footer: true },
        {
          extend: 'print',
          text: 'Print',
          className: 'btn btn-sm btn-warning',
          footer: true,
          customize: function (win) {
            $(win.document.body)
              .css('font-size', '12px')
              .prepend('<h3 style="text-align:center;">Shop Ledger Report</h3>');

            // Add footer manually for print
            const footer = `
              <tfoot>
                <tr>
                  <th colspan="4" style="text-align:right;">Total:</th>
                  <th>${totalGrand.toFixed(2)}</th>
                </tr>
              </tfoot>`;
            $(win.document.body).find('table').append(footer);
          }
        }
      ],
      language: {
        lengthMenu: "Show _MENU_ entries per page",
        zeroRecords: "No records found",
        info: "Showing _START_ to _END_ of _TOTAL_ entries",
        infoEmpty: "No entries available",
        search: "Search:",
      },
      footerCallback: function (row, data, start, end, display) {
        const api = this.api();
        const intVal = i => typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : (typeof i === 'number' ? i : 0);

        const totalGrand = api.column(4, { search: 'applied' })
          .data()
          .reduce((a, b) => intVal(a) + intVal(b), 0);

        $(api.column(4).footer()).html(totalGrand.toFixed(2));
      }
    });

  } catch (err) {
    console.error('Error loading ledger:', err);
  }
}

async function loadFactoryLedger() {
  try {
    const res = await fetch("ledger.php?action=get_factory_ledger");
    const data = await res.json();

    const tbody = document.getElementById("factoryLedgerTbody");
    tbody.innerHTML = "";

    if (!data || data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="11" class="text-center">No factory ledger found</td></tr>`;
      return;
    }

    // Build table rows
    let totalMeter = 0, totalAmount = 0, totalRejection = 0, totalNet = 0;
    const uniqueMap = {}; // For unique factory + lot

    data.forEach((inv, index) => {
      const meter = parseFloat(inv.total_meter || 0);
      const amount = parseFloat(inv.total_amount || 0);
      const rejection = parseFloat(inv.rejection || 0);
      const net = parseFloat(inv.net_amount || 0);

      // Unique key for totals
      const key = inv.factory_name + "_" + inv.lot_number;
      if (!uniqueMap[key]) {
        totalMeter += meter;
        totalAmount += amount;
        totalRejection += rejection;
        totalNet += net;
        uniqueMap[key] = true;
      }

      tbody.innerHTML += `
        <tr>
          <td>${index + 1}</td>
          <td>${inv.factory_name || '-'}</td>
          <td>${inv.product_name || '-'}</td>
          <td>${inv.lot_number || '-'}</td>
          <td>${meter.toFixed(2)}</td>
          <td>${parseFloat(inv.per_meter_rate || 0).toFixed(2)}</td>
          <td>${amount.toFixed(2)}</td>
          <td>${rejection.toFixed(2)}</td>
          <td>${net.toFixed(2)}</td>
          <td>${parseFloat(inv.advance_adjusted || 0).toFixed(2)}</td>
          <td>${formatDate(inv.created_at) || '-'}</td>
        </tr>`;
    });

    // Update footer totals
    document.getElementById("factoryTotalMeter").textContent = totalMeter.toFixed(2);
    document.getElementById("factoryTotalAmount").textContent = totalAmount.toFixed(2);
    document.getElementById("factoryTotalRejection").textContent = totalRejection.toFixed(2);
    document.getElementById("factoryTotalNet").textContent = totalNet.toFixed(2);

    // Destroy old DataTable
    if ($.fn.DataTable.isDataTable('#factoryLedgerTable')) {
      $('#factoryLedgerTable').DataTable().destroy();
    }

    $('#factoryLedgerTable').DataTable({
      dom: 'Bfrtip',
      responsive: true,
      pageLength: 5,
      lengthMenu: [5, 10, 25, 50, 100],
      buttons: [
        { extend: 'csvHtml5', text: 'CSV', className: 'btn btn-sm btn-primary', footer: true },
        { extend: 'excelHtml5', text: 'Excel', className: 'btn btn-sm btn-success', footer: true },
        { extend: 'pdfHtml5', text: 'PDF', className: 'btn btn-sm btn-danger', footer: true },
        {
          extend: 'print',
          text: 'Print',
          className: 'btn btn-sm btn-warning',
          footer: true,
          customize: function (win) {
            $(win.document.body)
              .css('font-size', '12px')
              .prepend('<h3 style="text-align:center;">Factory Ledger Report</h3>');
          }
        }
      ],
      language: {
        search: "Search:",
        lengthMenu: "Show _MENU_ entries per page",
        zeroRecords: "No records found",
        info: "Showing _START_ to _END_ of _TOTAL_ entries",
        infoEmpty: "No entries available",
        paginate: { previous: "Prev", next: "Next" }
      },
      footerCallback: function (row, data, start, end, display) {
        const api = this.api();
        const intVal = i => typeof i === 'string' ? parseFloat(i.replace(/[\$,]/g, '')) || 0 : (typeof i === 'number' ? i : 0);

        // Unique totals by factory + lot
        const uniqueMap = {};
        let totalMeter = 0, totalAmount = 0, totalRejection = 0, totalNet = 0;

        api.rows({ search: 'applied' }).data().each(d => {
          const factory = d[1]; // Factory Name column
          const lot = d[3];     // Lot No column
          const key = factory + "_" + lot;

          if (!uniqueMap[key]) {
            totalMeter += intVal(d[4]);
            totalAmount += intVal(d[6]);
            totalRejection += intVal(d[7]);
            totalNet += intVal(d[8]);
            uniqueMap[key] = true;
          }
        });

        $(api.column(4).footer()).html(totalMeter.toFixed(2));
        $(api.column(6).footer()).html(totalAmount.toFixed(2));
        $(api.column(7).footer()).html(totalRejection.toFixed(2));
        $(api.column(8).footer()).html(totalNet.toFixed(2));
      }
    });

  } catch (err) {
    console.error("Error loading factory ledger:", err);
  }
}


const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  if (isNaN(date)) return dateStr; // if invalid, return as is
  return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};