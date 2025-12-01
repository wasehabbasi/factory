document.addEventListener("DOMContentLoaded", () => {
    loadBuyers();
    loadBuyerInvoices();
    loadDesignNumbers();
    getProductByDesignNumber();

    const form = document.getElementById("buyerInvoiceForm");

    // --- Auto calculate total_amount when qty or rate changes ---
    const qtyInput = document.getElementById("buyer_qty");
    const rateInput = document.getElementById("buyer_rate");
    const totalInput = document.getElementById("buyer_total_amount");
    const amountPaidInput = document.getElementById("buyer_amount_paid"); // ❌ '#' hatao yahan se

    function updateTotal() {
        const qty = parseFloat(qtyInput.value) || 0;
        const rate = parseFloat(rateInput.value) || 0;
        const total = qty * rate;
        totalInput.value = total.toFixed(2);

        // ✅ Check if amount paid is greater than total
        const paid = parseFloat(amountPaidInput.value) || 0;
        if (paid > total) {
            alert("Paid amount cannot be greater than total amount!");
            amountPaidInput.value = total.toFixed(2); // auto-fix
        }
    }

    // 🔹 Recalculate total when qty or rate changes
    qtyInput.addEventListener("input", updateTotal);
    rateInput.addEventListener("input", updateTotal);

    // 🔹 Check paid amount in real-time too
    amountPaidInput.addEventListener("input", () => {
        const total = parseFloat(totalInput.value) || 0;
        const paid = parseFloat(amountPaidInput.value) || 0;
        if (paid > total) {
            alert("Paid amount cannot be greater than total amount!");
            amountPaidInput.value = total.toFixed(2);
        }
    });

    $("#buyer_total_amount").prop("readonly", true);


    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const productInput = document.getElementById("product_id");
        const productId = productInput.getAttribute("data-product-id");
        if (!productId) {
            alert("Please select a valid Design Number to fetch Product.");
            return;
        }

        const formData = new FormData(form);
        formData.set("product_id", productId);

        try {
            const res = await fetch("buyers_invoices.php?action=save", {
                method: "POST",
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                alert(data.message || "Invoice saved successfully!");
                form.reset();

                if ($.fn.DataTable.isDataTable("#buyerInvoicesTable")) {
                    $("#buyerInvoicesTable").DataTable().destroy();
                }

                await loadBuyerInvoices();
                bootstrap.Modal.getInstance(document.getElementById("buyerInvoiceModal")).hide();
            } else {
                alert(data.message || data.error || "Failed to save invoice!");
            }
        } catch (err) {
            console.error("Error saving invoice:", err);
            alert("An error occurred while saving invoice.");
        }
    });

});

// ------------------ Load Buyers ------------------
async function loadBuyers() {
    try {
        const res = await fetch("buyers.php");
        const d = await res.json();
        if (!d.success) throw new Error("Failed to load buyers");

        let opts = '<option value="">Select Buyer</option>';
        d.data.forEach(b => (opts += `<option value="${b.id}">${b.name}</option>`));
        document.getElementById("buyerSelect").innerHTML = opts;
    } catch (err) {
        console.error(err);
        alert(err.message);
    }
}

async function loadWarehouses() {
    try {
        const res = await fetch("buyers_invoices.php?action=get_warehouses");
        const data = await res.json();
        if (!data.success) throw new Error("Failed to load warehouses");

        let opts = '<option value="">Select Warehouse</option>';
        data.data.forEach(w => {
            opts += `<option value="${w.id}">${w.name}</option>`;
        });
        document.getElementById("warehouseSelect").innerHTML = opts;
    } catch (err) {
        console.error(err);
    }
}

document.getElementById("buyerInvoiceModal").addEventListener("show.bs.modal", () => {
    loadWarehouses(); // existing
    loadDesignNumbers(); // refresh design numbers
});

