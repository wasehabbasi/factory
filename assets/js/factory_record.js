document.addEventListener("DOMContentLoaded", () => {
    refreshAllTables();
    loadFactories();
    loadProductLot();
    loadVendors();
    loadWarehouses();
    loadWarehouseInventory();
    loadWarehouseSummary();
    setDesignNumber();
    formatWarehouseSummaryRow();

    const receive_factory = document.getElementById("receiveFactorySelect");
    const receive_vendor = document.getElementById("receiveVendorSelect");
    const receive_lot = document.getElementById("lot_number");

    function checkAndSendQty() {
        if (receive_factory.value && receive_vendor.value && receive_lot.value) {
            setSendQty(); // ✅ All fields filled, call function
        }
    }

    // ✅ Add listeners AFTER data loaded
    receive_factory.addEventListener("change", checkAndSendQty);
    receive_vendor.addEventListener("change", checkAndSendQty);
    receive_lot.addEventListener("change", checkAndSendQty);

    // --- Send Form Submit ---
    document.getElementById("sendForm").addEventListener("submit", function (e) {
        e.preventDefault();
        const form = this;

        fetch("factory_record.php?action=save_send", { method: "POST", body: new FormData(form) })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    refreshAllTables();
                    bootstrap.Modal.getInstance(document.getElementById("sendModal"))?.hide();
                    location.reload();
                    form.reset();
                } else {
                    alert(d.message || "Error saving data");
                }
            });
    });

    // --- Receive Form Submit ---
    document.getElementById("receiveForm").addEventListener("submit", function (e) {
        e.preventDefault();

        fetch("factory_record.php?action=save_receive", { method: "POST", body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    refreshAllTables();
                    bootstrap.Modal.getInstance(document.getElementById("receiveModal"))?.hide();
                    location.reload();
                    this.reset();
                } else {
                    alert(d.message || "Error saving data");
                }
            });
    });

    // --- Save Design Number ---
    document.addEventListener("click", async (e) => {
        if (e.target.classList.contains("save-design-btn")) {
            const btn = e.target;
            const productId = btn.getAttribute("data-product-id");
            const input = btn.closest("tr").querySelector(".design-input");
            const designNumber = input?.value?.trim();

            if (!designNumber) return alert("Please enter a design number.");

            try {
                const res = await fetch("save_design_number.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ product_id: productId, design_number: designNumber })
                });
                const data = await res.json();
                if (data.success) {
                    alert("Design number saved successfully!");
                    refreshAllTables();
                } else {
                    alert("Error: " + data.message);
                }
            } catch (err) {
                alert("Request failed. Try again.");
                console.error(err);
            }
        }
    });


});

// ---------- Refresh All Tables Function ----------
function refreshAllTables() {
    setTimeout(() => {
        loadTableData("factory_record.php?action=list_send", "#sendTbody", "#sendTable", "Send_List", formatSendRow);
        loadTableData("factory_record.php?action=list_receive", "#receiveTbody", "#receiveTable", "Receive_List", formatReceiveRow);
        // loadTableData("factory_record.php?action=list_unique_warehouse_design", "#warehouseStockTbody", "#warehouseTable", "Warehouse_List", formatWarehouseRow);
    }, 500);
}

