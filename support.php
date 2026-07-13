<?php
/**
 * EcoRise - Support Page
 * 
 * Handles donations for a specific campaign.
 */
require_once 'config.php';
require_once __DIR__ . '/includes/public_nav.php';

// Redirect to signin if not logged in
if (!is_logged_in()) {
    redirect('signin.php', 'Please sign in to support a campaign.', 'error');
}

$id = filter_var($_GET['id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);

// CSRF Token
$csrf_token = generate_csrf_token();

// Fetch campaign details
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ?");
    $stmt->execute([$id]);
    $campaign = $stmt->fetch();
} else {
    redirect('index.php', 'Invalid campaign selected.', 'error');
}

if (!$campaign) {
    redirect('index.php', 'Campaign not found.', 'error');
}

// Check if campaign is approved
if ($campaign['approval_status'] !== 'approved') {
    redirect('index.php', 'This campaign is not yet approved. Please check back later.', 'warning');
}

$walletLogos = [
    'rocket' => file_exists(__DIR__ . '/assets/logo/rocket.png') ? 'assets/logo/rocket.png' : 'assets/logo/rocket.svg',
    'nagad' => file_exists(__DIR__ . '/assets/logo/nagad.png') ? 'assets/logo/nagad.png' : 'assets/logo/nagad.svg',
    'bkash' => file_exists(__DIR__ . '/assets/logo/bkash.png') ? 'assets/logo/bkash.png' : 'assets/logo/bkash.svg',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support <?php echo htmlspecialchars($campaign['title']); ?> | EcoRise</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .wallet-dummy-grid {
            display: grid;
            gap: 10px;
            margin-top: 14px;
        }

        .wallet-dummy-btn {
            width: 100%;
            border: 1px solid #dbe4ee;
            border-radius: 14px;
            padding: 11px 12px;
            background: #f8fafc;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 700;
            cursor: pointer;
            opacity: 1;
            transition: all 0.2s ease;
        }

        .wallet-dummy-btn:hover {
            border-color: #86efac;
            background: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .wallet-dummy-left {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .wallet-logo {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.01em;
        }

        .wallet-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
            display: block;
        }

        .wallet-logo--bkash {
            background: linear-gradient(135deg, #e2136e, #b80f58);
        }

        .wallet-logo--nagad {
            background: linear-gradient(135deg, #ff7a00, #e56000);
        }

        .wallet-logo--rocket {
            background: linear-gradient(135deg, #7b1fa2, #5e1380);
        }

        .wallet-tag {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 600;
        }
    </style>
</head>
<body class="w3-light-grey">
    <?php render_public_nav('opportunities'); ?>

    <!-- Project Hero Header -->
    <div class="support-hero w3-container w3-center w3-padding-64" style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('<?php echo $campaign['image_path']; ?>') no-repeat center center; background-size: cover; color: white; min-height: 300px; display: flex; flex-direction: column; justify-content: center;">
        <div class="w3-content">
            <span class="w3-tag w3-green w3-round-large w3-margin-bottom"><?php echo strtoupper($campaign['status']); ?></span>
            <h1 class="w3-xxlarge" style="font-weight: 800; color: #ffffff; text-shadow: 0 4px 10px rgba(0,0,0,0.4);"><?php echo htmlspecialchars($campaign['title']); ?></h1>
            <p class="w3-large" style="color: #f8fafc;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($campaign['district']); ?>, <?php echo htmlspecialchars($campaign['division']); ?></p>
        </div>
    </div>

    <div class="w3-container w3-content w3-padding-64" style="max-width: 1200px;">
        <div class="w3-row-padding">
            <!-- Left: Campaign Content -->
            <div class="w3-col m8">
                <div class="card-premium">
                    <img src="<?php echo $campaign['image_path']; ?>" alt="<?php echo $campaign['title']; ?>" style="width:100%; border-radius:30px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); margin-bottom: 32px;" onerror="this.src='https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=800&auto=format&fit=crop'">
                    <h2 class="w3-margin-bottom w3-xxlarge">About this mission</h2>
                    <p class="w3-large w3-text-gray" style="line-height: 1.8; font-weight: 400;"><?php echo nl2br(htmlspecialchars($campaign['description'])); ?></p>
                    
                    <div class="w3-panel w3-leftbar w3-light-gray w3-padding-16 w3-margin-top">
                         <p class="w3-small w3-text-gray">Fundraising for environmental causes requires collective transparency. By supporting this campaign, you agree to our terms of service and our commitment to verifiable ecological impact.</p>
                    </div>
                </div>
            </div>

            <!-- Right: Funding Sidebar -->
            <div class="w3-col m4">
                <div class="card-premium">
                    <h3 class="w3-center w3-xlarge w3-margin-bottom">Contribute Now</h3>
                    
                    <?php 
                        $percent = min(($campaign['raised_amount'] / $campaign['target_amount']) * 100, 100);
                    ?>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?php echo $percent; ?>%"></div>
                    </div>
                    <div class="campaign-info">
                        <span class="w3-xlarge" style="color:var(--dark)">৳<?php echo number_format($campaign['raised_amount']); ?></span>
                        <span class="w3-text-green"><?php echo round($percent, 1); ?>%</span>
                    </div>
                    <p class="w3-small w3-text-gray w3-margin-bottom" style="font-weight: 600;">Raised of ৳<?php echo number_format($campaign['target_amount']); ?> Goal</p>

                    <!-- Feedback -->
                    <?php if (isset($_SESSION['msg'])): ?>
                        <div class="w3-panel w3-<?php echo $_SESSION['msg_type'] === 'error' ? 'red' : 'green'; ?> w3-round-large w3-padding-16">
                            <p><?php echo $_SESSION['msg']; unset($_SESSION['msg'], $_SESSION['msg_type']); ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="create_checkout_session.php" method="POST" style="margin-top: 32px;">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="campaign_id" value="<?php echo (int) $id; ?>">
                        <div class="form-group">
                            <label for="amount" class="w3-small w3-text-gray">Support Amount (৳)</label>
                            <input type="number" id="amount" name="amount" class="form-control" min="70" step="0.01" placeholder="Minimum 70" required>
                        </div>
                        <button type="submit" class="btn-primary w3-block w3-button w3-large w3-margin-top" style="padding: 18px !important; border-radius: 15px !important;">Pay Securely with Card</button>
                    </form>

                    <div class="wallet-dummy-grid" aria-label="Dummy mobile wallet options">
                        <button type="button" class="wallet-dummy-btn wallet-pay-btn" data-method="rocket" data-campaign-id="<?php echo (int) $id; ?>">
                            <span class="wallet-dummy-left">
                                <span class="wallet-logo"><img src="<?php echo htmlspecialchars($walletLogos['rocket']); ?>" alt="Rocket"></span>
                                <span>Rocket</span>
                            </span>
                            <span class="wallet-tag">Pay</span>
                        </button>
                        <button type="button" class="wallet-dummy-btn wallet-pay-btn" data-method="nagad" data-campaign-id="<?php echo (int) $id; ?>">
                            <span class="wallet-dummy-left">
                                <span class="wallet-logo"><img src="<?php echo htmlspecialchars($walletLogos['nagad']); ?>" alt="Nagad"></span>
                                <span>Nagad</span>
                            </span>
                            <span class="wallet-tag">Pay</span>
                        </button>
                        <button type="button" class="wallet-dummy-btn wallet-pay-btn" data-method="bkash" data-campaign-id="<?php echo (int) $id; ?>">
                            <span class="wallet-dummy-left">
                                <span class="wallet-logo"><img src="<?php echo htmlspecialchars($walletLogos['bkash']); ?>" alt="bKash"></span>
                                <span>bKash</span>
                            </span>
                            <span class="wallet-tag">Pay</span>
                        </button>
                    </div>

                    <p class="w3-small w3-text-gray w3-center w3-margin-top">Minimum amount for card checkout: BDT 70. Test mode card: 4242 4242 4242 4242</p>
                    
                    <div class="w3-center w3-padding-24">
                       <p class="w3-small w3-text-gray"><i class="fas fa-lock"></i> Secure Payment</p>
                       <div class="w3-padding">
                           <i class="fab fa-cc-visa w3-xlarge w3-margin-right w3-text-gray"></i>
                           <i class="fab fa-cc-mastercard w3-xlarge w3-margin-right w3-text-gray"></i>
                           <i class="fab fa-paypal w3-xlarge w3-text-gray"></i>
                       </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.wallet-pay-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                var amountInput = document.getElementById('amount');
                var amount = parseFloat(amountInput ? amountInput.value : '0');
                var campaignId = button.getAttribute('data-campaign-id');
                var method = button.getAttribute('data-method');

                if (!amount || amount < 70) {
                    alert('Please enter at least BDT 70 before selecting a wallet.');
                    if (amountInput) {
                        amountInput.focus();
                    }
                    return;
                }

                var target = 'mobile_payment.php?campaign_id=' + encodeURIComponent(campaignId) +
                    '&method=' + encodeURIComponent(method) +
                    '&amount=' + encodeURIComponent(amount.toFixed(2));

                window.location.href = target;
            });
        });
    </script>
</body>
</html>
