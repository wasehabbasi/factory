document.addEventListener("DOMContentLoaded", () => {
  loadVendors();
  loadLotNumbers();
  loadInvoices();

  const invoiceForm = document.getElementById("invoiceForm");
  invoiceForm.addEventListener("submit", async function (e) {
    e.preventDefault();
    const btn = document.getElementById("saveInvoiceBtn");
    btn.disabled = true;
    btn.textContent = "Saving...";

    try {
      const formData = new FormData(this);
      const res = await fetch("invoices.php?action=save", {
        method: "POST",
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        // ✅ Auto-refresh table after save
        await loadInvoices(); // loadInvoices handles table update & totals

        location.reload();

        // Hide modal & reset form
        const modalEl = document.getElementById("invoiceModal");
        bootstrap.Modal.getInstance(modalEl)?.hide();
        this.reset();
        document.getElementById("invoiceId").value = '';
      } else {
        alert(data.message || data.error || "Error saving invoice");
      }
    } catch (err) {
      console.error(err);
      alert("Request failed");
    } finally {
      btn.disabled = false;
      btn.textContent = "Save";
    }
  });
  // Reset modal on close
  document.getElementById('invoiceModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById("invoiceForm").reset();
    document.getElementById("invoiceId").value = '';
  });
});

async function loadVendors() {
  try {
    const res = await fetch("vendors.php");
    const d = await res.json();
    if (d.success) {
      let opts = '<option value="">Select Vendor</option>';
      d.data.forEach(v => {
        opts += `<option value="${v.id}">${escapeHtml(v.name)}</option>`;
      });
      document.getElementById("vendorSelect").innerHTML = opts;
    } else {
      document.getElementById("vendorSelect").innerHTML = '<option value="">No vendors</option>';
    }
  } catch (err) {
    console.error(err);
    document.getElementById("vendorSelect").innerHTML = '<option value="">Error loading</option>';
  }
}

