document.addEventListener("DOMContentLoaded", () => {
  loadWarehouses();
  loadShops();
  loadProducts();
  loadTransfers();
  loadShopSummary();
  loadUniqueShopInventory();
  loadDesignNumbers();

  // ✅ Add this section right below
  const designSelect = document.getElementById("designSelect");
  const productName = document.getElementById("productName");
  const productId = document.getElementById("productId");
  const lotNumber = document.getElementById("lotNumber");
  const nagField = document.getElementById("nagField");
  const measurementField = document.getElementById("measurementField");

  if (designSelect) {
    designSelect.addEventListener("change", function () {
      const option = this.selectedOptions[0];
      if (option && option.value) {
        productName.value = option.getAttribute("data-product-name");
        productId.value = option.getAttribute("data-product-id");
        lotNumber.value = option.getAttribute("data-lot");
        nagField.value = option.getAttribute("data-nag");
        measurementField.value = option.getAttribute("data-measurement");
      } else {
        productName.value = "";
        productId.value = "";
        lotNumber.value = "";
        nagField.value = "";
        measurementField.value = "";
      }
    });
  }

  document.getElementById("transferForm").addEventListener("submit", async function (e) {
    e.preventDefault();
    const res = await fetch("shop_inventory.php?action=save_transfer", { method: "POST", body: new FormData(this) });
    const d = await res.json();
    if (d.success) {
      const modalEl = document.getElementById("transferModal");
      const modal = bootstrap.Modal.getInstance(modalEl);

      // 🧹 Clear form
      this.reset();

      // 👇 Hide modal
      modal.hide();

      // ✅ Remove these from here
      // await loadTransfers();
      // await loadShopSummary();
      // await loadUniqueShopInventory();


    } else {
      alert(d.message || "Error saving transfer");
    }


  });

  // invoice qty -> calculate total
  document.getElementById("invoice_rate").addEventListener("input", recalcInvoiceTotal);
  document.getElementById("invoice_qty").addEventListener("input", recalcInvoiceTotal);

  document.getElementById("invoiceForm").addEventListener("submit", async function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    const res = await fetch("shop_inventory.php?action=save_invoice", { method: "POST", body: fd });
    const d = await res.json();
    if (d.success) {
      bootstrap.Modal.getInstance(document.getElementById("invoiceModal")).hide();
      // this.reset();

      // 🔄 Refresh related tables
      console.log("✅ Refreshing transfers...");
      await loadTransfers();
      console.log("✅ Transfers refreshed!");

      console.log("✅ Refreshing shop summary...");
      await loadShopSummary();
      console.log("✅ Shop summary refreshed!");

      console.log("✅ Refreshing unique shop inventory...");
      await loadUniqueShopInventory();
      console.log("✅ Unique inventory refreshed!");


      // 🧾 Open PDF automatically
      window.open(`shop_invoice_pdf.php?id=${encodeURIComponent(d.invoice_id)}`, '_blank');

      alert("Invoice saved and tables refreshed!");
    } else {
      alert(d.message || "Error saving invoice");
    }

  });
});

function recalcInvoiceTotal() {
  const q = parseFloat(document.getElementById("invoice_qty").value || 0);
  const r = parseFloat(document.getElementById("invoice_rate").value || 0);
  document.getElementById("invoice_total").value = (q * r).toFixed(2);
}

async function loadTransfers() {
  try {
    const res = await fetch("shop_inventory.php?action=list_transfers");
    const d = await res.json();
    const tableSelector = '#shopTransferTable';

    // Destroy previous DataTable
    if ($.fn.DataTable.isDataTable(tableSelector)) $(tableSelector).DataTable().clear().destroy();

    const tbody = document.getElementById("transferTbody");
    tbody.innerHTML = "";

    let rows = "";
    let totalQty = 0;

    if (d.success && d.data.length) {
      d.data.forEach((t, i) => {
        const qty = parseFloat(t.qty) || 0;
        totalQty += qty;

        rows += `<tr>
          <td>${i + 1}</td>
          <td>${t.date}</td>
          <td>${escapeHtml(t.warehouse_name || '')}</td>
          <td>${escapeHtml(t.product_name || '')}</td>
          <td>${escapeHtml(t.measurement || '')}</td>
          <td>${escapeHtml(t.shop_name || '')}</td>
          <td>${escapeHtml(t.design_number || '')}</td>
          <td>${escapeHtml(t.nag || '')}</td>
          <td>${qty.toFixed(2)}</td>
        </tr>`;
      });
    } else {
      // rows = `<tr><td colspan="9" class="text-center">No transfers found</td></tr>`;
      const columnCount = tbody ? tbody.closest("table").querySelectorAll("thead th").length : 9;
      let dummyCells = "";
      for (let i = 0; i < columnCount; i++) dummyCells += `<td class="text-center text-muted">0</td>`;
      rows = `<tr class="dummy-row">${dummyCells}</tr>`;
    }

    tbody.innerHTML = rows;
    document.getElementById("transferTotalQty").textContent = totalQty.toFixed(2);

    // Reinitialize DataTable
    initDataTable(tableSelector);
  } catch (err) {
    console.error(err);

    const tbody = document.getElementById("transferTbody");
    tbody.innerHTML = `<tr><td colspan="9" class="text-center">Error loading transfers</td></tr>`;
    document.getElementById("transferTotalQty").textContent = "0.00";
  }
}

