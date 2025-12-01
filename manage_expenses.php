<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main class="content">
  <div class="card p-3">
    <h2>Expense Records</h2>
    <div class="mb-3 d-flex gap-2">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#expenseModal">+ Add Expense</button>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#salaryModal">+ Add Salary Expense</button>
    </div>

    <!-- Expense Table -->
    <table id="expenseTable" class="table text-nowrap table-hover">
      <thead>
        <tr>
          <th>#</th>
          <th>Type</th>
          <th>Date</th>
          <th>Month</th>
          <th>Employee</th>
          <th>Details</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody id="expenseTbody"></tbody>
      <tfoot>
        <tr>
          <th colspan="6" class="text-end">Total Amount:</th>
          <th id="totalExpenseAmount">0.00</th>
        </tr>
      </tfoot>
    </table>

    <!-- Monthly Summary -->
    <h5 class="mt-4">Monthly Expense Summary</h5>
    <table id="summaryTable" class="table text-nowrap table-hover">
      <thead>
        <tr>
          <th>Month</th>
          <th>General</th>
          <th>Salary</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody id="summaryTbody"></tbody>
      <tfoot>
        <tr>
          <th class="text-end">Overall Total:</th>
          <th id="totalGeneral">0.00</th>
          <th id="totalSalary">0.00</th>
          <th id="totalOverall">0.00</th>
        </tr>
      </tfoot>
    </table>
  </div>
</main>

<!-- General Expense Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content bg-light text-dark">
      <form id="expenseForm">
        <div class="modal-header">
          <h5>Add General Expense</h5>
        </div>
        <div class="modal-body">
          <input type="hidden" name="type" value="general">
          <label>Date</label><input type="date" name="date" class="form-control" required>
          <label>Details</label><textarea name="details" class="form-control" required></textarea>
          <label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" required>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Salary Expense Modal -->
<div class="modal fade" id="salaryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content bg-light text-dark">
      <form id="salaryForm">
        <div class="modal-header">
          <h5>Add Salary Expense</h5>
        </div>
        <div class="modal-body">
          <input type="hidden" name="type" value="salary">
          <label>Month</label><input type="text" name="month" class="form-control" placeholder="e.g. October 2025" required>
          <label>Date</label><input type="date" name="date" class="form-control" required>
          <label>Employee</label><select name="employee_id" id="employeeSelect" class="form-control" required></select>
          <label>Details</label><textarea name="details" class="form-control" required></textarea>
          <label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" required>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="./assets/js/expenses.js"></script>