async function loadInvoices() {
  try {
    const res = await fetch("invoices.php?action=list");
    const d = await res.json();
    if (!d.success) return;

    const tbody = document.getElementById("invoiceTbody");
    tbody.innerHTML = '';

    d.data.forEach((inv, i) => {
      const total = parseFloat(inv.total_amount || 0);
      const paid = parseFloat(inv.amount_paid || 0);
      const balance = parseFloat(inv.balance ?? (total - paid));

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${i + 1}</td>
        <td>${formatDate(inv.payment_date) || '-'}</td> <!-- moved -->
        <td>${escapeHtml(inv.vendor_name)}</td>
        <td>${escapeHtml(inv.lot_number ?? '-')}</td>
        <td>${parseFloat(inv.issue_meter || 0).toFixed(2)}</td>
        <td>${parseFloat(inv.rejection || 0).toFixed(2)}</td>
        <td>${parseFloat(inv.safi_meter || 0).toFixed(2)}</td>
        <td>${parseFloat(inv.rate || 0).toFixed(2)}</td>
        <td>${total.toFixed(2)}</td>
        <td>${paid.toFixed(2)}</td>
        <td>${balance.toFixed(2)}</td>
        <td>${escapeHtml(inv.remarks ?? '-')}</td>
        <td>
          <button class="btn btn-sm btn-info" onclick="openPdf(${inv.id})">PDF</button>
          <button class="btn btn-sm btn-secondary" onclick="editInvoice(${inv.id})">Edit</button>
        </td>
      `;
      tbody.appendChild(tr);
    });

    const table = $("#invoicesTable");

    // Destroy old table if exists
    if ($.fn.DataTable.isDataTable(table)) {
      table.DataTable().clear().destroy();
    }

    // Initialize DataTable with dynamic totals
    table.DataTable({
      dom: 'Bfrtip',
      responsive: true,
      pageLength: 10,
      lengthMenu: [10, 25, 50, 100],
      buttons: [
        { extend: 'csvHtml5', text: 'CSV', className: 'btn btn-sm btn-primary', footer: true },
        { extend: 'excelHtml5', text: 'Excel', className: 'btn btn-sm btn-primary', footer: true },
        { extend: 'pdfHtml5', text: 'PDF', className: 'btn btn-sm btn-danger', footer: true },
        { extend: 'print', text: 'Print', className: 'btn btn-sm btn-success', footer: true }
      ],
      footerCallback: function (row, data, start, end, display) {
        const api = this.api();

        const intVal = i => typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : (typeof i === 'number' ? i : 0);

        // Columns: 3=Issue Meter, 4=Rejection, 5=Safi Meter, 7=Total Amount, 8=Paid, 9=Balance
        const totalIssue = api.column(4, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
        const totalRejection = api.column(5, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
        const totalSafi = api.column(6, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
        const totalAmount = api.column(8, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
        const totalPaid = api.column(9, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
        const totalBalance = api.column(10, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);

        $(api.column(4).footer()).html(totalIssue.toFixed(2));
        $(api.column(5).footer()).html(totalRejection.toFixed(2));
        $(api.column(6).footer()).html(totalSafi.toFixed(2));
        $(api.column(8).footer()).html(totalAmount.toFixed(2));
        $(api.column(9).footer()).html(totalPaid.toFixed(2));
        $(api.column(10).footer()).html(totalBalance.toFixed(2));
      },

      language: {
        lengthMenu: "Show _MENU_ entries per page",
        zeroRecords: "No records found",
        info: "Showing _START_ to _END_ of _TOTAL_ entries",
        infoEmpty: "No entries available",
        search: "Search:",
      }
    });

  } catch (err) {
    console.error(err);
    alert("Could not load invoices");
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  if (isNaN(date)) return dateStr;
  return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

function openPdf(id) {
  window.open(`invoice_pdf.php?id=${encodeURIComponent(id)}`, '_blank');
}

async function editInvoice(id) {
  try {
    const res = await fetch(`invoices.php?action=get&id=${encodeURIComponent(id)}`);
    const d = await res.json();
    if (!d.success) {
      alert("Invoice not found");
      return;
    }
    const inv = d.data;
    document.getElementById("invoiceId").value = inv.id;
    document.getElementById("vendorSelect").value = inv.vendor_id;
    document.getElementById("lot_number").value = inv.lot_number;
    document.getElementById("total_amount").value = parseFloat(inv.total_amount).toFixed(2);
    $("#total_amount").prop("readonly", true);
    document.getElementById("amount_paid").value = parseFloat(inv.amount_paid).toFixed(2);
    document.getElementById("payment_date").value = inv.payment_date ? inv.payment_date : '';
    document.getElementById("remarks").value = inv.remarks ?? '';

    const modalEl = document.getElementById('invoiceModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
  } catch (err) {
    console.error(err);
    alert("Failed to fetch invoice");
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

document.getElementById("vendorSelect").addEventListener("change", loadLotNumbers);

async function loadLotNumbers() {
  try {
    const vendorId = document.getElementById("vendorSelect").value;
    if (!vendorId) return; // ✅ only check vendorId

    const res = await fetch(`get_lot_numbers.php?vendor_id=${vendorId}`);
    const d = await res.json();
    if (d.success) {
      let opts = '<option value="">Select Lot</option>';
      d.data.forEach(lot => {
        opts += `<option value="${escapeHtml(lot)}">${escapeHtml(lot)}</option>`;
      });
      document.getElementById("lot_number").innerHTML = opts;
    } else {
      document.getElementById("lot_number").innerHTML = '<option value="">No lots found</option>';
    }
  } catch (err) {
    console.error(err);
    document.getElementById("lot_number").innerHTML = '<option value="">Error loading lots</option>';
  }
}

document.getElementById("vendorSelect").addEventListener("change", fetchAndSetTotalAmount);
document.getElementById("lot_number").addEventListener("change", fetchAndSetTotalAmount);

async function fetchAndSetTotalAmount() {
  const vendorId = document.getElementById("vendorSelect").value;
  const lotNumber = document.getElementById("lot_number").value;
  const totalAmountField = document.getElementById("total_amount");

  // Reset field initially
  totalAmountField.value = '';
  totalAmountField.readOnly = false;

  if (!vendorId || !lotNumber) return;

  try {
    const res = await fetch(`get_total_amount.php?vendor_id=${vendorId}&lot_number=${encodeURIComponent(lotNumber)}`);
    const data = await res.json();

    if (data.success) {
      totalAmountField.value = parseFloat(data.total_amount).toFixed(2);
      totalAmountField.readOnly = true;
    } else {
      console.warn("No total amount found for vendor/lot:", data.message);
    }
  } catch (err) {
    console.error("Error fetching total amount:", err);
  }
}


document.getElementById("vendorSelect").addEventListener("change", logVendorAndLot);
document.getElementById("lot_number").addEventListener("change", logVendorAndLot);

async function logVendorAndLot() {
  const vendorId = document.getElementById("vendorSelect").value;
  const lotNumber = document.getElementById("lot_number").value;

  if (vendorId && lotNumber) {
    try {
      const res = await fetch(
        `get_purchase_data.php?vendor_id=${vendorId}&lot_number=${encodeURIComponent(lotNumber)}`
      );
      const data = await res.json();

      if (data.success) {
        // ✅ Convert values
        const rate = parseFloat(data.rate) || 0;
        const issueMeter = parseFloat(data.issue_meter) || 0;
        const rejection = parseFloat(data.rejection) || 0;

        // ✅ Calculate Safi Meter
        const safiMeter = issueMeter - rejection;

        // ✅ Calculate Total Amount
        const totalAmount = safiMeter * rate;

        // ✅ Show results in fields
        document.getElementById("safi_meter").value = safiMeter.toFixed(2);
        document.getElementById("rate").value = rate.toFixed(2); // fill rate
        document.getElementById("total_amount").value = totalAmount.toFixed(2);
      } else {
        document.getElementById("safi_meter").value = '';
        document.getElementById("rate").value = '';
        document.getElementById("total_amount").value = '';
      }
    } catch (err) {
      document.getElementById("safi_meter").value = '';
      document.getElementById("rate").value = '';
      document.getElementById("total_amount").value = '';
    }
  } else {
    // Reset when selections incomplete
    document.getElementById("safi_meter").value = '';
    document.getElementById("rate").value = '';
    document.getElementById("total_amount").value = '';
  }
}