async function loadBuyerInvoices() {
    try {
        const res = await fetch("buyers_invoices.php?action=list");
        const d = await res.json();
        if (!d.success) throw new Error("Failed to load invoices");

        let rows = "";
        d.data.forEach((inv, i) => {
            const qty = parseFloat(inv.qty || 0);
            const rate = parseFloat(inv.rate || 0);
            const total = parseFloat(inv.total_amount || 0);
            const nag = parseFloat(inv.nag || 0);
            const paid = parseFloat(inv.amount_paid || 0);
            const balance = parseFloat(total - paid);

            rows += `
        <tr>
          <td>${i + 1}</td>
          <td>${inv.invoice_no || '-'}</td>
          <td>${inv.buyer_name || '-'}</td>
          <td>${inv.product_name || '-'}</td>
          <td>${inv.design_number || '-'}</td>
          <td>${qty.toFixed(2)}</td>
          <td>${rate.toFixed(2)}</td>
          <td>${total.toFixed(2)}</td>
          <td>${nag.toFixed(2)}</td>
          <td>${paid.toFixed(2)}</td>
          <td>${balance.toFixed(2)}</td>
          <td>${formatDate(inv.payment_date) || '-'}</td>
          <td>${inv.remarks || '-'}</td>
          <td>
            <button class="btn btn-sm btn-info" onclick="window.open('buyer_invoice_pdf.php?id=${inv.id}', '_blank')">PDF</button>
            <button class="btn btn-sm btn-secondary" onclick="editBuyerInvoice(${inv.id})">Edit</button>
          </td>
        </tr>`;
        });

        document.getElementById("buyerInvoiceTbody").innerHTML = rows;

        // Destroy previous DataTable
        if ($.fn.DataTable.isDataTable("#buyerInvoicesTable")) {
            $("#buyerInvoicesTable").DataTable().clear().destroy();
        }

        // ✅ Initialize DataTable with dynamic footer total update
        const table = $("#buyerInvoicesTable").DataTable({
            dom: 'Bfrtip',
            responsive: true,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            buttons: [
                {
                    extend: 'csvHtml5',
                    text: 'CSV',
                    className: 'btn btn-sm btn-primary',
                    footer: true // ✅ include footer totals in CSV
                },
                {
                    extend: 'excelHtml5',
                    text: 'Excel',
                    className: 'btn btn-sm btn-success',
                    footer: true // ✅ include footer totals in Excel
                },
                {
                    extend: 'pdfHtml5',
                    text: 'PDF',
                    className: 'btn btn-sm btn-danger',
                    footer: true, // ✅ include footer in PDF
                    orientation: 'landscape',
                    pageSize: 'A4'
                },
                {
                    extend: 'print',
                    text: 'Print',
                    className: 'btn btn-sm btn-warning',
                    footer: true, // ✅ include footer in print view
                    customize: function (win) {
                        // ✅ Style the print totals properly
                        $(win.document.body)
                            .find('tfoot th')
                            .css({
                                'font-weight': 'bold',
                                'background-color': '#f8f9fa',
                                'color': '#000'
                            });
                    }
                }
            ],

            language: {
                lengthMenu: "Show _MENU_ entries per page",
                zeroRecords: "No records found",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "No entries available",
                search: "Search:",
            },
            footerCallback: function (row, data, start, end, display) {
                const api = this.api();

                // Helper to sum visible column values
                const sum = (idx) =>
                    api
                        .column(idx, { page: 'current', search: 'applied' })
                        .data()
                        .reduce((a, b) => a + parseFloat(b || 0), 0);

                // Update footer with visible totals
                $("#buyerTotalQty").text(sum(5).toFixed(2));
                $("#buyerTotalRate").text(sum(6).toFixed(2));
                $("#buyerTotalAmount").text(sum(7).toFixed(2));
                $("#buyerTotalNag").text(sum(8).toFixed(2));
                $("#buyerTotalPaid").text(sum(9).toFixed(2));
                $("#buyerTotalBalance").text(sum(10).toFixed(2));
            }
        });

    } catch (err) {
        console.error(err);
        alert(err.message);
    }
}

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    if (isNaN(date)) return dateStr; // if invalid, return as is
    return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};


async function editBuyerInvoice(id) {
    try {
        const res = await fetch(`buyers_invoices.php?action=get&id=${id}`);
        const d = await res.json();

        console.log(d);
        if (!d.success) throw new Error("Invoice not found!");

        const inv = d.data;

        document.getElementById("buyer_invoice_id").value = inv.id;
        document.getElementById("buyerSelect").value = inv.buyer_id;

        // Load warehouses first
        await loadWarehouses();
        document.getElementById("warehouseSelect").value = inv.warehouse_id;

        // Product
        document.getElementById("product_id").value = inv.product_name;
        document.getElementById("product_id").setAttribute("data-product-id", inv.product_id);

        document.getElementById("buyer_lot_number").value = inv.lot_number;
        document.getElementById("buyer_design_number").value = inv.design_number;
        document.getElementById("buyer_qty").value = inv.qty;
        document.getElementById("buyer_rate").value = inv.rate;
        document.getElementById("buyer_total_amount").value = inv.total_amount;
        $("#buyer_total_amount").prop("readonly", true);
        document.getElementById("nag").value = inv.nag;
        document.getElementById("buyer_amount_paid").value = inv.amount_paid;
        document.getElementById("buyer_payment_date").value = inv.payment_date;
        document.getElementById("buyer_remarks").value = inv.remarks;

        new bootstrap.Modal(document.getElementById("buyerInvoiceModal")).show();
    } catch (err) {
        console.error(err);
        alert(err.message);
    }
}

async function loadDesignNumbers() {
    try {
        const res = await fetch("buyers_invoices.php?action=get_design_numbers");
        const d = await res.json();
        if (!d.success) throw new Error("Failed to load design numbers");

        let opts = '<option value="">Select Design Number</option>';
        d.data.forEach(num => {
            opts += `<option value="${num}">${num}</option>`;
        });
        const el = document.getElementById("buyer_design_number");
        if (el) el.innerHTML = opts;
    } catch (err) {
        console.error("Error loading design numbers:", err);
    }
}

function getProductByDesignNumber() {
    $("#buyer_design_number").on("change", function () {
        const selectedDesign = $(this).val();
        if (!selectedDesign) return;

        console.log("Selected Design Number:", selectedDesign);

        $.ajax({
            url: "buyers_invoices.php",
            method: "GET",
            data: {
                action: "get_product_by_design",
                design_number: selectedDesign
            },
            dataType: "json",
            success: function (res) {
                if (res.success) {
                    console.log("Product Response:", res);

                    $("#product_id").val(res.product_name || "");
                    $("#buyer_lot_number").val(res.lot_number || "");

                    $("#product_id").attr("data-product-id", res.product_id || "");
                    $("#product_id").attr("data-lot-number", res.lot_number || "");
                } else {
                    alert(res.message || "No product found for this design number!");
                    $("#product_id").val("").removeAttr("data-product-id").removeAttr("data-lot-number");
                }
            },
            error: function (xhr, status, err) {
                console.error("Error fetching product:", err);
            }
        });
    });
}