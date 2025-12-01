<?php
// manage_employee.php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/topbar.php';
?>

<main class="content">
    <div id="view">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 m-0">Employees</h2>
                <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employeeModal">Add Employee</a>
            </div>
            <div class="table-responsive">
                <table id="employeesTable" class="table text-nowrap table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Designation</th>
                            <th>Joining Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="employeesTbody"></tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="employeeModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-light text-dark">
                    <div class="modal-header">
                        <h5 class="modal-title">Employee</h5>
                        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="employeeForm" class="vstack gap-2">
                            <input type="hidden" name="id" id="employee_id">

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Name</label>
                                    <input name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input name="phone" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Designation</label>
                                    <input name="designation" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address</label>
                                    <input name="address" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Joining Date</label>
                                    <input type="date" name="joining_date" class="form-control">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button id="saveEmployeeBtn" type="button" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </div>
</main>

<script src="./assets/js/employee.js"></script>
<?php
include 'includes/footer.php';
?>