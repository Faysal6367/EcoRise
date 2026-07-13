<?php
/**
 * EcoRise - Delete Own Campaign
 * 
 * Allows users to delete campaigns they personally created.
 */
require_once 'config.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('signin.php', 'Please sign in.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        redirect('dashboard.php', 'Invalid session.', 'error');
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $user_id = $_SESSION['user_id'];

    if ($id > 0) {
        try {
            // Verify ownership BEFORE deleting
            $stmt = $pdo->prepare("SELECT image_path FROM campaigns WHERE id = ? AND created_by = ?");
            $stmt->execute([$id, $user_id]);
            $camp = $stmt->fetch();

            if ($camp) {
                $img = $camp['image_path'];

                // Delete DB record
                $stmt_del = $pdo->prepare("DELETE FROM campaigns WHERE id = ? AND created_by = ?");
                if ($stmt_del->execute([$id, $user_id])) {
                    // Delete file if exists and not default
                    if ($img && $img !== 'assets/campaigns/default.jpg' && file_exists($img)) {
                        unlink($img);
                    }
                    redirect('dashboard.php', 'Your project has been successfully removed.', 'success');
                } else {
                    redirect('dashboard.php', 'Could not complete the deletion request.', 'error');
                }
            } else {
                redirect('dashboard.php', 'Access denied: You do not own this project or it was not found.', 'error');
            }
        } catch (PDOException $e) {
            redirect('dashboard.php', 'Error: ' . $e->getMessage(), 'error');
        }
    } else {
        redirect('dashboard.php', 'No project ID provided.', 'error');
    }
} else {
    redirect('dashboard.php');
}
?>
