<?php
/**
 * EcoRise - Sign In Page
 */
require_once 'config.php';
require_once __DIR__ . '/includes/public_nav.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | EcoRise</title>
    <!-- W3.CSS for responsiveness -->
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .otp-box {
            background: linear-gradient(180deg, #f8fffb 0%, #f0fdf4 100%);
            border: 1px solid rgba(16, 185, 129, 0.18);
            border-radius: 20px;
            padding: 18px;
            margin-top: 8px;
        }

        .otp-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .otp-status {
            font-size: 0.92rem;
            color: #475569;
            margin-top: 10px;
            line-height: 1.5;
        }

        .otp-status.is-success {
            color: #047857;
        }

        .otp-status.is-error {
            color: #b91c1c;
        }

        .otp-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
            background: #eef2ff;
            color: #4338ca;
        }

        .otp-pill.is-verified {
            background: #dcfce7;
            color: #166534;
        }
    </style>
</head>
<body class="w3-light-grey">
    <?php render_public_nav(); ?>

    <div class="w3-container">
        <div class="form-card animate-in w3-white">
            <div class="w3-center w3-margin-bottom">
                <a href="index.php" class="w3-xlarge w3-text-green" style="text-decoration:none; font-weight:800;">
                    <i class="fas fa-leaf"></i> EcoRise
                </a>
                <h2 class="w3-large w3-margin-top">Welcome Back to EcoRise</h2>
                <p class="w3-text-gray">Please sign in to continue supporting the planet.</p>
            </div>

            <!-- Feedback Message -->
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="w3-panel w3-<?php echo $_SESSION['msg_type'] === 'error' ? 'red' : 'green'; ?> w3-round-large w3-padding-16">
                    <p><?php echo $_SESSION['msg']; unset($_SESSION['msg'], $_SESSION['msg_type']); ?></p>
                </div>
            <?php endif; ?>

            <form action="process_signin.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" id="signin_email_verified" name="signin_email_verified" value="0">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="example@ecorise.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" data-target="password" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email OTP Verification (required for all users except admin@ecorise.com)</label>
                    <div class="otp-box">
                        <div id="signin-otp-pill" class="otp-pill"><i class="fas fa-shield-alt"></i> Not verified</div>
                        <div class="otp-actions">
                            <button type="button" id="signin-send-otp-btn" class="btn-primary w3-button w3-round-xlarge">
                                <i class="fas fa-paper-plane"></i> Send OTP
                            </button>
                        </div>
                        <div class="otp-actions">
                            <input type="text" id="signin_otp_code" class="form-control" placeholder="Enter 6-digit OTP" maxlength="6" inputmode="numeric" pattern="\d{6}" style="max-width:220px;">
                            <button type="button" id="signin-verify-otp-btn" class="btn-primary w3-button w3-round-xlarge">
                                <i class="fas fa-check-circle"></i> Verify OTP
                            </button>
                        </div>
                        <div id="signin-otp-status" class="otp-status">Verify OTP before sign in. Only admin@ecorise.com can sign in without OTP.</div>
                    </div>
                </div>

                <button type="submit" class="btn-primary w3-block w3-button w3-large w3-margin-top">Sign In</button>

                <div class="w3-center w3-padding-16">
                    <p class="w3-small">Don't have an account? <a href="signup.php" class="w3-text-green"><b>Join today</b></a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('.password-toggle').forEach(function(button) {
            button.addEventListener('click', function() {
                var input = document.getElementById(button.getAttribute('data-target'));
                var icon = button.querySelector('i');
                var show = input.type === 'password';

                input.type = show ? 'text' : 'password';
                button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);
            });
        });

        (function() {
            var emailInput = document.getElementById('email');
            var otpCodeInput = document.getElementById('signin_otp_code');
            var sendOtpButton = document.getElementById('signin-send-otp-btn');
            var verifyOtpButton = document.getElementById('signin-verify-otp-btn');
            var otpStatus = document.getElementById('signin-otp-status');
            var otpPill = document.getElementById('signin-otp-pill');
            var verifiedFlagInput = document.getElementById('signin_email_verified');
            var signinForm = document.querySelector('form[action="process_signin.php"]');

            function normalizeEmail() {
                return (emailInput.value || '').trim().toLowerCase();
            }

            function setOtpStatus(message, isError, isSuccess) {
                otpStatus.textContent = message;
                otpStatus.classList.remove('is-error', 'is-success');
                if (isError) {
                    otpStatus.classList.add('is-error');
                }
                if (isSuccess) {
                    otpStatus.classList.add('is-success');
                }
            }

            function setVerifiedState(verified) {
                verifiedFlagInput.value = verified ? '1' : '0';
                otpPill.classList.toggle('is-verified', verified);
                otpPill.innerHTML = verified
                    ? '<i class="fas fa-circle-check"></i> Verified'
                    : '<i class="fas fa-shield-alt"></i> Not verified';
            }

            async function postForm(url, data) {
                var body = new URLSearchParams(data);
                var response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: body.toString()
                });

                var payload;
                try {
                    payload = await response.json();
                } catch (error) {
                    payload = { success: false, message: 'Server returned an invalid response.' };
                }

                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Request failed.');
                }

                return payload;
            }

            emailInput.addEventListener('input', function() {
                setVerifiedState(false);
            });

            sendOtpButton.addEventListener('click', async function() {
                var email = normalizeEmail();
                if (!email) {
                    setOtpStatus('Please enter your email first.', true, false);
                    return;
                }

                sendOtpButton.disabled = true;
                setOtpStatus('Sending OTP to your email...', false, false);

                try {
                    await postForm('OTP send/Send OTP Code.php', { email: email });
                    setVerifiedState(false);
                    setOtpStatus('OTP sent. Check inbox and spam folder, then enter the code.', false, true);
                } catch (error) {
                    setOtpStatus(error.message, true, false);
                } finally {
                    sendOtpButton.disabled = false;
                }
            });

            verifyOtpButton.addEventListener('click', async function() {
                var email = normalizeEmail();
                var otpCode = (otpCodeInput.value || '').replace(/\D+/g, '');

                if (!email) {
                    setOtpStatus('Please enter your email first.', true, false);
                    return;
                }

                if (otpCode.length !== 6) {
                    setOtpStatus('Please enter the 6-digit OTP.', true, false);
                    return;
                }

                verifyOtpButton.disabled = true;
                setOtpStatus('Verifying OTP...', false, false);

                try {
                    await postForm('OTP send/Verify OTP Code.php', { email: email, otp: otpCode });
                    setVerifiedState(true);
                    setOtpStatus('Email verified successfully. You can now sign in.', false, true);
                } catch (error) {
                    setVerifiedState(false);
                    setOtpStatus(error.message, true, false);
                } finally {
                    verifyOtpButton.disabled = false;
                }
            });

            signinForm.addEventListener('submit', function(event) {
                if (normalizeEmail() === 'admin@ecorise.com') {
                    return;
                }

                if (verifiedFlagInput.value !== '1') {
                    event.preventDefault();
                    setOtpStatus('Please verify OTP before signing in.', true, false);
                    otpCodeInput.focus();
                }
            });
        })();
    </script>
</body>
</html>
