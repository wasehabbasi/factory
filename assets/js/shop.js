document.addEventListener('DOMContentLoaded', function () {
    const saveBtn = document.getElementById('saveShopBtn');
    const shopForm = document.getElementById('shopForm');
    const shopsTbody = document.getElementById('shopsTbody');
    let editingId = null;
    let dataTable = null;
    let shopInventory = [];

    // Initialize DataTable
    function initDataTable() {
        if ($.fn.DataTable.isDataTable('#shopsTable')) $('#shopsTable').DataTable().destroy();
        dataTable = $('#shopsTable').DataTable({
            dom: 'Bfrtip',
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            paging: true, searching: true, ordering: true,
            buttons: [
                { extend: 'excelHtml5', text: 'Excel', className: 'btn btn-sm btn-success' },
                { extend: 'csvHtml5', text: 'CSV', className: 'btn btn-sm btn-primary' },
                { extend: 'pdfHtml5', text: 'PDF', className: 'btn btn-sm btn-danger' },
                { extend: 'print', text: 'Print', className: 'btn btn-sm btn-secondary' }
            ],
            language: { lengthMenu: "Show _MENU_ entries per page", paginate: { previous: "← Prev", next: "Next →" }, search: "Search:" }
        });
    }

    // Add Shop row
    let shopCounter = 1;

    function addShopRow(shop) {
        const tr = document.createElement('tr');
        tr.dataset.id = shop.id;

        tr.innerHTML = `
        <td>${shopCounter++}</td>
        <td class="name">${shop.name}</td>
        <td class="address">${shop.address}</td>
        <td class="phone_number">${shop.phone_number}</td>
        <td class="image_url">${shop.image_url ? `<img src="${shop.image_url}" width="50">` : ''}</td>
        <td>
            <button class="btn btn-sm btn-warning editBtn">Edit</button>
            <button class="btn btn-sm btn-danger deleteBtn">Delete</button>
            <button class="btn btn-sm btn-info generateInvoiceBtn">Generate Invoice</button>
        </td>
    `;
        shopsTbody.appendChild(tr);

        // Total qty & remaining
        // fetch(`shop_inventory.php?action=total_qty&shop_id=${shop.id}`)
        //     .then(res => res.json())
        //     .then(data => {
        //         tr.querySelector('.total_qty').textContent = data.total_qty || 0;
        //         tr.querySelector('.remaining_qty').textContent = data.remaining_qty || 0;
        //     });

        // Edit
        tr.querySelector('.editBtn').addEventListener('click', () => {
            editingId = shop.id;
            shopForm.id.value = shop.id;
            shopForm.name.value = shop.name;
            shopForm.address.value = shop.address;
            shopForm.phone_number.value = shop.phone_number;
            shopForm.image_url.value = shop.image_url || '';
            new bootstrap.Modal(document.getElementById('shopModal')).show();
        });

        // Delete
        tr.querySelector('.deleteBtn').addEventListener('click', () => {
            if (confirm('Delete this shop?')) {
                fetch('shops.php', { method: 'DELETE', body: new URLSearchParams({ id: shop.id }) })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            tr.remove();
                            if (dataTable) dataTable.row($(tr)).remove().draw();
                            // Recalculate numbering
                            resetShopNumbering();
                        } else alert('Failed to delete shop');
                    });
            }
        });

        // Generate Invoice
        tr.querySelector('.generateInvoiceBtn').addEventListener('click', function () {
            var tr = $(this).closest('tr');
            var id = tr.data('id');
            openInvoiceModal(id);
        });
    }

    function resetShopNumbering() {
        shopCounter = 1;
        document.querySelectorAll('#shopsTbody tr').forEach(tr => {
            tr.querySelector('td:first-child').textContent = shopCounter++;
        });
    }


    // Load Shops
    async function loadShops() {
        try {
            const res = await fetch('shops.php');
            const data = await res.json();

            // destroy old DataTable if exists
            if ($.fn.DataTable.isDataTable('#shopsTable')) $('#shopsTable').DataTable().destroy();

            shopsTbody.innerHTML = '';
            if (data.success && Array.isArray(data.data)) data.data.forEach(addShopRow);

            // re-init DataTable
            initDataTable();
        } catch (err) { console.error(err); }
    }


    // ------------------- INVOICE LOGIC -------------------
    function openInvoiceModal(shopId) {

        document.getElementById('invoiceShopId').value = shopId;
        const tbody = document.querySelector('#invoiceItemsTable tbody');
        tbody.innerHTML = '';
        document.getElementById('invoiceGrandTotal').textContent = '0.00';
        document.getElementById('invoiceCustomer').value = '';
        document.getElementById('invoiceDate').value = new Date().toISOString().slice(0, 10);

        fetch(`shop_inventory.php?action=list_transfers&shop_id=${shopId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return alert('No inventory found');
                shopInventory = data.data;
                addInvoiceRow();
                new bootstrap.Modal(document.getElementById('invoiceModal')).show();
            });
    }

    async function addInvoiceRow() {
        const tbody = document.querySelector('#invoiceItemsTable tbody');
        const tr = document.createElement('tr');

        // 🟢 Fetch designs from API (once per row)
        const res = await fetch('get_designs.php');
        const { data: designs } = await res.json();

        let designOptions = '<option value="">Select Design #</option>';
        designs.forEach(d => {
            designOptions += `<option 
            value="${d.design_number}" 
            data-productid="${d.product_id}" 
            data-productname="${d.product_name}" 
            data-lot="${d.lot_number}"
        >${d.design_number}</option>`;
        });

        tr.innerHTML = `
        <td>
            <select class="form-control designSelect">${designOptions}</select>
        </td>
        <td class="productName">—</td>
        <td><input type="number" class="form-control cuttingInput" step="0.1" min="0" value="0"></td>
        <td><input type="number" class="form-control totalSuitsInput" min="1" value="1"></td>
        <td><input type="number" class="form-control qtyInput" readonly></td>
        <td><input type="number" class="form-control rateInput" min="1" value="100"></td>
        <td class="lineTotal">0.00</td>
        <td><button type="button" class="btn btn-sm btn-danger removeRowBtn">Remove</button></td>
        `;

        tbody.appendChild(tr);

        const designSelect = tr.querySelector('.designSelect');
        const productNameTd = tr.querySelector('.productName');
        const qtyInput = tr.querySelector('.qtyInput');
        const rateInput = tr.querySelector('.rateInput');
        const lineTotalTd = tr.querySelector('.lineTotal');
        const cuttingInput = tr.querySelector('.cuttingInput');
        const totalSuitsInput = tr.querySelector('.totalSuitsInput');


        async function updateLineTotal() {
            const cutting = parseFloat(cuttingInput.value) || 0;
            const suits = parseFloat(totalSuitsInput.value) || 0;
            const qty = cutting * suits; // ✅ Auto calculate total qty
            const rate = parseFloat(rateInput.value) || 0;
            qtyInput.value = qty.toFixed(2); // show in qty field
            lineTotalTd.textContent = (qty * rate).toFixed(2);
            updateGrandTotal();
        }


        // 🟢 When design selected → auto-fill product name & rate
        designSelect.addEventListener('change', async () => {
            const sel = designSelect.selectedOptions[0];
            const designNo = sel.value;
            const productId = sel.dataset.productid;
            const productName = sel.dataset.productname;
            const lot = sel.dataset.lot;

            if (!designNo) return;

            productNameTd.textContent = productName;

            // Fetch remaining quantity (optional)
            try {
                const res = await fetch(`get_quantity.php?lot_number=${lot}`);
                const data = await res.json();

                if (data.success) {
                    qtyInput.max = data.quantity;
                }
            } catch (err) {
                console.error('Error fetching quantity:', err);
            }

            updateLineTotal();

            // 🟢 Save hidden values for backend (use dataset)
            tr.dataset.productid = productId;
            tr.dataset.design = designNo;
            tr.dataset.lot = lot;
        });

        cuttingInput.addEventListener('input', updateLineTotal);
        totalSuitsInput.addEventListener('input', updateLineTotal);
        qtyInput.addEventListener('input', updateLineTotal);
        rateInput.addEventListener('input', updateLineTotal);

        tr.querySelector('.removeRowBtn').addEventListener('click', () => {
            tr.remove();
            updateGrandTotal();
        });
    }




    function updateGrandTotal() {
        let total = 0;
        document.querySelectorAll('#invoiceItemsTable tbody tr').forEach(tr => {
            total += parseFloat(tr.querySelector('.lineTotal').textContent) || 0;
        });
        document.getElementById('invoiceGrandTotal').textContent = total.toFixed(2);
    }

    document.getElementById('addInvoiceRowBtn').addEventListener('click', addInvoiceRow);

    document.getElementById('saveInvoiceBtn').addEventListener('click', async () => {
        const shopId = document.getElementById('invoiceShopId').value;
        const customerName = document.getElementById('invoiceCustomer').value;
        const paandiName = document.getElementById('invoicePaandi').value;
        const date = document.getElementById('invoiceDate').value;

        const items = [];
        document.querySelectorAll('#invoiceItemsTable tbody tr').forEach(tr => {
            let $selected = $(this).find('option:selected');

            let designNumber = $selected.val();
            const productId = tr.dataset.productid;
            const cutting = parseFloat(tr.querySelector('.cuttingInput').value) || 0;
            const totalSuits = parseInt(tr.querySelector('.totalSuitsInput').value) || 0;
            const qty = parseFloat(tr.querySelector('.qtyInput').value) || 0;
            const rate = parseFloat(tr.querySelector('.rateInput').value) || 0;

            if (productId && qty > 0) {
                items.push({
                    Design: designNumber,
                    product_id: productId,
                    cutting,
                    total_suits: totalSuits,
                    qty,
                    rate
                });
            }
        });

        if (!customerName || !paandiName || items.length === 0) {
            return alert('Please fill all fields and add at least one product');
        }

        try {
            const res = await fetch('save_invoice.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    shop_id: shopId,
                    customer_name: customerName,
                    paandi_name: paandiName,
                    date,
                    items
                })
            });

            const data = await res.json();

            if (data.success) {
                alert('Invoice created successfully!');
                console.log("Items: ", items);
                this.location.reload();

                // 🟩 Inventory adjustment ko separate try-catch mein lo
                try {
                    for (const item of items) {
                        const invRes = await fetch("shop_inventory.php?action=adjust_quantity", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({
                                wts_id: selectedWtsId,
                                product_id: item.product_id,
                                shop_id: shopId,
                                adjusted_qty: -Math.abs(item.qty),
                                note: `Issued in invoice #${data.invoice_id}`
                            })
                        });

                        // Debug: dekh lo response kya aaya
                        const invText = await invRes.text();
                        console.log("Inventory response:", invText);
                    }
                } catch (invErr) {
                    // console.error("Inventory adjustment error:", invErr);
                    // alert("Invoice saved, but inventory adjustment failed!");
                }

                // 🟩 Baaki kaam jaari rakho
                window.open(`shop_invoice_pdf.php?id=${data.invoice_id}`, "_blank");
                bootstrap.Modal.getInstance(document.getElementById('invoiceModal')).hide();
                loadShops();

            } else {
                alert('Error: ' + (data.message || 'Unable to save invoice.'));
            }

        } catch (err) {
            // console.error("Main invoice save error:", err);
            // alert('Error saving invoice!');
        }

    });

    // ------------------- SAVE/UPDATE SHOP -------------------
    saveBtn.addEventListener('click', async function () {
        const phone = shopForm.phone_number.value.trim();

        // ✅ Validate Pakistani phone number (11 digits)
        if (!/^[0-9]{11}$/.test(phone)) {
            alert('Please enter a valid 11-digit Pakistani phone number (e.g. 03001234567)');
            return; // stop execution
        }

        // ✅ Validate rest of the form
        if (!shopForm.checkValidity()) {
            shopForm.reportValidity();
            return;
        }

        const formData = new FormData(shopForm);
        if (editingId) formData.append('id', editingId);

        try {
            const res = await fetch('shops.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                await loadShops();
                loadShopInvoices();

                // ✅ Hide correct modal safely
                const modalEl = document.getElementById('shopModal');
                const modalInstance =
                    bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInstance.hide();

                shopForm.reset();
                editingId = null;
            } else {
                alert(data.message || 'Error saving shop');
            }
        } catch (err) {
            console.error(err);
            alert('Error saving shop!');
        }
    });


    loadShops();

    function loadShopInvoices() {
        fetch('shops.php?action=get_invoices')
            .then(res => res.json())
            .then(response => {
                const tbody = document.getElementById('shopInvoicesTbody');
                tbody.innerHTML = '';

                const data = response && Array.isArray(response.data) ? response.data : [];

                if (data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center">No invoices found</td></tr>`;
                    return;
                }

                data.forEach((inv, i) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                    <td>${i + 1}</td>
                    <td>${formatDate(inv.date) || ''}</td>
                    <td>${inv.customer_name || ''}</td>
                    <td>${inv.paandi_name || ''}</td>
                    <td>${inv.grand_total || 0}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="window.open('shop_invoice_pdf.php?id=${inv.id}', '_blank')">View</button>
                    </td>
                `;
                    tbody.appendChild(tr);
                });

                if ($.fn.DataTable.isDataTable('#shopInvoicesTable')) {
                    $('#shopInvoicesTable').DataTable().destroy();
                }

                $('#shopInvoicesTable').DataTable({
                    pageLength: 5,
                    lengthChange: false,
                    searching: true,
                    ordering: true,
                    info: true,
                    responsive: true,
                    dom: 'Bfrtip', // Required for buttons
                    buttons: [
                        { extend: 'csvHtml5', footer: true, title: 'Shop_Invoices_' + new Date().toISOString().split('T')[0] },
                        { extend: 'excelHtml5', footer: true, title: 'Shop_Invoices_' + new Date().toISOString().split('T')[0] },
                        { extend: 'pdfHtml5', footer: true, title: 'Shop_Invoices_' + new Date().toISOString().split('T')[0] },
                        { extend: 'print', footer: true, title: 'Shop Invoices' }
                    ],
                    footerCallback: function (row, data, start, end, display) {
                        const api = this.api();

                        // Helper to parse numbers
                        const intVal = i => typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : (typeof i === 'number' ? i : 0);

                        // Total of Grand Total column (column index 4)
                        const totalGrand = api.column(4, { search: 'applied' }).data()
                            .reduce((a, b) => intVal(a) + intVal(b), 0);

                        // Update footer
                        $(api.column(4).footer()).html(totalGrand.toFixed(2));
                    }
                });
            })
            .catch(err => console.error('Error loading invoices:', err));
    }

    loadShopInvoices();

    const formatDate = (dateStr) => {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        if (isNaN(date)) return dateStr; // if invalid, return as is
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    };

});