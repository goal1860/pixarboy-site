<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/MigrationRunner.php';

requireLogin();

// Only admins can run migrations
if (!isAdmin()) {
    redirect('/admin/');
}

$pdo = getDBConnection();
$runner = new MigrationRunner($pdo);

$output = '';
$error = '';
$action = $_GET['action'] ?? 'status';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    ob_start();
    try {
        switch ($action) {
            case 'migrate':
                $ran = $runner->migrate();
                if (empty($ran)) {
                    $output = "No pending migrations to run.";
                } else {
                    $output = "Successfully ran " . count($ran) . " migration(s):\n- " . implode("\n- ", $ran);
                }
                break;
                
            case 'rollback':
                $result = $runner->rollback();
                if (isset($result['message'])) {
                    $output = $result['message'];
                } else {
                    $output = "Successfully rolled back " . count($result) . " migration(s):\n- " . implode("\n- ", $result);
                }
                break;
                
            case 'reset':
                if (isset($_POST['confirm']) && $_POST['confirm'] === 'RESET') {
                    $result = $runner->reset();
                    $output = $result['message'] ?? 'Database reset complete.';
                } else {
                    $error = 'You must type "RESET" to confirm database reset.';
                }
                break;
        }
        
        $consoleOutput = ob_get_clean();
        if ($consoleOutput) {
            $output .= "\n\n=== Console Output ===\n" . $consoleOutput;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        $consoleOutput = ob_get_clean();
        if ($consoleOutput) {
            $error .= "\n\n=== Console Output ===\n" . $consoleOutput;
        }
    }
    
    $action = 'status'; // Show status after action
}

// Get migration status
$status = $runner->status();
$pending = array_filter($status, fn($m) => !$m['executed']);
$executed = array_filter($status, fn($m) => $m['executed']);

$pageTitle = 'Database Migrations';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1>🔄 Database Migrations</h1>
            <p style="color: #666; margin-top: 0.5rem;">Manage database schema changes safely</p>
        </div>
        <div>
            <a href="/admin/" class="btn btn-outline">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                </svg>
                Dashboard
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error" style="white-space: pre-wrap; font-family: monospace; font-size: 0.9rem;">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($output): ?>
        <div class="alert alert-success" style="white-space: pre-wrap; font-family: monospace; font-size: 0.9rem;">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <?php echo htmlspecialchars($output); ?>
        </div>
    <?php endif; ?>

    <!-- Migration Actions -->
    <div class="card">
        <h2>Migration Actions</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
            
            <!-- Run Migrations -->
            <div class="card" style="background: var(--light-color); border: 2px solid var(--primary-color);">
                <h3 style="margin-top: 0;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle;">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
                    </svg>
                    Run Migrations
                </h3>
                <p style="color: #666; font-size: 0.9rem;">
                    Execute all pending database migrations
                </p>
                <?php if (!empty($pending)): ?>
                    <form method="POST" action="" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="migrate">
                        <button type="submit" class="btn btn-primary btn-block" onclick="return confirm('Are you sure you want to run <?php echo count($pending); ?> pending migration(s)?')">
                            Run <?php echo count($pending); ?> Pending Migration(s)
                        </button>
                    </form>
                <?php else: ?>
                    <div class="badge badge-success" style="margin-top: 1rem;">All migrations up to date</div>
                <?php endif; ?>
            </div>

            <!-- Rollback -->
            <div class="card" style="background: var(--light-color); border: 2px solid #ff9800;">
                <h3 style="margin-top: 0;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle;">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.707-10.293a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L9.414 11H13a1 1 0 100-2H9.414l1.293-1.293z" clip-rule="evenodd"/>
                    </svg>
                    Rollback
                </h3>
                <p style="color: #666; font-size: 0.9rem;">
                    Undo the last batch of migrations
                </p>
                <?php if (!empty($executed)): ?>
                    <form method="POST" action="" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="rollback">
                        <button type="submit" class="btn btn-outline btn-block" style="border-color: #ff9800; color: #ff9800;" onclick="return confirm('Are you sure you want to rollback the last batch of migrations?\n\nThis will undo recent database changes.')">
                            Rollback Last Batch
                        </button>
                    </form>
                <?php else: ?>
                    <div class="badge badge-secondary" style="margin-top: 1rem;">No migrations to rollback</div>
                <?php endif; ?>
            </div>

            <!-- Reset (Danger) -->
            <div class="card" style="background: #fff5f5; border: 2px solid #dc3545;">
                <h3 style="margin-top: 0; color: #dc3545;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle;">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Reset Database
                </h3>
                <p style="color: #666; font-size: 0.9rem;">
                    <strong>DANGER:</strong> Rollback ALL migrations
                </p>
                <form method="POST" action="" style="margin-top: 1rem;" onsubmit="return confirm('⚠️ WARNING!\n\nThis will rollback ALL migrations and potentially destroy data.\n\nAre you ABSOLUTELY sure?')">
                    <input type="hidden" name="action" value="reset">
                    <input 
                        type="text" 
                        name="confirm" 
                        class="form-control" 
                        placeholder='Type "RESET" to confirm'
                        style="margin-bottom: 0.5rem; border-color: #dc3545;"
                        required
                    >
                    <button type="submit" class="btn btn-danger btn-block">
                        Reset All Migrations
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Migration Status -->
    <div class="card">
        <h2>Migration Status</h2>
        
        <?php if (empty($status)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3>No Migrations Found</h3>
                <p>Create migration files in: <code>database/migrations/</code></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Status</th>
                            <th>Migration</th>
                            <th>File</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($status as $migration): ?>
                            <tr>
                                <td>
                                    <?php if ($migration['executed']): ?>
                                        <span class="badge badge-success" title="Executed">
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Done
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning" title="Pending">
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                            </svg>
                                            Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($migration['name']); ?></strong></td>
                                <td>
                                    <code style="background: var(--light-color); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">
                                        <?php echo htmlspecialchars($migration['file']); ?>.php
                                    </code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary -->
            <div style="margin-top: 1.5rem; padding: 1rem; background: var(--light-color); border-radius: 8px; display: flex; gap: 2rem;">
                <div>
                    <strong>Total:</strong> <?php echo count($status); ?> migration(s)
                </div>
                <div>
                    <strong style="color: var(--success-color);">Executed:</strong> <?php echo count($executed); ?>
                </div>
                <div>
                    <strong style="color: #ff9800;">Pending:</strong> <?php echo count($pending); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Help Section -->
    <div class="card" style="background: var(--light-color); border: 2px solid #17a2b8;">
        <h3 style="margin-top: 0;">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle;">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
            About Migrations
        </h3>
        <div style="color: #666; line-height: 1.6;">
            <p><strong>Migrations</strong> allow you to update your database schema safely without losing data.</p>
            <ul style="margin-top: 0.5rem;">
                <li>Create migration files in: <code>database/migrations/</code></li>
                <li>File format: <code>###_description.php</code> (e.g., <code>001_add_categories.php</code>)</li>
                <li>Each migration has <code>up()</code> (apply) and <code>down()</code> (revert) methods</li>
                <li>Migrations run in order and are tracked in the database</li>
                <li>You can also run migrations via CLI: <code>php migrate.php</code></li>
            </ul>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

