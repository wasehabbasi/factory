document.addEventListener("DOMContentLoaded", () => {
  loadBalanceSheet();
  document.getElementById("downloadCsv").addEventListener("click", downloadCSV);
});

function loadBalanceSheet() {
  fetch("balance_sheet.php?action=list")
    .then(r => r.json())
    .then(d => {
      let rows = "";

      d.data.forEach(row => {
        rows += `
    <tr>
      <td>${formatDate(row.purchase_date) ?? '-'}</td>
      <td>${row.vendor_name ?? '-'}</td>
      <td>${row.lot_number ?? '-'}</td>
      <td>${row.measurement ?? '-'}</td>
      <td>${row.product_name ?? '-'}</td>
      <td>${parseFloat(row.width || 0).toFixed(2)}</td>
      <td>${parseFloat(row.thaan || 0).toFixed(2)}</td>
      <td>${parseFloat(row.issue_meter || 0).toFixed(2)}</td>
      <td>${parseFloat(row.net_gazana || 0).toFixed(2)}</td>
      <td>${parseFloat(row.fresh_gazana || 0).toFixed(2)}</td>
      <td>${parseFloat(row.rate || 0).toFixed(2)}</td>

      <!-- ✅ Editable fields -->
      <td>
        <input type="number" step="0.01" value="${parseFloat(row.l_kmi || 0).toFixed(2)}"
          data-lot="${row.lot_number}" data-field="l_kmi"
          class="form-control form-control-sm text-center" style="width: 100px;">
      </td>
      <td>${parseFloat(row.rejection || 0).toFixed(2)}</td>
      <td>${parseFloat(row.shortage || 0).toFixed(2)}</td>
      <td>
        <input type="number" step="0.01" value="${parseFloat(row.remaining_meter || 0).toFixed(2)}"
          data-lot="${row.lot_number}" data-field="remaining_meter"
          class="form-control form-control-sm text-center" style="width: 100px;">
      </td>
      <td>
        <input type="text" value="${row.final_remarks ?? ''}"
          data-lot="${row.lot_number}" data-field="final_remarks"
          class="form-control form-control-sm" placeholder="Enter remarks" style="min-width: 150px;">
      </td>

      <td><button class="btn btn-sm btn-primary" onclick="saveRow(${row.lot_number})">💾</button></td>
    </tr>
  `;
      });


      document.getElementById("balanceTbody").innerHTML = rows;

      // Destroy previous DataTable
      if ($.fn.DataTable.isDataTable("#balanceTable")) {
        $("#balanceTable").DataTable().destroy();
      }

      // ✅ Initialize DataTable
      $("#balanceTable").DataTable({
        dom: "Bfrtip",
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        buttons: [
          { extend: "csvHtml5", footer: true, className: "btn btn-sm btn-primary" },
          { extend: "excelHtml5", footer: true, className: "btn btn-sm btn-success" },
          {
            extend: "pdfHtml5",
            footer: true,
            className: "btn btn-sm btn-danger",
            title: "Balance_Sheet_" + new Date().toISOString().split("T")[0],
            customize: function (doc) {
              // add totals in pdf
              const footerCells = $("#balanceTable tfoot th");
              const totalsRow = [
                { text: "TOTAL", bold: true, alignment: "right", colSpan: 5 },
                "", "", "", "",
                footerCells.eq(5).text(),
                footerCells.eq(6).text(),
                footerCells.eq(7).text(),
                footerCells.eq(8).text(),
                footerCells.eq(9).text(),
                footerCells.eq(10).text(),
                footerCells.eq(11).text(),
                footerCells.eq(12).text(),
                footerCells.eq(13).text(),
                footerCells.eq(14).text(),
                "", ""
              ];
              doc.content[1].table.body.push(totalsRow);
            }
          },
          { extend: "print", footer: true, className: "btn btn-sm btn-warning" }
        ],
        footerCallback: function (row, data, start, end, display) {
          const api = this.api();

          const intVal = (i) =>
            typeof i === "string"
              ? parseFloat(i.replace(/[\$,]/g, "")) || 0
              : typeof i === "number"
                ? i
                : 0;

          // Columns to total
          const cols = [5, 6, 7, 8, 9, 10, 11, 12, 13, 14];

          cols.forEach((colIndex) => {
            const total = api
              .column(colIndex, { search: "applied" })
              .data()
              .reduce((a, b) => intVal(a) + intVal(b), 0);
            $(api.column(colIndex).footer()).html(total.toFixed(2));
          });
        },
        language: {
          lengthMenu: "Show _MENU_ entries per page",
          paginate: { previous: "← Prev", next: "Next →" },
          search: "Search:"
        }
      });
    })
    .catch((err) => console.error("Error loading balance sheet:", err));
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  if (isNaN(date)) return dateStr; // if invalid, return as is
  return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

function saveRow(lot) {
  const l_kmi = document.querySelector(`input[data-lot="${lot}"][data-field="l_kmi"]`).value;
  const remaining = document.querySelector(`input[data-lot="${lot}"][data-field="remaining_meter"]`).value;
  const remarks = document.querySelector(`input[data-lot="${lot}"][data-field="final_remarks"]`).value;

  const form = new FormData();
  form.append("lot_number", lot);
  form.append("l_kmi", l_kmi);
  form.append("remaining_meter", remaining);
  form.append("final_remarks", remarks);

  fetch("balance_sheet.php?action=update", { method: "POST", body: form })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        alert("✅ Saved successfully");
        loadBalanceSheet();
      }
    });
}

function downloadCSV() {
  const rows = document.querySelectorAll("#balanceTable tr");
  const csv = [...rows].map(r => {
    const cols = r.querySelectorAll("td, th");
    return [...cols].map(c => `"${c.innerText.replace(/,/g, '')}"`).join(",");
  }).join("\n");

  const blob = new Blob([csv], { type: "text/csv" });
  const link = document.createElement("a");
  link.href = URL.createObjectURL(blob);
  link.download = "balance_sheet.csv";
  link.click();
}