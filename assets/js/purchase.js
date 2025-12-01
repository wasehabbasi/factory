document.addEventListener("DOMContentLoaded", function () {
    const saveBtn = document.getElementById("savePurchaseBtn");
    const purchaseForm = document.getElementById("purchaseForm");
    const purchaseTbody = document.getElementById("purchaseTbody");
    const vendorSelect = document.getElementById("vendorSelect");
    const purchaseTableSelector = "#purchasesTable";
    let editingId = null;
    let purchaseCounter = 1;

    // -------------------- DataTable --------------------
    function initDataTable() {
        if ($.fn.DataTable.isDataTable(purchaseTableSelector)) {
            $(purchaseTableSelector).DataTable().destroy();
        }
        $(purchaseTableSelector).DataTable({
            dom: 'Bfrtip',
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            buttons: [
                { extend: 'excelHtml5', text: 'Excel', title: 'Purchases_List', className: 'btn btn-sm btn-primary' },
                { extend: 'csvHtml5', text: 'CSV', title: 'Purchases_List', className: 'btn btn-sm btn-info' },
                { extend: 'pdfHtml5', text: 'PDF', title: 'Purchases_List', className: 'btn btn-sm btn-danger' },
                { extend: 'print', text: 'Print', title: 'Purchases List', className: 'btn btn-sm btn-success' }
            ]
        });
    }

    // -------------------- Add Row --------------------
    function addPurchaseRow(purchase) {
        const tr = document.createElement("tr");
        tr.dataset.id = purchase.id;

        tr.innerHTML = `
            <td>${purchaseCounter++}</td>
            <td class="date">${purchase.date}</td>
            <td class="vendor">${purchase.vendor_name || ''}</td>
            <td class="rate">${purchase.rate}</td>
            <td class="lot">${purchase.lot_number}</td>
            <td class="measurement">${purchase.measurement}</td>
            <td class="product">${purchase.product_name}</td>
            <td class="width">${purchase.width}</td>
            <td class="thaan">${purchase.thaan}</td>
            <td class="issue">${purchase.issue_meter}</td>
            <td>
                <button class="btn btn-sm btn-warning editBtn">Edit</button>
                <button class="btn btn-sm btn-danger deleteBtn">Delete</button>
            </td>
        `;
        purchaseTbody.appendChild(tr);

        // Edit
        tr.querySelector(".editBtn").addEventListener("click", () => {
            editingId = purchase.id;
            Object.keys(purchaseForm.elements).forEach(k => {
                const el = purchaseForm.elements[k];
                if (el && purchase[el.name] !== undefined) {
                    el.value = purchase[el.name];
                }
            });
            new bootstrap.Modal(document.getElementById("purchaseModal")).show();
        });

        // Delete
        tr.querySelector(".deleteBtn").addEventListener("click", () => {
            if (confirm("Delete this purchase?")) {
                fetch("purchase.php", {
                    method: "DELETE",
                    body: new URLSearchParams({ id: purchase.id })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            tr.remove();
                            resetPurchaseNumbering();
                        } else alert("Failed to delete purchase");
                    });
            }
        });
    }

    // -------------------- Reset Numbering --------------------
    function resetPurchaseNumbering() {
        purchaseCounter = 1;
        document.querySelectorAll("#purchaseTbody tr").forEach(tr => {
            tr.querySelector("td:first-child").textContent = purchaseCounter++;
        });
    }

    // -------------------- Load Vendors --------------------
    function loadVendors() {
        fetch("vendors.php")
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    vendorSelect.innerHTML = '<option value="">Select Vendor</option>';
                    data.data.forEach(v => {
                        vendorSelect.innerHTML += `<option value="${v.id}">${v.name}</option>`;
                    });
                }
            });
    }

    // -------------------- Load Purchases --------------------
    async function loadPurchases() {
        const res = await fetch("purchase.php");
        const data = await res.json();

        if (!data.success) return;
        purchaseTbody.innerHTML = "";
        purchaseCounter = 1;

        let totalRate = 0, totalWidth = 0, totalThaan = 0, totalIssue = 0;

        data.data.forEach(p => {
            addPurchaseRow(p);
            totalRate += parseFloat(p.rate) || 0;
            totalWidth += parseFloat(p.width) || 0;
            totalThaan += parseFloat(p.thaan) || 0;
            totalIssue += parseFloat(p.issue_meter) || 0;
        });

        // Update totals
        document.getElementById("totalRate").textContent = totalRate.toFixed(2);
        document.getElementById("totalWidth").textContent = totalWidth.toFixed(2);
        document.getElementById("totalThaan").textContent = totalThaan.toFixed(2);
        document.getElementById("totalIssue").textContent = totalIssue.toFixed(2);

        initDataTable();
    }

    // -------------------- Save Purchase --------------------
    // Save Purchase
    saveBtn.addEventListener("click", async () => {
        const formData = new FormData(purchaseForm);
        if (editingId) formData.append("id", editingId);

        try {
            const res = await fetch("purchase.php", { method: "POST", body: formData });
            const data = await res.json();

            if (data.success) {
                editingId = null;
                purchaseForm.reset();

                const modalEl = document.getElementById("purchaseModal");
                const modal = bootstrap.Modal.getInstance(modalEl);

                if (modal) {
                    modal.hide();

                    // Wait for modal to fully close animation
                    modalEl.addEventListener('hidden.bs.modal', async function handler() {
                        await loadPurchases();
                        location.reload();
                        modalEl.removeEventListener('hidden.bs.modal', handler);
                    });
                } else {
                    await loadPurchases();
                }
            } else {
                alert(data.message || "Error saving purchase");
            }
        } catch (err) {
            console.error("Error saving purchase:", err);
            alert("Error saving purchase. Check console for details.");
        }
    });

    // -------------------- Modal Close Event --------------------
    const purchaseModalEl = document.getElementById("purchaseModal");
    purchaseModalEl.addEventListener('hidden.bs.modal', () => {
        loadPurchases(); // Auto-refresh table whenever modal closes
    });

    // -------------------- Initial Load --------------------
    loadVendors();
    loadPurchases();
});
