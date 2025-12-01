// employee.js
document.addEventListener('DOMContentLoaded', function () {
    const saveBtn = document.getElementById('saveEmployeeBtn');
    const employeeForm = document.getElementById('employeeForm');
    const employeeModal = document.getElementById('employeeModal');

    let editingId = null;

    // -------------------------
    // Reusable function: Refresh table
    // -------------------------
    function refreshEmployeeTable(employees) {
        const tbody = document.getElementById('employeesTbody');

        // Destroy existing DataTable if any
        if ($.fn.DataTable.isDataTable('#employeesTable')) {
            $('#employeesTable').DataTable().destroy();
        }

        // Clear tbody
        tbody.innerHTML = '';

        // Add rows dynamically
        let counter = 1;
        employees.forEach(emp => {
            const tr = document.createElement('tr');
            tr.dataset.id = emp.id;
            tr.innerHTML = `
                <td>${counter++}</td>
                <td class="name">${emp.name}</td>
                <td class="email">${emp.email || ''}</td>
                <td class="phone">${emp.phone || ''}</td>
                <td class="address">${emp.address || ''}</td>
                <td class="designation">${emp.designation || ''}</td>
                <td class="joining_date">${emp.joining_date || ''}</td>
                <td>
                    <button class="btn btn-sm btn-warning editBtn">Edit</button>
                    <button class="btn btn-sm btn-danger deleteBtn">Delete</button>
                </td>
            `;
            tbody.appendChild(tr);

            // Edit
            tr.querySelector('.editBtn').addEventListener('click', () => {
                editingId = emp.id;
                employeeForm.name.value = emp.name;
                employeeForm.email.value = emp.email;
                employeeForm.phone.value = emp.phone;
                employeeForm.address.value = emp.address;
                employeeForm.designation.value = emp.designation;
                employeeForm.joining_date.value = emp.joining_date;

                const modal = new bootstrap.Modal(employeeModal);
                modal.show();
            });

            // Delete
            tr.querySelector('.deleteBtn').addEventListener('click', () => {
                if (confirm('Delete this employee?')) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', emp.id);

                    fetch('employee.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                // Reload table
                                fetchEmployees();
                            } else {
                                alert(data.message || 'Failed to delete employee');
                            }
                        });
                }
            });
        });

        // Reinitialize DataTable
        $('#employeesTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: 'Bfrtip',
            buttons: [
                { extend: 'excelHtml5', title: 'Employees_List' },
                { extend: 'csvHtml5', title: 'Employees_List' },
                { extend: 'pdfHtml5', title: 'Employees_List' },
                { extend: 'print', title: 'Employees List' }
            ]
        });
    }

    // -------------------------
    // Fetch employees from server
    // -------------------------
    function fetchEmployees() {
        const formData = new FormData();
        formData.append('action', 'fetch');

        fetch('employee.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    refreshEmployeeTable(data.data);
                }
            });
    }

    // Initial load
    fetchEmployees();

    // -------------------------
    // Save (Add / Edit)
    // -------------------------
    saveBtn.addEventListener('click', function () {
        const formData = new FormData(employeeForm);

        if (editingId) {
            formData.append('action', 'update');
            formData.append('id', editingId);
        } else {
            formData.append('action', 'add');
        }

        fetch('employee.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Reload table
                    fetchEmployees();

                    const modal = bootstrap.Modal.getInstance(employeeModal);
                    if (modal) modal.hide();

                    employeeForm.reset();
                    editingId = null;
                } else {
                    alert(data.message || 'Error saving employee');
                }
            });
    });
});
