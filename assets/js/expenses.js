document.addEventListener("DOMContentLoaded", () => {
  loadExpenses();
  loadEmployees();
  loadSummary();

  // --- General Expense Form Submit ---
  document.getElementById("expenseForm").addEventListener("submit", function (e) {
    e.preventDefault();
    saveExpense(this);
  });

  // --- Salary Expense Form Submit ---
  document.getElementById("salaryForm").addEventListener("submit", function (e) {
    e.preventDefault();
    saveExpense(this);
  });
});

// ---- Save Expense (General or Salary) ----
function saveExpense(form) {
  fetch("expenses.php?action=save", { method: "POST", body: new FormData(form) })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        loadExpenses();  // Refresh expense table
        loadSummary();   // Refresh summary table
        bootstrap.Modal.getInstance(form.closest(".modal")).hide();
        form.reset();
      } else {
        alert("Failed to save expense!");
      }
    })
    .catch(err => console.error(err));
}

// ---- Load Expenses Table ----
function loadExpenses() {
  fetch("expenses.php?action=list")
    .then(r => r.json())
    .then(d => {
      if (!d.success) return;

      const table = "#expenseTable";

      // Destroy existing DataTable
      if ($.fn.DataTable.isDataTable(table)) $(table).DataTable().clear().destroy();

      // Clear tbody
      let tbody = document.getElementById("expenseTbody");
      tbody.innerHTML = "";

      let rows = "";
      let totalAmount = 0;

      d.data.forEach((ex, i) => {
        rows += `
          <tr>
            <td>${i + 1}</td>
            <td>${ex.type}</td>
            <td>${ex.date}</td>
            <td>${ex.month ?? "-"}</td>
            <td>${ex.employee_name ?? "-"}</td>
            <td>${ex.details}</td>
            <td>${parseFloat(ex.amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
          </tr>`;
        totalAmount += parseFloat(ex.amount) || 0;
      });

      tbody.innerHTML = rows;
      document.getElementById("totalExpenseAmount").textContent =
        totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2 });

      // Initialize DataTable
      initExpenseTable();
    })
    .catch(err => console.error("Error loading expenses:", err));
}


// ---- Load Employees for Salary Modal ----
function loadEmployees() {
  fetch("employee.php?action=fetch")
    .then(r => r.json())
    .then(d => {
      let opts = '<option value="">Select Employee</option>';
      if (d.data && d.data.length) {
        d.data.forEach(e => {
          opts += `<option value="${e.id}">${e.name}</option>`;
        });
      }
      document.getElementById("employeeSelect").innerHTML = opts;
    })
    .catch(err => console.error("Error loading employees:", err));
}

// ---- Load Monthly Summary ----
function loadSummary() {
  fetch("expenses.php?action=monthly_summary")
    .then(r => r.json())
    .then(d => {
      if (!d.success) return;

      const table = "#summaryTable";

      if ($.fn.DataTable.isDataTable(table)) $(table).DataTable().clear().destroy();

      let tbody = document.getElementById("summaryTbody");
      tbody.innerHTML = "";

      let rows = "";
      let totalGeneral = 0, totalSalary = 0, totalOverall = 0;

      d.data.forEach(s => {
        rows += `
          <tr>
            <td>${s.month}</td>
            <td>${parseFloat(s.total_general).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
            <td>${parseFloat(s.total_salary).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
            <td>${parseFloat(s.total).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
          </tr>`;

        totalGeneral += parseFloat(s.total_general) || 0;
        totalSalary += parseFloat(s.total_salary) || 0;
        totalOverall += parseFloat(s.total) || 0;
      });

      tbody.innerHTML = rows;
      document.getElementById("totalGeneral").textContent =
        totalGeneral.toLocaleString(undefined, { minimumFractionDigits: 2 });
      document.getElementById("totalSalary").textContent =
        totalSalary.toLocaleString(undefined, { minimumFractionDigits: 2 });
      document.getElementById("totalOverall").textContent =
        totalOverall.toLocaleString(undefined, { minimumFractionDigits: 2 });

      initSummaryTable();
    })
    .catch(err => console.error("Error loading summary:", err));
}

// ---- Initialize Expense Table ----
function initExpenseTable() {
  const table = "#expenseTable";
  if ($.fn.DataTable.isDataTable(table)) $(table).DataTable().destroy();

  $(table).DataTable({
    dom: "Bfrtip",
    pageLength: 5,
    responsive: true,
    lengthMenu: [[5, 25, 50, 100], [5, 25, 50, 100]],
    buttons: [
      { extend: "excelHtml5", text: "Excel", className: "btn btn-sm btn-primary" },
      { extend: "csvHtml5", text: "CSV", className: "btn btn-sm btn-info" },
      { extend: "pdfHtml5", text: "PDF", className: "btn btn-sm btn-danger" },
      { extend: "print", text: "Print", className: "btn btn-sm btn-success" }
    ]
  });
}

// ---- Initialize Summary Table ----
function initSummaryTable() {
  const table = "#summaryTable";
  if ($.fn.DataTable.isDataTable(table)) $(table).DataTable().destroy();

  $(table).DataTable({
    dom: "Bfrtip",
    pageLength: 5,
    responsive: true,
    lengthMenu: [[5, 25, 50, 100], [5, 25, 50, 100]],
    buttons: [
      { extend: "excelHtml5", text: "Excel", className: "btn btn-sm btn-primary" },
      { extend: "csvHtml5", text: "CSV", className: "btn btn-sm btn-info" },
      { extend: "pdfHtml5", text: "PDF", className: "btn btn-sm btn-danger" },
      { extend: "print", text: "Print", className: "btn btn-sm btn-success" }
    ]
  });
}