// ---------- UNIVERSAL TABLE LOADER ----------
function loadTableData(apiUrl, tbodySelector, tableSelector, title, rowFormatter) {
    fetch(apiUrl)
        .then(r => r.json())
        .then(d => {
            let totals = {
                send: 0,
                receiveSend: 0,
                receiveReceive: 0,
                receiveShortage: 0,
                receiveRejection: 0,
                receiveLKMI: 0,
                warehouseQty: 0
            };

            let rows = "";
            const countedLots = new Set();

            if (d.success && d.data.length > 0) {
                d.data.forEach((item, i) => {
                    rows += rowFormatter(item, i);

                    // console.log("Item", item.total_qty);

                    // --- Totals calculation ---
                    if (tableSelector === "#sendTable") totals.send += parseFloat(item.quantity) || 0;
                    if (tableSelector === "#receiveTable") {
                        if (!countedLots.has(item.lot_number)) {
                            totals.receiveSend += parseFloat(item.send_quantity) || 0;
                            countedLots.add(item.lot_number);
                        }
                        // totals.receiveSend += parseFloat(item.send_quantity) || 0;
                        totals.receiveReceive += parseFloat(item.receive_quantity) || 0;
                        totals.receiveShortage += parseFloat(item.shortage) || 0;
                        totals.receiveRejection += parseFloat(item.rejection) || 0;
                        totals.receiveLKMI += parseFloat(item.l_kmi) || 0;
                    }
                    if (tableSelector === "#warehouseTable" && item.total_qty) totals.warehouseQty += parseFloat(item.total_qty);
                    if (tableSelector === "#warehouseSummaryTable" && item.total_qty) totals.warehouseQty += parseFloat(item.total_qty);
                });
            } else {
                // --- No data found: dynamic colspan ---
                const table = document.querySelector(tableSelector);
                const columnCount = table ? table.querySelectorAll("thead th").length : 1;

                // --- Create dummy row with 0 in each column ---
                let dummyCells = "";
                for (let i = 0; i < columnCount; i++) {
                    dummyCells += `<td class="text-center text-muted">0</td>`;
                }
                rows = `<tr class="dummy-row">${dummyCells}</tr>`;

            }

            // --- Update tbody ---
            const tbody = document.querySelector(tbodySelector);
            if (tbody) tbody.innerHTML = rows;
            else console.warn(`Table body not found: ${tbodySelector}`);

            // --- Update totals ---
            if (tableSelector === "#sendTable") document.getElementById("sendTotalQty").textContent = totals.send.toLocaleString();
            if (tableSelector === "#receiveTable") {
                document.getElementById("totalSendQty").textContent = totals.receiveSend.toLocaleString();
                document.getElementById("totalReceiveQty").textContent = totals.receiveReceive.toLocaleString();
                document.getElementById("totalShortage").textContent = totals.receiveShortage.toLocaleString();
                document.getElementById("totalRejection").textContent = totals.receiveRejection.toLocaleString();
                document.getElementById("totalLKMI").textContent = totals.receiveLKMI.toLocaleString();
            }
            if (tableSelector === "#warehouseTable") {
                const el = document.getElementById("warehouseTotal");
                if (el) el.textContent = totals.warehouseQty.toFixed(2);
            }
            if (tableSelector === "#warehouseSummaryTable") {
                const el = document.getElementById("warehouseSummaryTotal");
                if (el) el.textContent = totals.warehouseQty.toFixed(2);
            }

            // --- Reinitialize DataTable safely ---
            if ($.fn.DataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().clear().destroy();
            }
            $(tableSelector).DataTable(getDataTableConfig(title));
        })
        .catch(err => {
            console.error(`Error loading table data from ${apiUrl}:`, err);
        });
}


function formatSendRow(s, i) {
    return `<tr>             
    <td>${i + 1}</td>             
    <td>${s.date}</td>             
    <td>${s.factory_name}</td>             
    <td>${s.vendor_name}</td>             
    <td>${s.lot_number}</td>             
    <td>${s.quantity}</td>         
    </tr>`;
}

function formatReceiveRow(s, i) {
    return `<tr>
    <td>${i + 1}</td>
    <td>${s.date}</td>
    <td>${s.factory_name}</td>
    <td>${s.vendor_name}</td>
    <td>${s.warehouse_name || "Unknown Warehouse"}</td>
    <td>${s.lot_number}</td>
    <td>${s.send_quantity}</td>
    <td>${s.receive_quantity}</td>
    <td>${s.design_number ?? '-'}</td>
    <td>${s.nag ?? '-'}</td>
    <td>${s.shortage ?? '-'}</td>
    <td>${s.rejection ?? '-'}</td>
    <td>${s.l_kmi ?? '-'}</td>
    </tr>`;
}

