document.addEventListener('DOMContentLoaded', function () {
    const saveBtn = document.getElementById('saveBuyerBtn');
    const buyerForm = document.getElementById('buyerForm');
    const buyersTbody = document.getElementById('buyersTbody');
    let editingId = null;

    let buyerCounter = 1;

    function addBuyerRow(buyer) {
        const tr = document.createElement('tr');
        tr.dataset.id = buyer.id;
        tr.innerHTML = `
        <td>${buyerCounter++}</td>
        <td>${buyer.name}</td>
        <td>${buyer.address}</td>
        <td>${buyer.phone}</td>
        <td>${buyer.image_url ? `<img src="${buyer.image_url}" width="50">` : ''}</td>
        <td>
            <button class="btn btn-sm btn-warning editBtn">Edit</button>
            <button class="btn btn-sm btn-danger deleteBtn">Delete</button>
        </td>
    `;
        buyersTbody.appendChild(tr);

        // ✏️ Edit button
        tr.querySelector('.editBtn').addEventListener('click', () => {
            editingId = buyer.id;
            buyerForm.id.value = buyer.id;
            buyerForm.name.value = buyer.name;
            buyerForm.address.value = buyer.address;
            buyerForm.phone.value = buyer.phone;

            const modal = new bootstrap.Modal(document.getElementById('buyerModal'));
            modal.show();
        });

        // 🗑️ Delete button
        tr.querySelector('.deleteBtn').addEventListener('click', () => {
            if (confirm('Delete this buyer?')) {
                fetch('buyers.php', {
                    method: 'DELETE',
                    body: new URLSearchParams({ id: buyer.id })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            tr.remove();
                            resetBuyerNumbering(); // Reset numbering after delete
                        } else {
                            alert('Failed to delete');
                        }
                    });
            }
        });
    }

    // 🔁 Function to reset numbering after delete
    function resetBuyerNumbering() {
        buyerCounter = 1;
        document.querySelectorAll('#buyersTbody tr').forEach(tr => {
            tr.querySelector('td:first-child').textContent = buyerCounter++;
        });
    }


    function loadBuyers() {
        fetch('buyers.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    buyersTbody.innerHTML = '';
                    data.data.forEach(buyer => addBuyerRow(buyer));
                }
            });
    }

    saveBtn.addEventListener('click', async function () {
        const phone = buyerForm.phone.value.trim();

        // ✅ Step 1: Validate phone (11 digits, Pakistani format)
        if (!/^[0-9]{11}$/.test(phone)) {
            alert('Please enter a valid 11-digit Pakistani phone number (e.g. 03001234567)');
            return; // stop here if invalid
        }

        // ✅ Step 2: Check other form fields
        if (!buyerForm.checkValidity()) {
            buyerForm.reportValidity();
            return;
        }

        // ✅ Step 3: Prepare form data
        const formData = new FormData(buyerForm);
        if (editingId) formData.append('id', editingId);

        try {
            const res = await fetch('buyers.php', { method: 'POST', body: formData });
            const data = await res.json();

            console.log('✅ Response:', data);

            if (data.success) {
                await loadBuyers();

                // ✅ Step 4: Close modal safely
                const modalEl = document.getElementById('buyerModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInstance.hide();

                // ✅ Step 5: Reset form
                buyerForm.reset();
                editingId = null;
            } else {
                alert(data.message || 'Error saving buyer');
            }
        } catch (err) {
            console.error('❌ Error:', err);
            alert('Error saving buyer!');
        }
    });


    document.querySelector('[data-bs-target="#buyerModal"]').addEventListener('click', () => {
        editingId = null;
        buyerForm.reset();
    });

    loadBuyers();

    setTimeout(() => {
        $('#buyersTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            responsive: true,
            pageLength: 10, // 🔹 default 10 records per page
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]], // 🔹 dropdown options
            dom: 'Bfrtip', // buttons + filter + pagination
            buttons: [
                { extend: 'csvHtml5', title: 'Buyers_List' },
                {
                    extend: 'excelHtml5',
                    title: 'Buyers_List'
                },
                {
                    extend: 'pdfHtml5',
                    title: 'Buyers_List'
                },
                {
                    extend: 'print',
                    title: 'Buyers_List'
                }
            ]
        });
    }, 800);


});
