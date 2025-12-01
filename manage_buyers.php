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
                <h2 class="h5 m-0">Buyers</h2>
                <div>
                    <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#buyerModal">Add Buyer</a>
                </div>
            </div>
            <div class="table-responsive">
                <table id="buyersTable" class="table text-nowrap table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="buyersTbody"></tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="buyerModal" tabindex="-1" aria-labelledby="buyerModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-light text-dark">
                    <div class="modal-header">
                        <h5 class="modal-title" id="buyerModalLabel">Add Buyer</h5>
                        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <form id="buyerForm" class="vstack gap-3" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="id">

                            <!-- Buyer Name -->
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input
                                    type="text"
                                    name="name"
                                    class="form-control"
                                    required
                                    placeholder="Enter buyer name">
                                <div class="invalid-feedback">Please enter buyer name.</div>
                            </div>

                            <!-- Buyer Address -->
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea
                                    name="address"
                                    class="form-control"
                                    required
                                    placeholder="Enter buyer address"></textarea>
                                <div class="invalid-feedback">Please enter buyer address.</div>
                            </div>

                            <!-- Buyer Phone -->
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input
                                    type="tel"
                                    name="phone"
                                    class="form-control"
                                    required
                                    pattern="[0-9]{11}"
                                    minlength="11"
                                    maxlength="11"
                                    placeholder="03XXXXXXXXX">
                                <div class="form-text text-secondary">Enter 11-digit Pakistani number (e.g., 03001234567)</div>
                                <div class="invalid-feedback">Please enter a valid 11-digit Pakistani phone number.</div>
                            </div>

                            <!-- Buyer Image -->
                            <div class="mb-3">
                                <label class="form-label">Image</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <img
                                    id="buyerImagePreview"
                                    src=""
                                    alt=""
                                    style="width: 100px; margin-top: 10px; display: none;">
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button id="saveBuyerBtn" type="button" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </div>

</main>

<script src="./assets/js/buyers.js"></script>
<?php include 'includes/footer.php'; ?>