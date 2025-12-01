<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php'; // yahan mysqli connection hona chahiye
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/topbar.php';

$msg = "";

// Form submit handle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $image_url = $_POST['image_url'] ?? '';

    if (!empty($name)) {
        if ($id) {
            // Update vendor
            $stmt = $conn->prepare("UPDATE vendors SET name=?, phone=?, address=?, image_url=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $phone, $address, $image_url, $id);
            $stmt->execute();
            $msg = "Vendor updated successfully!";
            $stmt->close();
        } else {
            // Insert vendor
            $stmt = $conn->prepare("INSERT INTO vendors (name, phone, address, image_url) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $phone, $address, $image_url);
            $stmt->execute();
            $msg = "Vendor added successfully!";
            $stmt->close();
        }
    } else {
        $msg = "Name is required!";
    }
}

// Fetch vendors list
$result = $conn->query("SELECT * FROM vendors ORDER BY id DESC");
?>

<main class="content">
    <section id="view" class="p-3 p-md-4">
        <div class="container">
            <h3 class="mb-3">Vendors</h3>

            <?php if (!empty($msg)): ?>
                <div class="alert alert-info"><?= $msg ?></div>
            <?php endif; ?>

            <!-- Add Vendor Form -->
            <form method="POST" class="mb-4">
                <input type="hidden" name="id" />
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input name="phone" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <input name="address" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Image URL (optional)</label>
                        <input name="image_url" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Save Vendor</button>
            </form>

            <!-- Vendors List -->
            <div id="vendorList" class="table-responsive">
                <?php if ($result->num_rows === 0): ?>
                    <p>No vendors found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Image</th>
                            </tr>
                            <?php while ($v = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $v['id'] ?></td>
                                    <td><?= $v['name'] ?></td>
                                    <td><?= $v['phone'] ?></td>
                                    <td><?= $v['address'] ?></td>
                                    <td>
                                        <?php if (!empty($v['image_url'])): ?>
                                            <img src="<?= $v['image_url'] ?>" width="50">
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>