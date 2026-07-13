<?php
/**
 * EcoRise - Dummy Mobile Wallet Payment Page
 */
require_once 'config.php';
require_once __DIR__ . '/includes/public_nav.php';

if (!is_logged_in()) {
    redirect('signin.php', 'Please sign in to continue payment.', 'error');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$campaignId = filter_var($_GET['campaign_id'] ?? 0, FILTER_VALIDATE_INT);
$amount = filter_var($_GET['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
$method = strtolower(trim((string) ($_GET['method'] ?? '')));

$methodMap = [
    'rocket' => ['name' => 'Rocket', 'logo' => file_exists(__DIR__ . '/assets/logo/rocket.png') ? 'assets/logo/rocket.png' : 'assets/logo/rocket.svg'],
    'nagad' => ['name' => 'Nagad', 'logo' => file_exists(__DIR__ . '/assets/logo/nagad.png') ? 'assets/logo/nagad.png' : 'assets/logo/nagad.svg'],
    'bkash' => ['name' => 'bKash', 'logo' => file_exists(__DIR__ . '/assets/logo/bkash.png') ? 'assets/logo/bkash.png' : 'assets/logo/bkash.svg'],
];

if (!$campaignId || !$amount || $amount < 1 || !isset($methodMap[$method])) {
    redirect('index.php', 'Invalid payment request.', 'error');
}

$stmt = $pdo->prepare('SELECT id, title, status, approval_status FROM campaigns WHERE id = ?');
$stmt->execute([$campaignId]);
$campaign = $stmt->fetch();

if (!$campaign || $campaign['status'] !== 'active' || $campaign['approval_status'] !== 'approved') {
    redirect('index.php', 'Campaign not available for payment.', 'error');
}

$wallet = $methodMap[$method];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($wallet['name']); ?> Payment | EcoRise</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <style>
        .mobile-pay-wrap {
            max-width: 680px;
            margin: 40px auto;
        }
        .mobile-pay-card {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 24px;
            box-shadow: 0 20px 48px rgba(15, 23, 42, 0.10);
            padding: 28px;
        }
        .mobile-pay-head {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }
        .mobile-pay-head img {
            width: 54px;
            height: 54px;
            border-radius: 12px;
            object-fit: cover;
        }
        .mobile-pay-list {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 16px;
            margin: 16px 0 20px;
        }
        .mobile-pay-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .mobile-pay-row:last-child { border-bottom: 0; }
    </style>
</head>
<body class="w3-light-grey">
    <?php render_public_nav('opportunities'); ?>

    <div class="w3-container">
        <div class="mobile-pay-wrap">
            <div class="mobile-pay-card">
                <div class="mobile-pay-head">
                    <img src="<?php echo e($wallet['logo']); ?>" alt="<?php echo e($wallet['name']); ?>">
                    <div>
                        <h2 class="w3-xlarge" style="margin:0; font-weight:800;"><?php echo e($wallet['name']); ?> Payment</h2>
                        <div class="w3-small w3-text-gray">Dummy payment gateway page</div>
                    </div>
                </div>

                <div class="mobile-pay-list">
                    <div class="mobile-pay-row"><span>Campaign</span><strong><?php echo e((string) $campaign['title']); ?></strong></div>
                    <div class="mobile-pay-row"><span>Amount</span><strong>BDT <?php echo e(number_format((float) $amount, 2)); ?></strong></div>
                    <div class="mobile-pay-row"><span>Wallet</span><strong><?php echo e($wallet['name']); ?></strong></div>
                </div>

                <div class="w3-panel w3-pale-yellow w3-round-large" style="margin: 0 0 16px 0;">
                    <p class="w3-small" style="margin: 10px 0;"><strong>Demo mode:</strong> This is a dummy wallet payment page for UI demonstration.</p>
                </div>

                <div class="w3-row-padding" style="margin: 0 -8px;">
                    <div class="w3-half" style="padding: 0 8px;">
                        <a href="support.php?id=<?php echo (int) $campaignId; ?>" class="w3-button w3-border w3-round-xlarge w3-block">Back</a>
                    </div>
                    <div class="w3-half" style="padding: 0 8px;">
                        <a href="https://checkout.stripe.com/c/pay/cs_test_a1JIqCqwke9Ql8cg0s4tdA7QqUydQzDTkdxkjmk2gBVGf0twIY74wEY1X2#fidnandhYHdWcXxpYCc%2FJ2FgY2RwaXEnKSdkdWxOYHwnPyd1blpxYHZxWjA0UU1Wd2JPMkZoMXZqUDFCcUl%2FVnJBYWpJaVAzX39MRDVOXU58TTA0R01td39MdGBKX1NARj08alB9b1ZhPG5Xd1VuN0JjVmtMYFZuUHMwdldUSnc1QGxBNTV9R1VjYjRoSCcpJ2N3amhWYHdzYHcnP3F3cGApJ2dkZm5id2pwa2FGamlqdyc%2FJyZjY2NjY2MnKSdpZHxqcHFRfHVgJz8ndmxrYmlgWmxxYGgnKSdga2RnaWBVaWRmYG1qaWFgd3YnP3F3cGB4JSUl" class="w3-button w3-green w3-round-xlarge w3-block">Confirm Payment</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