$('#receiveVendorSelect').on('change', function () {
    loadLotNumbers();
});

function loadLotNumbers() {
    const selectedVendorId = $('#receiveVendorSelect').val();
    if (!selectedVendorId) {
        $('#lot_number').html('<option value="">Select Lot</option>');
        return;
    }

    fetch("factory_record.php?action=get_lot_numbers&vendor_id=" + selectedVendorId)
        .then(res => res.json())
        .then(response => {
            const lotSelect = $('#lot_number');
            lotSelect.html('<option value="">Select Lot</option>'); // reset

            if (!response.success) return;

            const lots = response.data ?? [];
            lots.forEach(lot => {
                lotSelect.append(`<option value="${lot}">${lot}</option>`);
            });
        })
        .catch(err => console.error("Error loading lot numbers:", err));
}



document.addEventListener("DOMContentLoaded", loadLotNumbers);

function formatWarehouseRow(w, i) {
    const warehouseName = w.warehouse_name || "Unknown Warehouse";
    const designNum = w.design_number || "-";
    const nagNum = w.nag ?? "-";
    const qty = parseFloat(w.total_qty || 0).toFixed(3);

    return `
        <tr>
            <td>${i + 1}</td>
            <td>${warehouseName}</td>
            <td>${designNum}</td>
            <td>${nagNum}</td>
            <td>${qty}</td>
        </tr>
    `;
}

function formatWarehouseSummaryRow(w, i) {
    if (!w) {
        console.warn("Skipped row: undefined warehouse summary object at index", i);
        return "";
    }

    const factoryName = w.factory_name || "-";
    const vendorName = w.vendor_name || "-";
    const productName = w.product_name || "-";
    const measurement = w.product_measurement || "-";
    const designNum = w.design_number || "-";
    const nag = w.nag ?? "-";
    const qty = parseFloat(w.total_qty || 0).toFixed(2);
    const warehouseName = w.warehouse_name || "Unknown Warehouse";

    return `
    <tr>
      <td>${1}</td>
      <td>${warehouseName}</td>
      <td>${factoryName}</td>
      <td>${vendorName}</td>
      <td>${productName}</td>
      <td>${measurement}</td>
      <td>${designNum}</td>
      <td>${nag}</td>
      <td>${qty}</td>
    </tr>
  `;
}

// ---------- DATATABLE CONFIG ----------
function getDataTableConfig(title) {
    return {
        paging: true,
        searching: true,
        ordering: true,
        responsive: true,
        pageLength: 5,
        lengthMenu: [[5, 10, 50, 100], [5, 10, 50, 100]],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', text: 'Excel', title, className: 'btn btn-sm btn-primary' },
            { extend: 'csvHtml5', text: 'CSV', title, className: 'btn btn-sm btn-info' },
            { extend: 'pdfHtml5', text: 'PDF', title, className: 'btn btn-sm btn-danger' },
            { extend: 'print', text: 'Print', title, className: 'btn btn-sm btn-success' }
        ]
    };
}

// ---------- DROPDOWN LOADERS ----------
function loadFactories() {
    fetch("factories.php")
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                let opts = '<option value="">Select Factory</option>';
                d.data.forEach(f => opts += `<option value="${f.id}">${f.name}</option>`);
                document.getElementById("sendFactorySelect").innerHTML = opts;
                document.getElementById("receiveFactorySelect").innerHTML = opts;
            }
        });
}

// Vendor select hone par lot numbers load karo
$('#sendVendorSelect').on('change', function () {
    loadProductLot();
});


function loadProductLot() {
    const selectedVendorId = $('#sendVendorSelect').val();
    fetch("factory_record.php?action=get_lot_number&vendor_id=" + selectedVendorId)
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                let opts = '<option value="">Select Lot Number</option>';
                d.data.forEach(f => {
                    opts += `<option value="${f.lot_number}">${f.lot_number}</option>`;
                });
                document.getElementById("sendLotNumber").innerHTML = opts;

                // $('#sendLotNumber').select2({
                //     placeholder: "Search or select Lot Number",
                //     allowClear: true,
                //     width: '100%',
                //     dropdownParent: $('#sendModal')
                // });
            }
        })
        .catch(err => console.error("Error loading lot numbers:", err));
}

