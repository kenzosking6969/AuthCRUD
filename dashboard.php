<?php
session_start();
// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection settings
$conn = new mysqli("localhost", "root", "", "login_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

// Process CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // INSERT operation
    if ($action === 'insert') {
        $first_name = trim($_POST['first_name']);
        $last_name  = trim($_POST['last_name']);
        $email      = trim($_POST['email']);
        $contact    = trim($_POST['contact']);
        $username   = trim($_POST['username']);
        $password   = trim($_POST['password']);

        if (!empty($first_name) && !empty($last_name) && !empty($email) && !empty($contact) && !empty($username) && !empty($password)) {
            // Hash the password before saving
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert into register table
            $stmt = $conn->prepare("INSERT INTO register (first_name, last_name, email, contact_info, username, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $first_name, $last_name, $email, $contact, $username, $hashedPassword);
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                // Also insert into login table
                $stmt2 = $conn->prepare("INSERT INTO login (user_id, username, password) VALUES (?, ?, ?)");
                $stmt2->bind_param("iss", $new_id, $username, $hashedPassword);
                if ($stmt2->execute()) {
                    $message = "Record inserted successfully.";
                } else {
                    $message = "Error inserting login data: " . $conn->error;
                }
                $stmt2->close();
            } else {
                $message = "Error inserting record: " . $conn->error;
            }
            $stmt->close();
        } else {
            $message = "Please fill in all required fields.";
        }
    }

    // UPDATE operation
    elseif ($action === 'update') {
        $id          = intval($_POST['id']);
        $first_name  = trim($_POST['first_name']);
        $last_name   = trim($_POST['last_name']);
        $email       = trim($_POST['email']);
        $contact     = trim($_POST['contact']);
        $usernameNew = trim($_POST['username']);
        $passwordNew = trim($_POST['password']);

        // Retrieve current register record to compare changes
        $result = $conn->query("SELECT username, password FROM register WHERE id = $id");
        $current = $result->fetch_assoc();

        // If a new password is provided, hash it; otherwise, keep the current hashed password.
        if (!empty($passwordNew)) {
            $hashedPassword = password_hash($passwordNew, PASSWORD_DEFAULT);
        } else {
            $hashedPassword = $current['password'];
        }

        // Update register table
        $stmt = $conn->prepare("UPDATE register SET first_name = ?, last_name = ?, email = ?, contact_info = ?, username = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $contact, $usernameNew, $hashedPassword, $id);
        if ($stmt->execute()) {
            // If username or password has changed, update the login table as well.
            if ($usernameNew !== $current['username'] || !empty($passwordNew)) {
                $stmt2 = $conn->prepare("UPDATE login SET username = ?, password = ? WHERE user_id = ?");
                $stmt2->bind_param("ssi", $usernameNew, $hashedPassword, $id);
                if ($stmt2->execute()) {
                    $message = "Record updated successfully.";
                } else {
                    $message = "Error updating login data: " . $conn->error;
                }
                $stmt2->close();
            } else {
                $message = "Record updated successfully.";
            }
        } else {
            $message = "Error updating record: " . $conn->error;
        }
        $stmt->close();
    }

    // DELETE operation
    elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        // Deleting from register will cascade to login table (if foreign key ON DELETE CASCADE is set)
        $stmt = $conn->prepare("DELETE FROM register WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Record deleted successfully.";
        } else {
            $message = "Error deleting record: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all records from the register table for display
$result = $conn->query("SELECT * FROM register");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Manage Users</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- External Dashboard CSS -->
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
<div class="container">
    <?php if ($message != ""): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>
    <h1 class="mb-4">Dashboard - Manage Register Data</h1>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#insertModal">Insert New Record</button>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Username</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['contact_info']); ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td>
                    <button class="btn btn-warning btn-sm editBtn"
                        data-id="<?php echo $row['id']; ?>"
                        data-first_name="<?php echo htmlspecialchars($row['first_name']); ?>"
                        data-last_name="<?php echo htmlspecialchars($row['last_name']); ?>"
                        data-email="<?php echo htmlspecialchars($row['email']); ?>"
                        data-contact="<?php echo htmlspecialchars($row['contact_info']); ?>"
                        data-username="<?php echo htmlspecialchars($row['username']); ?>"
                    >Edit</button>
                    <button class="btn btn-danger btn-sm deleteBtn" data-id="<?php echo $row['id']; ?>">Delete</button>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <a href="logout.php" class="btn btn-secondary">Logout</a>
</div>

<!-- Insert Modal -->
<div class="modal fade" id="insertModal" tabindex="-1" aria-labelledby="insertModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="dashboard.php">
          <div class="modal-header">
            <h5 class="modal-title" id="insertModalLabel">Insert New Record</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
              <input type="hidden" name="action" value="insert">
              <div class="mb-3">
                  <label class="form-label">First Name</label>
                  <input type="text" name="first_name" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label">Last Name</label>
                  <input type="text" name="last_name" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label">Contact</label>
                  <input type="text" name="contact" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label">Username</label>
                  <input type="text" name="username" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label">Password</label>
                  <input type="password" name="password" class="form-control" required>
              </div>
          </div>
          <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary">Insert</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Update Modal -->
<div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="dashboard.php">
          <div class="modal-header">
            <h5 class="modal-title" id="updateModalLabel">Update Record</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" id="update_id">
              <div class="mb-3">
                  <label class="form-label">First Name</label>
                  <input type="text" name="first_name" id="update_first_name" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label">Last Name</label>
                  <input type="text" name="last_name" id="update_last_name" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" id="update_email" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label">Contact</label>
                  <input type="text" name="contact" id="update_contact" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label">Username</label>
                  <input type="text" name="username" id="update_username" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label">Password <small>(Leave blank to keep unchanged)</small></label>
                  <input type="password" name="password" id="update_password" class="form-control">
              </div>
          </div>
          <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-warning">Update</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="dashboard.php">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteModalLabel">Delete Record</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" id="delete_id">
              <p>Are you sure you want to delete this record?</p>
          </div>
          <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger">Delete</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Populate Update Modal with record data
document.querySelectorAll('.editBtn').forEach(button => {
    button.addEventListener('click', function(){
        const id         = this.getAttribute('data-id');
        const first_name = this.getAttribute('data-first_name');
        const last_name  = this.getAttribute('data-last_name');
        const email      = this.getAttribute('data-email');
        const contact    = this.getAttribute('data-contact');
        const username   = this.getAttribute('data-username');

        document.getElementById('update_id').value = id;
        document.getElementById('update_first_name').value = first_name;
        document.getElementById('update_last_name').value = last_name;
        document.getElementById('update_email').value = email;
        document.getElementById('update_contact').value = contact;
        document.getElementById('update_username').value = username;
        // Clear password field so that if left blank, it remains unchanged.
        document.getElementById('update_password').value = "";

        // Show the update modal
        var updateModal = new bootstrap.Modal(document.getElementById('updateModal'));
        updateModal.show();
    });
});

// Populate Delete Modal with record id
document.querySelectorAll('.deleteBtn').forEach(button => {
    button.addEventListener('click', function(){
        const id = this.getAttribute('data-id');
        document.getElementById('delete_id').value = id;
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    });
});
</script>
</body>
</html>
