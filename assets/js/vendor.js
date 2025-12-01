document.addEventListener('DOMContentLoaded', function () {

    const saveBtn = document.getElementById('saveVendorBtn');
    const vendorForm = document.getElementById('vendorForm');
    const vendorsTbody = document.getElementById('vendorsTbody');

    let editingId = null;

    function addVendorRow(vendor) {
        const tr = document.createElement('tr');
        tr.dataset.id = vendor.id;
        tr.innerHTML = `
        <td class="row-index"></td>
        <td class="name">${vendor.name}</td>
        <td class="phone">${vendor.phone}</td>
        <td class="address">${vendor.address}</td>
        <td class="image">${vendor.image_url ? `<img src="${vendor.image_url}" width="50">` : ''}</td>
        <td>
            <button class="btn btn-sm btn-warning editBtn">Edit</button>
            <button class="btn btn-sm btn-danger deleteBtn">Delete</button>
        </td>
    `;
        vendorsTbody.appendChild(tr);

        // --- Recalculate numbering each time ---
        updateRowNumbers();

        // Edit
        tr.querySelector('.editBtn').addEventListener('click', function () {
            editingId = vendor.id;
            vendorForm.name.value = vendor.name;
            vendorForm.phone.value = vendor.phone;
            vendorForm.address.value = vendor.address;
            const vendorModal = new bootstrap.Modal(document.getElementById('vendorModal'));
            vendorModal.show();
        });

        // Delete
        tr.querySelector('.deleteBtn').addEventListener('click', function () {
            if (confirm('Are you sure you want to delete this vendor?')) {
                fetch('vendors.php', {
                    method: 'DELETE',
                    body: new URLSearchParams({ id: vendor.id })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            tr.remove();
                            updateRowNumbers(); // recalc after delete
                        } else {
                            alert('Failed to delete vendor');
                        }
                    });
            }
        });
    }

    // ✅ Helper function to recalculate numbering
    function updateRowNumbers() {
        document.querySelectorAll('#vendorsTbody tr').forEach((row, index) => {
            row.querySelector('.row-index').textContent = index + 1;
        });
    }

    // ✅ Function to reload table completely
    function reloadVendors() {
        fetch('vendors.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    vendorsTbody.innerHTML = ''; // clear old data
                    data.data.forEach(vendor => addVendorRow(vendor));
                }
            });
    }

    // Load existing vendors
    reloadVendors();

    // Save (Add / Edit)
    saveBtn.addEventListener('click', function () {

        const phone = vendorForm.phone.value.trim();
        if (!/^[0-9]{11}$/.test(phone)) {
            alert('Please enter a valid 11-digit Pakistani phone number (e.g. 03001234567)');
            return; // stop execution
        }

        if (!vendorForm.checkValidity()) {
            vendorForm.reportValidity();
            return;
        }

        const formData = new FormData(vendorForm);
        if (editingId) formData.append('id', editingId);

        fetch('vendors.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const vendorModal = bootstrap.Modal.getInstance(document.getElementById('vendorModal'));
                    if (vendorModal) vendorModal.hide();

                    vendorForm.reset();
                    editingId = null;

                    reloadVendors();
                } else {
                    alert(data.message || 'Error saving vendor');
                }
            });
    });

    setTimeout(() => {
        $('#vendorsTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                { extend: 'csvHtml5', title: 'Vendors_List' },
                { extend: 'excelHtml5', title: 'Vendors_List' },
                { extend: 'pdfHtml5', title: 'Vendors_List' },
                { extend: 'print', title: 'Vendors List' }
            ]
        });
    }, 800);

});
