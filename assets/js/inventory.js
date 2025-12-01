document.addEventListener('DOMContentLoaded', function () {
    const saveBtn = document.getElementById('saveInventoryBtn');
    const inventoryForm = document.getElementById('inventoryForm');
    const inventoriesTbody = document.getElementById('inventoriesTbody');
    const vendorSelect = document.getElementById('vendorSelect');
    const warehouseSelect = document.getElementById('warehouseSelect');

    let editingId = null;

    // 🔹 Add row to table
    function addInventoryRow(inv) {
        const tr = document.createElement('tr');
        tr.dataset.id = inv.id;
        tr.innerHTML = `
        <td class="row-index"></td>
        <td class="name">${inv.name}</td>
        <td class="unit">${inv.unit}</td>
        <td class="measurement">${inv.measurement}</td>
        <td class="lot_number">${inv.lot_number}</td>
        <td class="vendor_name">${inv.vendor_name ?? '-'}</td>
        <td class="cost">${inv.cost}</td>
        <td class="type">${inv.type}</td>
        <td>
            <button class="btn btn-sm btn-warning editBtn">Edit</button>
            <button class="btn btn-sm btn-danger deleteBtn">Delete</button>
        </td>
    `;
        inventoriesTbody.appendChild(tr);

        // ✅ Recalculate numbering
        updateInventoryNumbers();

        // 🟡 Edit
        tr.querySelector('.editBtn').addEventListener('click', function () {
            editingId = inv.id;
            for (let key in inv) {
                if (inventoryForm[key]) {
                    inventoryForm[key].value = inv[key];
                }
            }
            loadVendors(inv.vendor_id);
            loadWarehouses(inv.warehouse_id);
            new bootstrap.Modal(document.getElementById('inventoryModal')).show();
        });

        // 🔴 Delete
        tr.querySelector('.deleteBtn').addEventListener('click', function () {
            if (confirm('Delete this inventory?')) {
                fetch('inventories.php', {
                    method: 'DELETE',
                    body: new URLSearchParams({ id: inv.id })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            tr.remove();
                            updateInventoryNumbers(); // ✅ Renumber after delete
                        } else {
                            alert('Failed to delete inventory');
                        }
                    });
            }
        });
    }

    // ✅ Helper function to update numbering
    function updateInventoryNumbers() {
        document.querySelectorAll('#inventoriesTbody tr').forEach((row, index) => {
            row.querySelector('.row-index').textContent = index + 1;
        });
    }


    // 🔹 Load warehouses
    function loadWarehouses(selectedId = null) {
        fetch('warehouses.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    warehouseSelect.innerHTML = '<option value="">Select Warehouse</option>';
                    data.data.forEach(w => {
                        warehouseSelect.innerHTML += `<option value="${w.id}" ${selectedId == w.id ? "selected" : ""}>${w.name}</option>`;
                    });
                }
            });
    }

    // 🔹 Load vendors
    function loadVendors(selectedId = null) {
        fetch('vendors.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    vendorSelect.innerHTML = '<option value="">Select Vendor</option>';
                    data.data.forEach(v => {
                        vendorSelect.innerHTML += `<option value="${v.id}" ${selectedId == v.id ? "selected" : ""}>${v.name}</option>`;
                    });
                }
            });
    }

    // 🔹 Load existing inventories
    fetch('inventories.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                inventoriesTbody.innerHTML = "";
                data.data.forEach(inv => addInventoryRow(inv));
            }
        });

    // 🔹 Save (Add / Edit)
    saveBtn.addEventListener('click', function () {
        const formData = new FormData(inventoryForm);
        if (editingId) formData.append('id', editingId);

        fetch('inventories.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Inventory saved successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('inventoryModal')).hide();
                    inventoryForm.reset();
                    editingId = null;
                    loadVendors();
                    loadWarehouses();
                    fetch('inventories.php')
                        .then(res => res.json())
                        .then(all => {
                            if (all.success) {
                                inventoriesTbody.innerHTML = "";
                                all.data.forEach(inv => addInventoryRow(inv));
                            }
                        });
                } else {
                    alert('Error saving inventory');
                }
            });
    });

    // 🔹 Preload vendor + warehouse lists when opening modal
    document.querySelector('[data-bs-target="#inventoryModal"]').addEventListener('click', () => {
        editingId = null;
        inventoryForm.reset();
        loadVendors();
        loadWarehouses();
    });

    setTimeout(() => {
        $('#inventoriesTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            responsive: true,
            dom: 'Bfrtip', // buttons + filter + pagination
            buttons: [
                { extend: 'csvHtml5', title: 'Inventories_List' },
                { extend: 'excelHtml5', title: 'Inventories_List' },
                { extend: 'pdfHtml5', title: 'Inventories_List' },
                { extend: 'print', title: 'Inventories_List' }
            ]
        });
    }, 800);
});
