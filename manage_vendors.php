<?php
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
                <h2 class="h5 m-0">Vendors</h2>
                <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#vendorModal">Add Vendor</a>
            </div>
            <div class="table-responsive">
                <table id="vendorsTable" class="table text-nowrap table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="vendorsTbody"></tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="vendorModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-light text-dark">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Vendor</h5>
                        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <form id="vendorForm" class="vstack gap-2" enctype="multipart/form-data">
                            <div class="row g-2">

                                <!-- Vendor Name -->
                                <div class="col-md-6">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control"
                                        minlength="3" maxlength="30" required placeholder="Enter Vendor Name">


                                        <!-- name="name"
                                        type="text"
                                        class="form-control"
                                        minlength="3"
                                        maxlength="50"
                                        required
                                        placeholder="Enter factory name" -->

                                </div>

                                <!-- Vendor Phone -->
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" id="phone" name="phone" class="form-control"
                                        minlength="11" maxlength="11" required
                                        pattern="[0-9]{11}"
                                        placeholder="03XXXXXXXXX">
                                    <div class="form-text text-light small">
                                        Enter 11-digit Pakistani number (e.g. 03001234567)
                                    </div>
                                </div>

                                <!-- Address -->
                                <div class="col-12">
                                    <label class="form-label">Address</label>
                                    <input type="text" name="address" class="form-control"
                                        minlength="7" maxlength="50" required placeholder="Enter Vendor Address"
                                        placeholder="Vendor Address">
                                </div>

                                <!-- Image (optional) -->
                                <div class="col-12">
                                    <label class="form-label">Image (optional)</label>
                                    <input type="file" name="image_file" class="form-control" accept="image/*">
                                </div>

                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button id="saveVendorBtn" type="button" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ Validation Script -->
        <!-- <script>
            document.getElementById('saveVendorBtn').addEventListener('click', function() {
                const form = document.getElementById('vendorForm');
                const phone = document.getElementById('phone').value.trim();

                // Phone Validation (exactly 11 digits)
                if (!/^[0-9]{11}$/.test(phone)) {
                    alert('Please enter a valid 11-digit Pakistani phone number (e.g. 03001234567)');
                    return; // stop submit
                }

                // Built-in form validation check
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                // ✅ If all good, then submit via AJAX or whatever you want
                alert('Vendor details are valid! Proceeding to save...');
            });
        </script> -->


</main>

<script src="./assets/js/vendor.js"></script>
<?php
include 'includes/footer.php';
