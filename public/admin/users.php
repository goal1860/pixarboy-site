<?php
require_once '../../config/config.php';
requireLogin();

if (!isAdmin()) {
    showMessage('Access denied. Admin privileges required.', 'error');
    redirect('/admin/');
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';
$userId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'new' || $action === 'edit') {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $role = $_POST['role'];
        $status = $_POST['status'];
        
        try {
            if ($action === 'new') {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $password, $role, $status]);
                showMessage('User created successfully!', 'success');
            } else {
                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, password=?, role=?, status=? WHERE id=?");
                    $stmt->execute([$username, $email, $password, $role, $status, $userId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, role=?, status=? WHERE id=?");
                    $stmt->execute([$username, $email, $role, $status, $userId]);
                }
                showMessage('User updated successfully!', 'success');
            }
            redirect('/admin/users.php');
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Handle delete
if ($action === 'delete' && $userId) {
    if ($userId != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        showMessage('User deleted successfully!', 'success');
    } else {
        showMessage('Cannot delete your own account!', 'error');
    }
    redirect('/admin/users.php');
}

// Get user for editing
if ($action === 'edit' && $userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        redirect('/admin/users.php');
    }
}

// Get all users
if ($action === 'list') {
    $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
}

$pageTitle = ucfirst($action) . ' Users';
include '../../includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Users Management</h2>
            <a href="?action=new" class="btn btn-success">Add New User</a>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><span class="badge badge-info"><?php echo ucfirst($u['role']); ?></span></td>
                    <td>
                        <span class="badge badge-<?php echo $u['status'] === 'active' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($u['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                    <td class="table-actions">
                        <a href="?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <a href="?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm" 
                               onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <div class="card">
        <h2><?php echo $action === 'new' ? 'Add New User' : 'Edit User'; ?></h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?php echo isset($user) ? htmlspecialchars($user['username']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo isset($user) ? htmlspecialchars($user['email']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password <?php echo $action === 'new' ? '*' : '(leave blank to keep current)'; ?></label>
                <input type="password" id="password" name="password" class="form-control" 
                       <?php echo $action === 'new' ? 'required' : ''; ?>>
            </div>
            
            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="user" <?php echo (isset($user) && $user['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                    <option value="editor" <?php echo (isset($user) && $user['role'] === 'editor') ? 'selected' : ''; ?>>Editor</option>
                    <option value="admin" <?php echo (isset($user) && $user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="active" <?php echo (!isset($user) || $user['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo (isset($user) && $user['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-success">Save User</button>
            <a href="users.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>

