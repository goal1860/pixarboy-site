<?php
require_once __DIR__ . '/../config/config.php';
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
include __DIR__ . '/../includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="margin-bottom: 0.25rem;">👥 User Management</h2>
                <p style="color: var(--text-light); margin: 0;">Manage user accounts and permissions</p>
            </div>
            <a href="?action=new" class="btn btn-gradient">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                    <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                </svg>
                Add New User
            </a>
        </div>
        
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div class="post-author-avatar" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                    <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                    <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                        <span class="badge badge-info" style="margin-left: 0.5rem; font-size: 0.7rem;">YOU</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $u['role'] === 'admin' ? 'danger' : 
                                    ($u['role'] === 'editor' ? 'info' : 'primary'); 
                            ?>">
                                <?php 
                                    $roleIcon = $u['role'] === 'admin' ? '👑' : ($u['role'] === 'editor' ? '✏️' : '👤');
                                    echo $roleIcon . ' ' . ucfirst($u['role']); 
                                ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $u['status'] === 'active' ? 'success' : 'warning'; ?>">
                                <?php echo $u['status'] === 'active' ? '✅ Active' : '⏸️ Inactive'; ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                        <td class="table-actions">
                            <a href="?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-primary btn-sm" title="Edit User">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle;">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                </svg>
                            </a>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <a href="?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm" 
                                   data-confirm="Are you sure you want to delete user '<?php echo htmlspecialchars($u['username']); ?>'?" title="Delete User">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle;">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); color: var(--text-light); text-align: center;">
            <p>Total: <strong><?php echo count($users); ?></strong> user(s)</p>
        </div>
    </div>

<?php else: ?>
    <div class="card">
        <div style="margin-bottom: 2rem;">
            <h2><?php echo $action === 'new' ? '➕ Add New User' : '✏️ Edit User'; ?></h2>
            <p style="color: var(--text-light); margin-top: 0.5rem;">
                <?php echo $action === 'new' ? 'Create a new user account' : 'Update user information'; ?>
            </p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                    Username *
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-control" 
                    placeholder="Enter username"
                    value="<?php echo isset($user) ? htmlspecialchars($user['username']) : ''; ?>" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="email">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                    </svg>
                    Email *
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="user@example.com"
                    value="<?php echo isset($user) ? htmlspecialchars($user['email']) : ''; ?>" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="password">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                    Password <?php echo $action === 'new' ? '*' : ''; ?>
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="<?php echo $action === 'new' ? 'Enter password' : 'Leave blank to keep current password'; ?>"
                    <?php echo $action === 'new' ? 'required' : ''; ?>
                >
                <?php if ($action === 'edit'): ?>
                    <small style="color: var(--text-light);">Leave blank to keep current password</small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="role">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    Role *
                </label>
                <select id="role" name="role" class="form-control" required>
                    <option value="user" <?php echo (isset($user) && $user['role'] === 'user') ? 'selected' : ''; ?>>👤 User - Basic access</option>
                    <option value="editor" <?php echo (isset($user) && $user['role'] === 'editor') ? 'selected' : ''; ?>>✏️ Editor - Can create and edit content</option>
                    <option value="admin" <?php echo (isset($user) && $user['role'] === 'admin') ? 'selected' : ''; ?>>👑 Admin - Full access</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Status *
                </label>
                <select id="status" name="status" class="form-control" required>
                    <option value="active" <?php echo (!isset($user) || $user['status'] === 'active') ? 'selected' : ''; ?>>✅ Active - Can log in</option>
                    <option value="inactive" <?php echo (isset($user) && $user['status'] === 'inactive') ? 'selected' : ''; ?>>⏸️ Inactive - Cannot log in</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; padding-top: 1rem; border-top: 1px solid var(--border-color); margin-top: 2rem;">
                <button type="submit" class="btn btn-success">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Save User
                </button>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
                <?php if (isset($user) && $user['id'] != $_SESSION['user_id']): ?>
                    <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-danger" data-confirm="Are you sure you want to delete this user?" style="margin-left: auto;">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Delete User
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
