<?php
/**
 * EcoRise - Create Stripe Checkout Session
 */
require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php';

if (!is_logged_in()) {
    redirect('signin.php', 'Please sign in to support a campaign.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php', 'Invalid payment request.', 'error');
}

if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    redirect('index.php', 'Invalid session. Please try again.', 'error');
}

$campaign_id = filter_var($_POST['campaign_id'] ?? 0, FILTER_VALIDATE_INT);
$amount = filter_var($_POST['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
$minimum_amount = 70;

if (!$campaign_id || !$amount || $amount <= 0) {
    redirect('index.php', 'Please enter a valid payment amount.', 'error');
}

if ($amount < $minimum_amount) {
    redirect('support.php?id=' . $campaign_id, 'Minimum donation is BDT ' . $minimum_amount . ' for card checkout.', 'error');
}

$stmt = $pdo->prepare("SELECT id, title, status FROM campaigns WHERE id = ?");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    redirect('index.php', 'Campaign not found.', 'error');
}

if ($campaign['status'] === 'pending') {
    redirect('support.php?id=' . $campaign_id, 'This campaign is not currently accepting donations.', 'error');
}

try {
    $stripe = new \Stripe\StripeClient(STRIPE_SECRET_KEY);

    $session = $stripe->checkout->sessions->create([
        'mode' => 'payment',
        'payment_method_types' => ['card'],
        'line_items' => [[
            'quantity' => 1,
            'price_data' => [
                'currency' => 'bdt',
                'unit_amount' => (int) round($amount * 100),
                'product_data' => [
                    'name' => 'Donation for ' . $campaign['title'],
                ],
            ],
        ]],
        'metadata' => [
            'user_id' => (string) $_SESSION['user_id'],
            'campaign_id' => (string) $campaign_id,
            'amount' => number_format($amount, 2, '.', ''),
        ],
        'success_url' => app_base_url() . '/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => app_base_url() . '/support.php?id=' . $campaign_id,
    ]);

    header('Location: ' . $session->url);
    exit();
} catch (Exception $e) {
    redirect('support.php?id=' . $campaign_id, 'Payment session failed: ' . $e->getMessage(), 'error');
}