async function loadDesignNumbers() {
  try {
    const res = await fetch("shop_inventory.php?action=get_design_numbers");
    const data = await res.json();

    if (data.success) {
      const select = document.getElementById("designSelect");
      select.innerHTML = `<option value="">Select Design Number</option>`;
      data.data.forEach(d => {
        select.innerHTML += `<option 
          value="${d.design_number}" 
          data-product-id="${d.product_id}" 
          data-product-name="${d.product_name}" 
          data-lot="${d.lot_number}" 
          data-measurement="${d.measurement}"
          data-nag="${d.nag}">
            ${d.design_number}
          </option>`;
      });
    } else {
      select.innerHTML = `<option value="">No design numbers found</option>`;
    }
  } catch (err) {
    console.error(err);
    const select = document.getElementById("designSelect");
    select.innerHTML = `<option value="">Error loading design numbers</option>`;
  }
}



function initDataTable(selector = '#shopTransferTable') {
  if (window.jQuery && $.fn.DataTable) {
    if ($.fn.DataTable.isDataTable(selector)) $(selector).DataTable().destroy();
    $(selector).DataTable({ dom: 'Bfrtip', pageLength: 10, buttons: ['csvHtml5', 'pdfHtml5', 'print'] });
  }
}

async function loadShopSummary() {
  try {
    const res = await fetch("shop_inventory.php?action=list_shop_totals");
    const d = await res.json();
    const tableSelector = '#shopSummaryTable';

    if ($.fn.DataTable.isDataTable(tableSelector)) $(tableSelector).DataTable().clear().destroy();

    const tbody = document.getElementById("shopSummaryTbody");
    tbody.innerHTML = "";

    let rows = "";
    let totalQty = 0;

    if (d.success && d.data.length) {
      d.data.forEach((shop, i) => {
        const qty = parseFloat(shop.total_qty) || 0;
        totalQty += qty;

        rows += `<tr>
          <td>${i + 1}</td>
          <td>${shop.shop_name}</td>
          <td>${qty.toFixed(2)}</td>
        </tr>`;
      });
    } else {
      rows = `<tr><td colspan="3" class="text-center">No data found</td></tr>`;
    }

    tbody.innerHTML = rows;
    document.getElementById("shopSummaryTotal").textContent = totalQty.toFixed(2);

    initDataTable(tableSelector);
  } catch (err) {
    // console.error(err);

    const tbody = document.getElementById("shopSummaryTbody");
    tbody.innerHTML = `<tr><td colspan="3" class="text-center">Error loading shop summary</td></tr>`;
    document.getElementById("shopSummaryTotal").textContent = "0.00";
  }
}

async function openInvoiceModal(transferId) {
  // fetch transfer details
  try {
    const res = await fetch(`shop_inventory.php?action=get_transfer&id=${transferId}`);
    const d = await res.json();
    if (!d.success) return alert("Transfer not found");

    const t = d.data;
    document.getElementById("invoice_transfer_id").value = t.id;
    document.getElementById("invoice_customer").value = t.shop_name;
    document.getElementById("invoice_available").value = parseFloat(t.remaining_qty ?? t.qty).toFixed(2);
    document.getElementById("invoice_qty").value = '';
    document.getElementById("invoice_detail").value = `${t.product_name} (Lot: ${t.lot_number})`;
    document.getElementById("invoice_rate").value = '';
    document.getElementById("invoice_total").value = '0.00';
    document.getElementById("invoice_date").value = new Date().toISOString().slice(0, 10);

    new bootstrap.Modal(document.getElementById("invoiceModal")).show();
  } catch (err) {
    console.error(err);
    alert("Failed to open invoice modal");
  }
}

