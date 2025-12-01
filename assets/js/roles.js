document.addEventListener('DOMContentLoaded', () => {
    const saveBtn = document.getElementById('saveRoleBtn');
    const roleForm = document.getElementById('roleForm');
    const rolesTbody = document.getElementById('rolesTbody');
    const modalEl = document.getElementById('roleModal');
    let editingId = null;
    let dataTable = null;

    // ✅ Initialize or reinitialize DataTable
    function initDataTable() {
        if ($.fn.DataTable.isDataTable('#rolesTable')) {
            $('#rolesTable').DataTable().clear().destroy();
        }

        dataTable = $('#rolesTable').DataTable({
            dom: 'Bfrtip',
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            buttons: [
                { extend: 'excelHtml5', text: 'Excel', className: 'btn btn-sm btn-success' },
                { extend: 'csvHtml5', text: 'CSV', className: 'btn btn-sm btn-primary' },
                { extend: 'pdfHtml5', text: 'PDF', className: 'btn btn-sm btn-danger' },
                { extend: 'print', text: 'Print', className: 'btn btn-sm btn-secondary' }
            ],
            language: {
                lengthMenu: "Show _MENU_ entries per page",
                paginate: {
                    previous: "← Prev",
                    next: "Next →"
                },
                search: "Search:"
            }
        });
    }

    // ✅ Add a single role row
    function addRoleRow(role, index) {
        const tr = document.createElement('tr');
        tr.dataset.id = role.id;

        tr.innerHTML = `
            <td>${index}</td>
            <td class="name">${role.name}</td>
            <td class="description">${role.description || ''}</td>
            <td class="modules">${Array.isArray(role.modules) ? role.modules.join(', ') : ''}</td>
            <td>
                <button class="btn btn-sm btn-warning editBtn">Edit</button>
                <button class="btn btn-sm btn-danger deleteBtn">Delete</button>
            </td>
        `;

        rolesTbody.appendChild(tr);

        // ✏️ Edit Role
        tr.querySelector('.editBtn').addEventListener('click', () => {
            editingId = role.id;
            roleForm.name.value = role.name;
            roleForm.description.value = role.description || '';

            // Reset checkboxes
            roleForm.querySelectorAll('input[name="modules[]"]').forEach(cb => {
                cb.checked = false;
            });

            // Recheck assigned modules
            if (Array.isArray(role.modules)) {
                roleForm.querySelectorAll('input[name="modules[]"]').forEach(cb => {
                    if (role.modules.includes(cb.nextElementSibling.textContent)) {
                        cb.checked = true;
                    }
                });
            }

            // Show modal
            new bootstrap.Modal(modalEl).show();
        });

        // 🗑️ Delete Role
        tr.querySelector('.deleteBtn').addEventListener('click', async () => {
            if (confirm('Delete this role?')) {
                try {
                    const res = await fetch('roles.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action: 'delete', id: role.id })
                    });
                    const data = await res.json();
                    if (data.success) {
                        loadRoles();
                    } else {
                        alert('Failed to delete role.');
                    }
                } catch (err) {
                    console.error('Delete Error:', err);
                    alert('Error deleting role!');
                }
            }
        });
    }

    // ✅ Load all roles
    async function loadRoles() {
        try {
            const res = await fetch('roles.php');
            const data = await res.json();

            // destroy existing DataTable if exists
            if ($.fn.DataTable.isDataTable('#rolesTable')) {
                $('#rolesTable').DataTable().clear().destroy();
            }

            rolesTbody.innerHTML = ''; // clear old data

            if (data.success && Array.isArray(data.data)) {
                let rowNumber = 1;
                data.data.forEach(role => {
                    addRoleRow(role, rowNumber++);
                });
            }

            initDataTable(); // initialize DataTable after rows added
        } catch (err) {
            console.error(err);
            alert('Error loading roles!');
        }
    }


    // ✅ Save or update role
    saveBtn.addEventListener('click', async () => {
        saveBtn.blur();
        const formData = new FormData(roleForm);
        if (editingId) formData.set('id', editingId);

        try {
            const res = await fetch('roles.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                bootstrap.Modal.getInstance(modalEl).hide();
                roleForm.reset();
                editingId = null;
                loadRoles();
            } else {
                alert(data.message || 'Error saving role!');
            }
        } catch (err) {
            console.error('Save Error:', err);
            alert('Error saving role!');
        }
    });

    // ✅ Initial load
    loadRoles();
});
