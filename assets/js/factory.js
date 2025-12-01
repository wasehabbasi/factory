document.addEventListener('DOMContentLoaded', function () {
    const saveBtn = document.getElementById('saveFactoryBtn');
    const factoryForm = document.getElementById('factoryForm');
    const tbody = document.getElementById('factoriesTbody');
    let editingId = null;

    function addFactoryRow(factory) {
        const tr = document.createElement('tr');
        tr.dataset.id = factory.id;
        tr.innerHTML = `
        <td class="row-index"></td>
        <td class="name">${factory.name}</td>
        <td class="address">${factory.address}</td>
        <td class="phone">${factory.phone}</td>
        <td class="image">${factory.image_url ? `<img src="${factory.image_url}" width="50">` : ''}</td>
        <td>
            <button class="btn btn-sm btn-warning editBtn">Edit</button>
            <button class="btn btn-sm btn-danger deleteBtn">Delete</button>
            <button class="btn btn-sm btn-info GenerateInvoiceBtn">Generate Invoice</button>

        </td>
        `;
        tbody.appendChild(tr);
        updateFactoryNumbers();

        tr.querySelector('.editBtn').addEventListener('click', function () {
            editingId = factory.id;
            factoryForm.name.value = factory.name;
            factoryForm.address.value = factory.address;
            factoryForm.phone.value = factory.phone;
            factoryForm.querySelector('input[name="id"]').value = factory.id;
            factoryForm.image_file.value = '';
            new bootstrap.Modal(document.getElementById('factoryModal')).show();
        });

        tr.querySelector('.deleteBtn').addEventListener('click', function () {
            if (confirm('Are you sure you want to delete this factory?')) {
                fetch('factories.php', {
                    method: 'DELETE',
                    body: new URLSearchParams({ id: factory.id })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            tr.remove();
                            updateFactoryNumbers();
                        } else {
                            alert('Failed to delete factory');
                        }
                    });
            }
        });
    }

    function updateFactoryNumbers() {
        document.querySelectorAll('#factoriesTbody tr').forEach((row, index) => {
            row.querySelector('.row-index').textContent = index + 1;
        });
    }

    function reloadFactories() {
        tbody.innerHTML = '';
        fetch('factories.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) data.data.forEach(f => addFactoryRow(f));
            });
    }

    reloadFactories();

    saveBtn.addEventListener('click', function () {

        if (!factoryForm.checkValidity()) {
            factoryForm.classList.add('was-validated');
            return;
        }

        const formData = new FormData(factoryForm);
        console.log(formData);
        if (editingId) formData.append('id', editingId);

        fetch('factories.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    reloadFactories();
                    const modal = bootstrap.Modal.getInstance(document.getElementById('factoryModal'));
                    if (modal) modal.hide();
                    factoryForm.reset();
                    factoryForm.classList.remove('was-validated');
                    editingId = null;
                } else {
                    alert(data.message || 'Error saving factory');
                }
            });
    });

    document.getElementById('addFactoryBtn').addEventListener('click', () => {
        editingId = null;
        factoryForm.reset();
    });

    document.addEventListener('click', async function (e) {
        if (e.target.classList.contains('GenerateInvoiceBtn')) {
            const factoryId = e.target.closest('tr').dataset.id;
            document.getElementById('factory_id').value = factoryId;

            const res = await fetch('factories.php?action=get_products');
            const data = await res.json();
            const productSelect = document.getElementById('product_id');
            productSelect.innerHTML = '<option value="">Select Product</option>';
            data.data.forEach(p => {
                productSelect.innerHTML += `<option value="${p.id}" data-lot="${p.lot_number}">${p.name} (Lot: ${p.lot_number})</option>`;
            });

            $('#product_id').select2({
                dropdownParent: $('#factoryInvoiceModal'),
                placeholder: "Search or select a product",
                width: '100%'
            });

            new bootstrap.Modal(document.getElementById('factoryInvoiceModal')).show();
        }
    });

    $('#product_id').on('change', function () {
        const selectedLot = $(this).find('option:selected').data('lot') || '';
        $('#lot_number').val(selectedLot);
    });


    document.getElementById('saveFactoryInvoiceBtn').addEventListener('click', function () {
        const form = document.getElementById('factoryInvoiceForm');
        const formData = new FormData(form);
        formData.append('action', 'save_invoice');

        // ✅ Check if it's edit mode
        const isEdit = document.getElementById('edit').value === "true";

        // Agar edit mode hai to invoice ID bhej do
        if (isEdit) {
            const invoiceId = document.getElementById('invoice_id')?.value;
            formData.append('id', invoiceId);
        }

        fetch('factories.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('factoryInvoiceModal')).hide();

                    loadFactoryInvoices();

                    if (isEdit) {
                        alert("Invoice updated successfully!");
                        location.reload();
                    } else {
                        alert("Invoice created successfully!");
                        location.reload();
                        if (data.invoice_id) {
                            window.open(`factory_invoice_pdf.php?id=${data.invoice_id}`, '_blank');
                        }
                    }

                    // ✅ Reset form + edit flag
                    form.reset();
                    document.getElementById('edit').value = "";
                    document.getElementById('saveFactoryInvoiceBtn').textContent = "Save Invoice";
                    document.querySelector('.modal-title').textContent = "Generate Factory Invoice";
                } else {
                    alert(data.message || 'Error saving invoice');
                }
            })
            .catch(err => console.error('Fetch Error:', err));
    });

    setTimeout(() => {
        $('#factoriesTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                { extend: 'csvHtml5', title: 'Factories_List' },
                { extend: 'excelHtml5', title: 'Factories_List' },
                { extend: 'pdfHtml5', title: 'Factories_List' },
                { extend: 'print', title: 'Factories_List' }
            ]
        });
    }, 800);

    loadFactoryInvoices();

    function loadFactoryInvoices() {
        fetch("factory_invoices.php?action=read")
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById("factoryInvoicesTbody");
                tbody.innerHTML = "";

                data.forEach((inv, index) => {
                    tbody.innerHTML += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${inv.factory_name}</td>
                        <td>${inv.product_name}</td>
                        <td>${inv.lot_number}</td>
                        <td>${inv.total_meter}</td>
                        <td>${inv.per_meter_rate}</td>
                        <td>${parseFloat(inv.total_amount).toFixed(2)}</td>
                        <td>${inv.rejection}</td>
                        <td>${parseFloat(inv.net_amount).toFixed(2)}</td>
                        <td>${inv.advance_adjusted}</td>
                        <td>${formatDate(inv.created_at)}</td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editFactoryInvoice(${inv.id})">Edit</button>
                            <button class="btn btn-sm btn-info" onclick="window.open('factory_invoice_pdf.php?id=${inv.id}', '_blank')">Print</button>
                        </td>
                    </tr>`;
                });

                if ($.fn.DataTable.isDataTable('#factoryInvoicesTable')) {
                    $('#factoryInvoicesTable').DataTable().destroy();
                }

                const table = $('#factoryInvoicesTable').DataTable({
                    pageLength: 5,
                    lengthChange: true,
                    ordering: true,
                    searching: true,
                    responsive: true,
                    dom: 'Bfrtip',
                    buttons: [
                        {
                            extend: 'csvHtml5',
                            footer: true,
                            title: 'Factory_Invoices_' + new Date().toISOString().split('T')[0]
                        },
                        {
                            extend: 'excelHtml5',
                            footer: true,
                            title: 'Factory_Invoices_' + new Date().toISOString().split('T')[0]
                        },
                        {
                            extend: 'pdfHtml5',
                            footer: true,
                            title: 'Factory_Invoices_' + new Date().toISOString().split('T')[0],
                            customize: function (doc) {
                                doc.content[1].table.body.push([
                                    { text: 'Total:', bold: true },
                                    '', '', '',
                                    $('#factoryInvoicesTable tfoot th').eq(4).text(),
                                    '',
                                    $('#factoryInvoicesTable tfoot th').eq(6).text(),
                                    $('#factoryInvoicesTable tfoot th').eq(7).text(),
                                    $('#factoryInvoicesTable tfoot th').eq(8).text(),
                                    '', '', ''
                                ]);
                            }
                        },
                        {
                            extend: 'print',
                            footer: true,
                            title: 'Factory Invoices'
                        }
                    ],
                    footerCallback: function (row, data, start, end, display) {
                        const api = this.api();

                        const intVal = i => typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : (typeof i === 'number' ? i : 0);

                        const totalMeter = api.column(4, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                        const totalRate = api.column(5, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                        const totalAmount = api.column(6, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                        const totalRejection = api.column(7, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                        // const totalNet = api.column(8, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                        const totalAdvance = api.column(9, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);

                        const totalNet = totalAmount - totalAdvance;

                        $(api.column(4).footer()).html(totalMeter.toFixed(2));
                        $(api.column(5).footer()).html(totalRate.toFixed(2));
                        $(api.column(6).footer()).html(totalAmount.toFixed(2));
                        $(api.column(7).footer()).html(totalRejection.toFixed(2));
                        $(api.column(8).footer()).html(totalNet.toFixed(2));
                        $(api.column(9).footer()).html(totalAdvance.toFixed(2));
                    },

                    language: {
                        search: "Search:",
                        paginate: { previous: "Prev", next: "Next" }
                    }
                });

                table.buttons().container().appendTo('#invoiceButtons');
            });
    }
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    if (isNaN(date)) return dateStr;
    return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

async function editFactoryInvoice(id) {
    const res = await fetch('factories.php?action=get_products');
    const productsData = await res.json();
    const productSelect = document.getElementById('product_id');
    productSelect.innerHTML = '<option value="">Select Product</option>';
    productsData.data.forEach(p => {
        productSelect.innerHTML += `<option value="${p.id}" data-lot="${p.lot_number}">${p.name} (Lot: ${p.lot_number})</option>`;
    });

    $.ajax({
        url: "../../get_factory_invoice.php",
        type: "POST",
        data: { id: id },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                const data = response.data;
                $("#invoice_id").val(data.id);
                $("#factory_id").val(data.factory_id);
                $("#total_meter").val(data.total_meter);
                $("#per_meter_rate").val(data.per_meter_rate);
                $("#rejection").val(data.rejection);
                $("#advance_adjusted").val(data.advance_adjusted);

                productSelect.value = data.product_id;

                productSelect.dispatchEvent(new Event('change'));

                $("#edit").val("true");
                $(".modal-title").text("Edit Factory Invoice");
                $("#saveFactoryInvoiceBtn").text("Update Invoice");
                $("#factoryInvoiceModal").modal("show");
            } else {
                alert("Failed to load invoice data");
            }
        }
    });
}