async function loadWarehouses() {
  try {
    const res = await fetch("warehouses.php");
    const d = await res.json();
    let opts = '<option value="">Select Warehouse</option>';
    d.data.forEach(w => opts += `<option value="${w.id}">${w.name}</option>`);
    document.getElementById("warehouseSelect").innerHTML = opts;
  } catch (e) { }
}

async function loadShops() {
  try {
    const res = await fetch("shops.php");
    const d = await res.json();
    let opts = '<option value="">Select Shop</option>';
    d.data.forEach(s => opts += `<option value="${s.id}">${s.name}</option>`);
    document.getElementById("shopSelect").innerHTML = opts;
  } catch (e) { }
}

async function loadProducts() {
  try {
    const res = await fetch("products.php");
    const d = await res.json();
    let opts = '<option value="">Select Product</option>';
    d.data.forEach(p => opts += `<option value="${p.id}">${p.name}</option>`);
    // productSelect may not exist on simplified pages; guard it
    const el = document.getElementById("productSelect");
    if (el) el.innerHTML = opts;
  } catch (e) { }
}

// small html escape
function escapeHtml(str) {
  if (!str && str !== 0) return '';
  return String(str).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
}

async function loadUniqueShopInventory() {
  try {
    const res = await fetch("shop_inventory.php?action=list_unique_shop_inventory");
    const d = await res.json();
    let rows = "";
    let totalQty = 0;

    if (d.success && d.data.length) {
      d.data.forEach((r, i) => {
        const qty = parseFloat(r.total_quantity) || 0;
        totalQty += qty;

        rows += `
        <tr data-product="${r.product_id}" data-shop="${r.shop_id}" data-design="${r.design_number}" data-wts="${r.wts_id}">
          <td>${i + 1}</td>
          <td>${escapeHtml(r.shop_name || '')}</td>
          <td>${escapeHtml(r.product_name || '')}</td>
          <td>${escapeHtml(r.measurement || '')}</td>
          <td>${escapeHtml(r.design_number || '')}</td>
          <td contenteditable="true" class="editable-nag">${escapeHtml(r.nag || '')}</td>
          <td>${qty.toFixed(2)}</td>
          <td><button class="btn btn-sm btn-primary save-nag-btn">Save</button></td>
        </tr>`;
      });
    } else {
      rows = `<tr><td colspan="8" class="text-center">No records found</td></tr>`;
    }

    document.getElementById("shopInventoryTbody").innerHTML = rows;
    document.getElementById("shopInventoryTotal").textContent = totalQty.toFixed(2);

    // Attach click event to Save buttons
    document.querySelectorAll(".save-nag-btn").forEach(btn => {
      btn.addEventListener("click", async function () {
        const row = this.closest("tr");
        const product_id = row.dataset.product;
        const shop_id = row.dataset.shop;
        const design_number = row.dataset.design;
        const wts_id = row.dataset.wts;
        const nagCell = row.querySelector(".editable-nag");
        const new_nag = nagCell.textContent.trim();

        try {
          const res = await fetch("shop_inventory.php?action=update_nag_temp", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              product_id,
              shop_id,
              design_number,
              old_nag: "", // optional
              new_nag,
              wts_id
            })
          });

          const data = await res.json();
          if (data.success) {
            alert("✅ Nag saved successfully!");
          } else {
            alert("❌ Failed to save Nag!");
          }
        } catch (err) {
          console.error(err);
          alert("Error saving Nag!");
        }
      });
    });

    initDataTable('#shopInventoryTable');
  } catch (err) {
    // console.error(err);
    const tbody = document.getElementById("shopInventoryTbody");
    tbody.innerHTML = `<tr><td colspan="8" class="text-center">Error loading shop inventory</td></tr>`;
    document.getElementById("shopInventoryTotal").textContent = "0.00";
  }

  document.getElementById("transferModal").addEventListener("hidden.bs.modal", async () => {
    await loadTransfers();
    await loadShopSummary();
    await loadUniqueShopInventory();
    console.log("Tables refreshed after modal closed!");
  });

}