function loadVendors() {
    fetch("vendors.php")
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                let opts = '<option value="">Select Vendor</option>';
                d.data.forEach(v => opts += `<option value="${v.id}">${v.name}</option>`);
                document.getElementById("sendVendorSelect").innerHTML = opts;
                document.getElementById("receiveVendorSelect").innerHTML = opts;
            }
        });
}

function loadWarehouses() {
    fetch("warehouses.php")
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                let opts = '<option value="">Select Warehouse</option>';
                d.data.forEach(w => opts += `<option value="${w.id}">${w.name}</option>`);
                document.getElementById("sendWarehouseSelect").innerHTML = opts;
                document.getElementById("receiveWarehouseSelect").innerHTML = opts;
            }
        });
}

async function loadWarehouseInventory() {
    try {
        const res = await fetch("factory_record.php?action=list_unique_warehouse_design");
        const data = await res.json();

        const tbody = document.getElementById("warehouseTbody");
        let rows = "";
        let totalQty = 0;
        let totalNag = 0;

        if (data.success && data.data.length > 0) {
            data.data.forEach((item, i) => {
                const qty = parseFloat(item.total_quantity) || 0;
                const nag = parseFloat(item.nag) || 0;

                totalQty += qty;
                totalNag += nag;

                rows += `
                    <tr>
                        <td>${i + 1}</td>
                        <td>${item.warehouse_name || "-"}</td>
                        <td>${item.factory_name || "-"}</td>
                        <td>${item.vendor_name || "-"}</td>
                        <td>${item.product_name || "-"}</td>
                        <td>${item.product_measurement || "-"}</td>
                        <td>${item.design_number || "-"}</td>
                        <td>${nag}</td>
                        <td>${qty.toFixed(2)}</td>
                    </tr>
                `;
            });
        } else {
            const columnCount = tbody ? tbody.closest("table").querySelectorAll("thead th").length : 9;
            let dummyCells = "";
            for (let i = 0; i < columnCount; i++) dummyCells += `<td class="text-center text-muted">0</td>`;
            rows = `<tr class="dummy-row">${dummyCells}</tr>`;
        }

        // --- Update tbody BEFORE DataTable init ---
        if ($.fn.DataTable.isDataTable("#warehouseTable")) {
            $("#warehouseTable").DataTable().destroy();
        }
        tbody.innerHTML = rows;

        // --- Update total ---
        document.getElementById("warehouseTotal").textContent = totalQty.toFixed(2);

        // --- Reinitialize DataTable ---
        initDataTable("#warehouseTable");

    } catch (err) {
        console.error("Error loading warehouse inventory:", err);
    }
}



function initDataTable(selector) {
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().destroy();
    }
    $(selector).DataTable({
        pageLength: 5,
        ordering: false,
        responsive: true,
    });
}

async function loadWarehouseSummary() {
    try {
        const res = await fetch("factory_record.php?action=list_unique_warehouse_summary");
        const data = await res.json();

        const tbody = document.getElementById("warehouseSummaryTbody");
        let rows = "";
        let totalQty = 0;

        if (data.success && data.data.length > 0) {
            data.data.forEach((item, i) => {
                const qty = parseFloat(item.total_quantity) || 0;
                totalQty += qty;

                rows += `
                    <tr>
                        <td>${i + 1}</td>
                        <td>${item.warehouse_name || "-"}</td>
                        <td>${qty.toFixed(2)}</td>
                    </tr>
                `;
            });
        } else {
            // --- Dummy row with 0 in all columns ---
            const columnCount = tbody ? tbody.closest("table").querySelectorAll("thead th").length : 3;
            let dummyCells = "";
            for (let i = 0; i < columnCount; i++) {
                dummyCells += `<td class="text-center text-muted">0</td>`;
            }
            rows = `<tr class="dummy-row">${dummyCells}</tr>`;
        }

        tbody.innerHTML = rows;
        document.getElementById("warehouseSummaryTotal").textContent = totalQty.toFixed(2);

        initDataTable("#warehouseSummaryTable");
    } catch (err) {
        console.error("Error loading warehouse summary:", err);
    }
}

