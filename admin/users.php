<?php
$page_title = "User Management";
require_once '../includes/auth.php';
require_admin();
$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];

    if (empty($full_name) || empty($username) || empty($role)) {
        $error = "Please fill in all required fields.";
    } else {
        if ($action === 'add') {
            $password = trim($_POST['password']);
            if (empty($password)) {
                $error = "Password is required for new users.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                try {
                    $stmt = $db->prepare("INSERT INTO users (full_name, username, password_hash, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$full_name, $username, $password_hash, $role]);
                    $message = "User created successfully.";
                } catch (PDOException $e) {
                    $error = "Error: Username already exists.";
                }
            }
        } elseif ($action === 'edit') {
            $id = intval($_POST['id']);
            $password = trim($_POST['password']);

            try {
                if (!empty($password)) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET full_name = ?, username = ?, role = ?, password_hash = ? WHERE id = ?");
                    $stmt->execute([$full_name, $username, $role, $password_hash, $id]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET full_name = ?, username = ?, role = ? WHERE id = ?");
                    $stmt->execute([$full_name, $username, $role, $id]);
                }
                $message = "User updated successfully.";
            } catch (PDOException $e) {
                $error = "Error: Username conflict.";
            }
        }
    }
}

// Reset Password
if (isset($_GET['reset'])) {
    $reset_id = intval($_GET['reset']);
    $default_pass = password_hash('password123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$default_pass, $reset_id]);
    $message = "Password reset successfully to 'password123'.";
}

// Delete User
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($del_id === intval($_SESSION['user_id'])) {
        $error = "You cannot delete your own currently logged-in account.";
    } else {
        try {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$del_id]);
            $message = "User deleted successfully.";
        } catch (PDOException $e) {
            $error = "Cannot delete user associated with stock movement logs.";
        }
    }
}

$users = $db->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();
require_once '../includes/header.php';
?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header-flex">
        <h3>User Management</h3>
        <button class="btn btn-success" onclick="openUserModal('add')">Add New User</button>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><span class="badge badge-<?php echo $u['role'] === 'admin' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                        <td><?php echo $u['created_at']; ?></td>
                        <td><?php echo $u['updated_at']; ?></td>
                        <td>
                            <button class="btn-sm btn-warning" onclick='openUserModal("edit", <?php echo json_encode($u); ?>)'>Edit</button>
                            <a href="users.php?reset=<?php echo $u['id']; ?>" class="btn-sm btn-info" onclick="return confirm('Reset password to password123?');">Reset Pwd</a>
                            <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                <a href="users.php?delete=<?php echo $u['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Delete this user?');">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="userModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeUserModal()">&times;</span>
        <h3 id="userModalTitle">Add User</h3>
        <form method="POST" action="users.php">
            <input type="hidden" name="action" id="userFormAction" value="add">
            <input type="hidden" name="id" id="userId">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" id="userFullName" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" id="userUsername" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Role *</label>
                <select name="role" id="userRole" class="form-control" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label>Password <span id="passHint">(Required for new user)</span></label>
                <input type="password" name="password" id="userPassword" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Save User</button>
        </form>
    </div>
</div>

<script>
function openUserModal(mode, data = null) {
    document.getElementById('userModal').style.display = 'block';
    if (mode === 'add') {
        document.getElementById('userModalTitle').innerText = 'Add New User';
        document.getElementById('userFormAction').value = 'add';
        document.getElementById('userId').value = '';
        document.getElementById('userFullName').value = '';
        document.getElementById('userUsername').value = '';
        document.getElementById('userRole').value = 'user';
        document.getElementById('userPassword').value = '';
        document.getElementById('passHint').innerText = '(Required)';
    } else if (mode === 'edit' && data) {
        document.getElementById('userModalTitle').innerText = 'Edit User';
        document.getElementById('userFormAction').value = 'edit';
        document.getElementById('userId').value = data.id;
        document.getElementById('userFullName').value = data.full_name;
        document.getElementById('userUsername').value = data.username;
        document.getElementById('userRole').value = data.role;
        document.getElementById('userPassword').value = '';
        document.getElementById('passHint').innerText = '(Leave blank to keep existing password)';
    }
}
function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}
</script>

<?php require_once '../includes/footer.php'; ?>