<?php
/**
 * EcoRise - Admin Campaign Management Processor (WITH LOGGING)
 * 
 * Securely handles Create, Approve, Deactivate, and Delete actions.
 */
require_once '../config.php';

// Diagnostic Logging
$log_msg = date('Y-m-d H:i:s') . " - Action: " . ($_POST['action'] ?? 'NONE') . " - ID: " . ($_POST['id'] ?? 'NONE') . " - User: " . ($_SESSION['user_id'] ?? 'ANONYMOUS') . "\n";
file_put_contents('actions_debug.log', $log_msg, FILE_APPEND);

// Access Control
if (!is_admin()) {
    header("HTTP/1.1 403 Forbidden");
    file_put_contents('actions_debug.log', "DENIED: Not an admin\n", FILE_APPEND);
    exit('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        file_put_contents('actions_debug.log', "CSRF FAILED\n", FILE_APPEND);
        redirect('campaigns.php', 'Security session has expired. Please refresh the page.', 'error');
    }

    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // --- DELETE ACTION ---
    if ($action === 'delete') {
        if ($id <= 0) {
            file_put_contents('actions_debug.log', "INVALID ID: $id\n", FILE_APPEND);
            redirect('campaigns.php', 'Invalid project ID.', 'error');
        }

        try {
            $stmt = $pdo->prepare("SELECT image_path FROM campaigns WHERE id = ?");
            $stmt->execute([$id]);
            $image_path = $stmt->fetchColumn();

            $stmt_del = $pdo->prepare("DELETE FROM campaigns WHERE id = ?");
            if ($stmt_del->execute([$id])) {
                if ($image_path && $image_path !== 'assets/campaigns/default.jpg') {
                    @unlink('../' . $image_path);
                }
                file_put_contents('actions_debug.log', "DELETE SUCCESS: ID $id\n", FILE_APPEND);
                redirect('campaigns.php', 'Project successfully deleted.', 'success');
            } else {
                file_put_contents('actions_debug.log', "DB EXECUTE FAILED: ID $id\n", FILE_APPEND);
                redirect('campaigns.php', 'Database failed to delete the record.', 'error');
            }
        } catch (PDOException $e) {
            file_put_contents('actions_debug.log', "PDO EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
            redirect('campaigns.php', 'Error: ' . $e->getMessage(), 'error');
        }
    }

    // --- APPROVE ACTION ---
    elseif ($action === 'approve' && $id > 0) {
        $campaign_stmt = $pdo->prepare("SELECT title, created_by FROM campaigns WHERE id = ?");
        $campaign_stmt->execute([$id]);
        $campaign = $campaign_stmt->fetch();

        $stmt = $pdo->prepare("UPDATE campaigns SET status = 'active' WHERE id = ?");
        $stmt->execute([$id]);

        if ($campaign && (int) ($campaign['created_by'] ?? 0) > 0) {
            try {
                create_notification(
                    $pdo,
                    (int) $campaign['created_by'],
                    'campaign_approved',
                    'Campaign is now active',
                    'Your campaign "' . (string) ($campaign['title'] ?? 'Campaign') . '" is active.',
                    '../support.php?id=' . $id,
                    'fa-circle-check'
                );
            } catch (Throwable $notifyError) {
                error_log('Notification create failed (campaign activate): ' . $notifyError->getMessage());
            }
        }

        file_put_contents('actions_debug.log', "APPROVE SUCCESS: ID $id\n", FILE_APPEND);
        redirect('campaigns.php', 'Project activated.');
    }

    // --- DEACTIVATE ACTION ---
    elseif ($action === 'deactivate' && $id > 0) {
        $campaign_stmt = $pdo->prepare("SELECT title, created_by FROM campaigns WHERE id = ?");
        $campaign_stmt->execute([$id]);
        $campaign = $campaign_stmt->fetch();

        $stmt = $pdo->prepare("UPDATE campaigns SET status = 'pending' WHERE id = ?");
        $stmt->execute([$id]);

        if ($campaign && (int) ($campaign['created_by'] ?? 0) > 0) {
            try {
                create_notification(
                    $pdo,
                    (int) $campaign['created_by'],
                    'campaign_rejected',
                    'Campaign moved to pending',
                    'Your campaign "' . (string) ($campaign['title'] ?? 'Campaign') . '" was moved to pending by admin for review.',
                    '../dashboard.php',
                    'fa-hourglass-half'
                );
            } catch (Throwable $notifyError) {
                error_log('Notification create failed (campaign deactivate): ' . $notifyError->getMessage());
            }
        }

        file_put_contents('actions_debug.log', "DEACTIVATE SUCCESS: ID $id\n", FILE_APPEND);
        redirect('campaigns.php', 'Project moved to pending.');
    }

    // --- CREATE ACTION ---
    elseif ($action === 'create') {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $target_amount = filter_var($_POST['target_amount'], FILTER_VALIDATE_FLOAT);

        if (!$title || !$description || !$target_amount) {
            file_put_contents('actions_debug.log', "CREATE FAILED: Missing fields\n", FILE_APPEND);
            redirect('campaigns.php', 'Missing fields.', 'error');
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO campaigns (title, description, target_amount, image_path, created_by, status) VALUES (?, ?, ?, 'assets/campaigns/default.jpg', ?, 'active')");
            $stmt->execute([$title, $description, $target_amount, $_SESSION['user_id']]);
            file_put_contents('actions_debug.log', "CREATE SUCCESS\n", FILE_APPEND);
            redirect('campaigns.php', 'New campaign created.');
        } catch (PDOException $e) {
            file_put_contents('actions_debug.log', "CREATE EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
            redirect('campaigns.php', 'Error: ' . $e->getMessage(), 'error');
        }
    }

} else {
    redirect('campaigns.php');
}
?>
