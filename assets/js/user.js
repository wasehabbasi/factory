document.addEventListener('DOMContentLoaded', function () {
    const saveBtn = document.getElementById('saveUserBtn');
    const userForm = document.getElementById('userForm');
    const usersTbody = document.getElementById('usersTbody');

    let editingId = null;

    let userCounter = 1;

    function addUserRow(user) {
        const tr = document.createElement('tr');
        tr.dataset.id = user.id;
        tr.innerHTML = `
        <td>${userCounter++}</td>
        <td class="name">${escapeHtml(user.name)}</td>
        <td class="email">${escapeHtml(user.email)}</td>
        <td class="role">${escapeHtml(user.role_name || '')}</td>
        <td class="status">${escapeHtml(user.status)}</td>
        <td class="last_login">${user.last_login || '-'}</td>
        <td>
            <button class="btn btn-sm btn-warning editBtn">Edit</button>
            <button class="btn btn-sm btn-danger deleteBtn">Delete</button>
        </td>
    `;
        usersTbody.appendChild(tr);

        // ✏️ Edit button
        tr.querySelector('.editBtn').addEventListener('click', function () {
            editingId = user.id;
            userForm.id.value = user.id;
            userForm.name.value = user.name;
            userForm.email.value = user.email;

            // set role select
            const roleSelect = userForm.querySelector('select[name="role_id"]');
            if (roleSelect) {
                roleSelect.value = user.role_id || '';
            }

            userForm.status.value = user.status || 'active';
            const userModal = new bootstrap.Modal(document.getElementById('userModal'));
            userModal.show();
        });

        // 🗑️ Delete button
        tr.querySelector('.deleteBtn').addEventListener('click', function () {
            if (confirm('Are you sure you want to delete this user?')) {
                fetch('users.php', {
                    method: 'DELETE',
                    body: new URLSearchParams({ id: user.id })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            tr.remove();
                            resetUserNumbering(); // 🔁 Reset numbering after delete
                        } else {
                            alert(data.message || 'Failed to delete user');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Server error');
                    });
            }
        });
    }

    // 🔢 Reset numbering after delete
    function resetUserNumbering() {
        userCounter = 1;
        document.querySelectorAll('#usersTbody tr').forEach(tr => {
            tr.querySelector('td:first-child').textContent = userCounter++;
        });
    }


    // Load existing users
    function loadUsers() {
        usersTbody.innerHTML = '';
        fetch('users.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    data.data.forEach(user => addUserRow(user));
                } else {
                    alert('Failed to load users');
                }
            }).catch(err => {
                console.error(err);
                alert('Server error while loading users');
            });
    }

    loadUsers();

    // Save (Add / Edit)
    saveBtn.addEventListener('click', function () {
        const formData = new FormData(userForm);
        // If editingId set, ensure id present
        if (editingId) formData.append('id', editingId);

        fetch('users.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // reload list
                    loadUsers();
                    const userModal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
                    if (userModal) userModal.hide();
                    userForm.reset();
                    editingId = null;
                } else {
                    alert(data.message || 'Error saving user');
                }
            }).catch(err => {
                console.error(err);
                alert('Server error');
            });
    });

    // helper: escape HTML to avoid injection in table
    function escapeHtml(text) {
        if (!text && text !== 0) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    setTimeout(() => {
        $('#usersTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            responsive: true,
            pageLength: 10, // 🔹 default max 10 records per page
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]], // 🔹 dropdown options
            dom: 'Bfrtip', // buttons + filter + pagination
            buttons: [
                {
                    extend: 'excelHtml5',
                    title: 'Users_List'
                },
                {
                    extend: 'csvHtml5',
                    title: 'Users_List'
                },
                {
                    extend: 'pdfHtml5',
                    title: 'Users_List'
                },
                {
                    extend: 'print',
                    title: 'Users List'
                }
            ]
        });
    }, 800);


});
