<?php
/**
 * EcoRise - Stripe Payment Success Handler
 */
require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php';

if (!is_logged_in()) {
    redirect('signin.php', 'Please sign in to continue.', 'error');
}

$session_id = sanitize($_GET['session_id'] ?? '');
if ($session_id === '') {
    redirect('index.php', 'Missing payment session.', 'error');
}

try {
    $existing = $pdo->prepare("SELECT campaign_id FROM donations WHERE stripe_session_id = ? LIMIT 1");
    $existing->execute([$session_id]);
    $existing_row = $existing->fetch();

    if ($existing_row) {
        redirect('support.php?id=' . (int) $existing_row['campaign_id'], 'Payment already confirmed.', 'success');
    }

    $stripe = new \Stripe\StripeClient(STRIPE_SECRET_KEY);
    $session = $stripe->checkout->sessions->retrieve($session_id);

    if (($session->payment_status ?? '') !== 'paid') {
        redirect('index.php', 'Payment is not completed yet.', 'error');
    }

    $campaign_id = (int) ($session->metadata->campaign_id ?? 0);
    $user_id = (int) ($session->metadata->user_id ?? 0);
    $amount = (float) ($session->metadata->amount ?? 0);

    if ($campaign_id <= 0 || $user_id <= 0 || $amount <= 0) {
        redirect('index.php', 'Invalid payment metadata.', 'error');
    }

    if ($user_id !== (int) $_SESSION['user_id']) {
        redirect('index.php', 'Payment user mismatch.', 'error');
    }

    $pdo->beginTransaction();

    $stmt_don = $pdo->prepare("INSERT INTO donations (user_id, campaign_id, amount, stripe_session_id, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_don->execute([$user_id, $campaign_id, $amount, $session_id, 'card', 'paid']);

    $stmt_upd = $pdo->prepare("UPDATE campaigns SET raised_amount = raised_amount + ? WHERE id = ?");
    $stmt_upd->execute([$amount, $campaign_id]);

    $stmt_check = $pdo->prepare("SELECT raised_amount, target_amount, title, created_by FROM campaigns WHERE id = ?");
    $stmt_check->execute([$campaign_id]);
    $cap_info = $stmt_check->fetch();

    if ($cap_info && (int) ($cap_info['created_by'] ?? 0) > 0) {
        try {
            create_notification(
                $pdo,
                (int) $cap_info['created_by'],
                'payment_received',
                'New donation received',
                'Your campaign "' . (string) ($cap_info['title'] ?? 'Campaign') . '" received a donation of BDT ' . number_format($amount, 2) . '.',
                'support.php?id=' . $campaign_id,
                'fa-hand-holding-heart'
            );
        } catch (Throwable $notifyError) {
            error_log('Notification create failed (payment received): ' . $notifyError->getMessage());
        }
    }

    if ($cap_info && $cap_info['raised_amount'] >= $cap_info['target_amount']) {
        $stmt_stat = $pdo->prepare("UPDATE campaigns SET status = 'completed' WHERE id = ?");
        $stmt_stat->execute([$campaign_id]);

        if ((int) ($cap_info['created_by'] ?? 0) > 0) {
            try {
                create_notification(
                    $pdo,
                    (int) $cap_info['created_by'],
                    'campaign_completed',
                    'Campaign goal reached',
                    'Great news. "' . (string) ($cap_info['title'] ?? 'Campaign') . '" has reached its target and is now completed.',
                    'support.php?id=' . $campaign_id,
                    'fa-trophy'
                );
            } catch (Throwable $notifyError) {
                error_log('Notification create failed (campaign completed): ' . $notifyError->getMessage());
            }
        }
    }

    $pdo->commit();
    redirect('support.php?id=' . $campaign_id, 'Thank you. Your Stripe payment was successful!', 'success');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (strpos($e->getMessage(), 'uniq_donations_stripe_session') !== false) {
        redirect('index.php', 'Payment already processed.', 'success');
    }

    redirect('index.php', 'Database payment processing failed: ' . $e->getMessage(), 'error');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirect('index.php', 'Stripe verification failed: ' . $e->getMessage(), 'error');
}
