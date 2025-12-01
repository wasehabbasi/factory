document.addEventListener('DOMContentLoaded', function () {
    const saveBtn = document.getElementById('saveWarehouseBtn');
    const warehouseForm = document.getElementById('warehouseForm');
    const tbody = document.getElementById('warehousesTbody');
    let editingId = null;

    function addWarehouseRow(warehouse) {
        const tr = document.createElement('tr');
        tr.dataset.id = warehouse.id;
        tr.innerHTML = `
        <td class="row-index"></td>
        <td class="name">${warehouse.name}</td>
        <td class="address">${warehouse.address}</td>
        <td class="phone">${warehouse.phone}</td>
        <td>
            <button class="btn btn-sm btn-warning editBtn">Edit</button>
            <button class="btn btn-sm btn-danger deleteBtn">Delete</button>
        </td>
    `;
        tbody.appendChild(tr);
        updateWarehouseNumbers();

        // --- Edit ---
        tr.querySelector('.editBtn').addEventListener('click', () => {
            editingId = warehouse.id;
            warehouseForm.name.value = warehouse.name;
            warehouseForm.address.value = warehouse.address;
            warehouseForm.phone.value = warehouse.phone;
            new bootstrap.Modal(document.getElementById('warehouseModal')).show();
        });

        // --- Delete ---
        tr.querySelector('.deleteBtn').addEventListener('click', () => {
            if (confirm('Are you sure you want to delete this warehouse?')) {
                fetch('warehouses.php', {
                    method: 'DELETE',
                    body: new URLSearchParams({ id: warehouse.id })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            tr.remove();
                            updateWarehouseNumbers();
                        } else {
                            alert('Delete failed');
                        }
                    });
            }
        });
    }

    // ✅ Helper function to renumber rows
    function updateWarehouseNumbers() {
        document.querySelectorAll('#warehousesTbody tr').forEach((row, index) => {
            row.querySelector('.row-index').textContent = index + 1;
        });
    }

    // ✅ Function to reload table data
    function reloadWarehouses() {
        tbody.innerHTML = ''; // clear existing rows
        fetch('warehouses.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) data.data.forEach(w => addWarehouseRow(w));
            });
    }

    // ✅ Initial load
    reloadWarehouses();

    // ✅ Save (Add/Edit)
    saveBtn.addEventListener('click', () => {

        const phone = warehouseForm.phone.value.trim();

        // ✅ Validate phone number if provided (allow empty or exactly 11 digits)
        if (phone !== '' && !/^[0-9]{11}$/.test(phone)) {
            alert('Please enter a valid 11-digit Pakistani phone number (e.g. 03001234567) or leave it blank.');
            return; // stop execution
        }

        // ✅ Built-in HTML validation (name, address required, etc.)
        if (!warehouseForm.checkValidity()) {
            warehouseForm.reportValidity();
            return;
        }

        // ✅ Proceed only if valid
        const formData = new FormData(warehouseForm);
        if (editingId) formData.append('id', editingId);

        fetch('warehouses.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // 🔁 Reload full table to stay synced
                    reloadWarehouses();

                    // ✅ Close modal + reset form
                    const modal = bootstrap.Modal.getInstance(document.getElementById('warehouseModal'));
                    if (modal) modal.hide();
                    warehouseForm.reset();
                    editingId = null;
                } else {
                    alert(data.message || 'Error saving warehouse');
                }
            });
    });


    // ✅ DataTable init
    setTimeout(() => {
        $('#warehouseTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                { extend: 'csvHtml5', title: 'Warehouse_List' },
                { extend: 'excelHtml5', title: 'Warehouse_List' },
                { extend: 'pdfHtml5', title: 'Warehouse_List' },
                { extend: 'print', title: 'Warehouse List' }
            ]
        });
    }, 800);
});
