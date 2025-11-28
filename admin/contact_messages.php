<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';
$messageId = $_GET['id'] ?? null;

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status' && $messageId) {
    $status = sanitize($_POST['status']);
    
    if (in_array($status, ['new', 'read', 'replied', 'archived'])) {
        try {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $messageId]);
            showMessage('Message status updated successfully!', 'success');
            redirect('/admin/contact_messages.php?action=view&id=' . $messageId);
        } catch (PDOException $e) {
            showMessage('Error updating status: ' . $e->getMessage(), 'error');
        }
    }
}

// Handle delete
if ($action === 'delete' && $messageId) {
    try {
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$messageId]);
        showMessage('Message deleted successfully!', 'success');
    } catch (PDOException $e) {
        showMessage('Error deleting message: ' . $e->getMessage(), 'error');
    }
    redirect('/admin/contact_messages.php');
}

// Get message for viewing
if ($action === 'view' && $messageId) {
    $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch();
    
    if (!$message) {
        redirect('/admin/contact_messages.php');
    }
    
    // Mark as read if it's new
    if ($message['status'] === 'new') {
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'read', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$messageId]);
        $message['status'] = 'read';
    }
}

// Get all messages
if ($action === 'list') {
    $statusFilter = $_GET['status'] ?? '';
    $query = "SELECT * FROM contact_messages";
    $params = [];
    
    if ($statusFilter && in_array($statusFilter, ['new', 'read', 'replied', 'archived'])) {
        $query .= " WHERE status = ?";
        $params[] = $statusFilter;
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();
    
    // Get counts by status
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM contact_messages GROUP BY status");
    $statusCounts = [];
    while ($row = $stmt->fetch()) {
        $statusCounts[$row['status']] = $row['count'];
    }
}

$pageTitle = 'Contact Messages';
include __DIR__ . '/../includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="margin-bottom: 0.25rem;">📧 Contact Messages</h2>
                <p style="color: var(--text-light); margin: 0;">Manage contact form submissions</p>
            </div>
        </div>
        
        <!-- Status Filter Tabs -->
        <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; border-bottom: 2px solid var(--border-color); padding-bottom: 1rem;">
            <a href="?action=list" 
               class="badge <?php echo !$statusFilter ? 'badge-primary' : 'badge-secondary'; ?>" 
               style="text-decoration: none; padding: 0.5rem 1rem; font-weight: 600;">
                All (<?php echo array_sum($statusCounts); ?>)
            </a>
            <a href="?action=list&status=new" 
               class="badge <?php echo $statusFilter === 'new' ? 'badge-primary' : 'badge-secondary'; ?>" 
               style="text-decoration: none; padding: 0.5rem 1rem; font-weight: 600;">
                New (<?php echo $statusCounts['new'] ?? 0; ?>)
            </a>
            <a href="?action=list&status=read" 
               class="badge <?php echo $statusFilter === 'read' ? 'badge-primary' : 'badge-secondary'; ?>" 
               style="text-decoration: none; padding: 0.5rem 1rem; font-weight: 600;">
                Read (<?php echo $statusCounts['read'] ?? 0; ?>)
            </a>
            <a href="?action=list&status=replied" 
               class="badge <?php echo $statusFilter === 'replied' ? 'badge-primary' : 'badge-secondary'; ?>" 
               style="text-decoration: none; padding: 0.5rem 1rem; font-weight: 600;">
                Replied (<?php echo $statusCounts['replied'] ?? 0; ?>)
            </a>
            <a href="?action=list&status=archived" 
               class="badge <?php echo $statusFilter === 'archived' ? 'badge-primary' : 'badge-secondary'; ?>" 
               style="text-decoration: none; padding: 0.5rem 1rem; font-weight: 600;">
                Archived (<?php echo $statusCounts['archived'] ?? 0; ?>)
            </a>
        </div>
        
        <?php if (empty($messages)): ?>
            <div class="empty-state" style="border: none;">
                <div class="empty-state-icon">📭</div>
                <h3>No messages found</h3>
                <p><?php echo $statusFilter ? 'No ' . $statusFilter . ' messages.' : 'No contact messages yet.'; ?></p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>Email</th>
                            <th>Message Preview</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                        <tr style="<?php echo $msg['status'] === 'new' ? 'background: rgba(102, 126, 234, 0.05);' : ''; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($msg['name']); ?></strong>
                            </td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" 
                                   style="color: var(--primary-color); text-decoration: none;">
                                    <?php echo htmlspecialchars($msg['email']); ?>
                                </a>
                            </td>
                            <td>
                                <div style="max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars(substr($msg['message'], 0, 100)); ?>
                                    <?php echo strlen($msg['message']) > 100 ? '...' : ''; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $msg['status'] === 'new' ? 'danger' : 
                                        ($msg['status'] === 'read' ? 'warning' : 
                                        ($msg['status'] === 'replied' ? 'success' : 'secondary')); 
                                ?>">
                                    <?php echo ucfirst($msg['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('M j, Y g:i A', strtotime($msg['created_at'])); ?>
                            </td>
                            <td class="table-actions">
                                <a href="?action=view&id=<?php echo $msg['id']; ?>" 
                                   class="btn btn-primary btn-sm" 
                                   title="View Message">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                    </svg>
                                </a>
                                <a href="?action=delete&id=<?php echo $msg['id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   data-confirm="Are you sure you want to delete this message?"
                                   title="Delete">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); color: var(--text-light); text-align: center;">
                <p>Total: <strong><?php echo count($messages); ?></strong> message(s)</p>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'view' && isset($message)): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <a href="/admin/contact_messages.php" 
                   style="color: var(--primary-color); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                    </svg>
                    Back to Messages
                </a>
                <h2 style="margin: 0;">Message Details</h2>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <span class="badge badge-<?php 
                    echo $message['status'] === 'new' ? 'danger' : 
                        ($message['status'] === 'read' ? 'warning' : 
                        ($message['status'] === 'replied' ? 'success' : 'secondary')); 
                ?>">
                    <?php echo ucfirst($message['status']); ?>
                </span>
            </div>
        </div>
        
        <!-- Message Info -->
        <div style="background: var(--light-color); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div>
                    <div style="font-size: 0.875rem; color: var(--text-light); margin-bottom: 0.25rem;">From</div>
                    <div style="font-weight: 600; font-size: 1.1rem;">
                        <?php echo htmlspecialchars($message['name']); ?>
                    </div>
                </div>
                <div>
                    <div style="font-size: 0.875rem; color: var(--text-light); margin-bottom: 0.25rem;">Email</div>
                    <div>
                        <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" 
                           style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                            <?php echo htmlspecialchars($message['email']); ?>
                        </a>
                    </div>
                </div>
                <div>
                    <div style="font-size: 0.875rem; color: var(--text-light); margin-bottom: 0.25rem;">Date</div>
                    <div style="font-weight: 600;">
                        <?php echo date('F j, Y \a\t g:i A', strtotime($message['created_at'])); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Message Content -->
        <div style="margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1rem;">Message</h3>
            <div style="background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border-color); white-space: pre-wrap; line-height: 1.8; color: var(--text-color);">
                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
            </div>
        </div>
        
        <!-- Status Update Form -->
        <div class="card" style="background: var(--light-color);">
            <h3 style="margin-bottom: 1rem;">Update Status</h3>
            <form method="POST" action="?action=update_status&id=<?php echo $message['id']; ?>" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <select name="status" class="form-control" style="min-width: 200px;" required>
                    <option value="new" <?php echo $message['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                    <option value="read" <?php echo $message['status'] === 'read' ? 'selected' : ''; ?>>Read</option>
                    <option value="replied" <?php echo $message['status'] === 'replied' ? 'selected' : ''; ?>>Replied</option>
                    <option value="archived" <?php echo $message['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    Update Status
                </button>
            </form>
        </div>
        
        <!-- Actions -->
        <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
            <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>?subject=Re: Your message to <?php echo SITE_NAME; ?>" 
               class="btn btn-gradient">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                </svg>
                Reply via Email
            </a>
            <a href="?action=delete&id=<?php echo $message['id']; ?>" 
               class="btn btn-danger"
               data-confirm="Are you sure you want to delete this message?">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                Delete Message
            </a>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