function initDataTable(selector) {
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().destroy();
    }
    $(selector).DataTable({
        pageLength: 5,
        ordering: false,
        responsive: true,
    });
}

function setDesignNumber() {
    const lotSelect = document.getElementById("lot_number");
    const designInput = document.getElementById("design_number");

    if (!lotSelect) {
        console.warn("lot_number select not found on page.");
        return;
    }

    lotSelect.addEventListener("change", function () {
        const selectedLot = this.value;
        if (!selectedLot) return; // nothing selected

        console.log("Selected lot_number:", selectedLot);

        fetch("factory_record.php?action=get_design_number&lot_number=" + selectedLot)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    console.log("Existing Design Number:", data.design_number);
                    if (designInput) {
                        designInput.value = data.design_number;
                        designInput.readOnly = true; // make readonly
                        // designInput.classList.add("bg-light"); // optional styling
                    }
                } else {
                    if (designInput) {
                        designInput.value = "";
                        designInput.readOnly = false; // make editable again
                        designInput.classList.remove("bg-light");
                    }
                }
            })
            .catch(err => {
                console.error("Error fetching design number:", err);
                if (designInput) {
                    designInput.readOnly = false;
                    // designInput.classList.remove("bg-light");
                }
            });
    });
}


function setSendQty() {
    const factory_id = $('#receiveFactorySelect').val();
    const vendor_id = $('#receiveVendorSelect').val();
    const lot_number = $('#lot_number').val();

    if (!factory_id || !vendor_id || !lot_number) return;

    fetch(`factory_record.php?action=get_send_qty&factory_id=${factory_id}&vendor_id=${vendor_id}&lot_number=${lot_number}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.log("Send Qty Data:", data);
                $('#send_quantity').val(data.quantity);
            } else {
                console.warn("No data found:", data);
            }
        })
        .catch(err => {
            console.error("Error fetching send qty:", err);
        });
}


$(document).ready(function () {
    $('#receive_quantity').on('input', function () {
        const sendQuantity = parseFloat($('#send_quantity').val()) || 0;
        const receiveQuantity = parseFloat($(this).val()) || 0;

        // Prevent infinite alerts — check only when user exceeds limit
        if (receiveQuantity > sendQuantity) {
            alert("Received Quantity cannot be greater than Send Quantity!");
            $(this).val(''); // Clear invalid input
        }
    });
});


function loadDesignNumbers() {
    fetch("factory_record.php?action=get_design_numbers") // API se existing design numbers fetch karenge
        .then(res => res.json())
        .then(data => {
            const select = $('#design_number');

            // Destroy old select2 if exists
            if ($.fn.select2.isPrototypeOf(select[0])) {
                select.select2('destroy');
            }

            // Clear previous options
            select.html('<option value=""></option>');

            // Populate existing design numbers
            if (data.success && Array.isArray(data.data)) {
                data.data.forEach(d => {
                    select.append(`<option value="${d.design_number}">${d.design_number}</option>`);
                });
            }

            // Initialize select2 with tags (new entry allowed)
            select.select2({
                placeholder: "Select or enter Design Number",
                allowClear: true,
                width: '100%',
                tags: true, // ✅ allow new entries
                dropdownParent: $('#receiveModal')
            });
        })
        .catch(err => console.error("Error loading design numbers:", err));
}

// Call this when modal opens
$('#receiveModal').on('shown.bs.modal', function () {
    loadDesignNumbers();
    loadLotNumbers();
});
