<?php
/**
 * EcoRise - Register Page
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
    <title>Join EcoRise | Secure Your Planet's Future</title>
    <!-- W3.CSS for responsiveness -->
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body.signup-page {
            background:
                radial-gradient(950px 520px at top right, rgba(16, 185, 129, 0.14), transparent 58%),
                radial-gradient(760px 460px at -10% 10%, rgba(34, 211, 238, 0.12), transparent 60%),
                linear-gradient(180deg, #f8fafc 0%, #eefbf4 100%);
            color: #0f172a;
        }

        .signup-card {
            max-width: 820px;
            margin: 48px auto 72px;
            padding: 44px;
            border-radius: 28px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.10);
        }

        .signup-card h2 {
            color: #0f172a;
            font-size: 1.85rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .signup-card p,
        .signup-card .w3-text-gray {
            color: #475569 !important;
        }

        .signup-card label {
            color: #334155;
            font-weight: 700;
        }

        .signup-card .form-control {
            color: #0f172a;
            background: #f8fafc;
            border-color: #dbe4ee;
        }

        .signup-card .form-control::placeholder {
            color: #94a3b8;
        }

        .signup-card .form-control:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
        }

        .upload-panel {
            background: linear-gradient(180deg, rgba(16,185,129,0.08), rgba(255,255,255,0.8));
            border: 1px dashed rgba(16,185,129,0.28);
            border-radius: 20px;
            padding: 18px;
        }

        .upload-panel input[type="file"] {
            padding: 12px 14px;
            background: #ffffff;
        }

        .location-box {
            background: linear-gradient(180deg, #f8fffb 0%, #f0fdf4 100%);
            border: 1px solid rgba(16, 185, 129, 0.18);
            border-radius: 20px;
            padding: 18px;
            margin-top: 8px;
        }

        .location-status {
            font-size: 0.92rem;
            color: #475569;
            margin-top: 10px;
            line-height: 1.5;
        }

        .location-preview {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .location-box .btn-primary {
            width: 100%;
            max-width: 260px;
        }

        .location-preview input {
            background: #fff;
        }

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

        @media (max-width: 640px) {
            .signup-card {
                margin: 20px auto 40px;
                padding: 24px;
                border-radius: 22px;
            }

            .location-preview {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="w3-light-grey signup-page">
    <?php render_public_nav(); ?>

    <div class="w3-container">
        <div class="form-card signup-card animate-in w3-white">
            <div class="w3-center w3-margin-bottom">
                <a href="index.php" class="w3-xlarge" style="text-decoration:none; font-weight:800; color:#10b981;">
                    <i class="fas fa-leaf"></i> EcoRise
                </a>
                <h2 class="w3-large w3-margin-top">Join Our Mission</h2>
                <p class="w3-text-gray">Start supporting and creating environmental impact.</p>
            </div>

            <!-- Feedback Message -->
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="w3-panel w3-<?php echo $_SESSION['msg_type'] === 'error' ? 'red' : 'green'; ?> w3-round-large w3-padding-16">
                    <p><?php echo $_SESSION['msg']; unset($_SESSION['msg'], $_SESSION['msg_type']); ?></p>
                </div>
            <?php endif; ?>

            <form action="process_signup.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" id="latitude" name="latitude" value="">
                <input type="hidden" id="longitude" name="longitude" value="">
                <input type="hidden" id="email_verified" name="email_verified" value="0">
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" placeholder="John Doe" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="example@ecorise.com" required>
                </div>

                <div class="form-group">
                    <label>Email Verification</label>
                    <div class="otp-box">
                        <div id="otp-pill" class="otp-pill"><i class="fas fa-shield-alt"></i> Not verified</div>
                        <div class="otp-actions">
                            <button type="button" id="send-otp-btn" class="btn-primary w3-button w3-round-xlarge">
                                <i class="fas fa-paper-plane"></i> Send OTP
                            </button>
                        </div>
                        <div class="otp-actions">
                            <input type="text" id="otp_code" class="form-control" placeholder="Enter 6-digit OTP" maxlength="6" inputmode="numeric" pattern="\d{6}" style="max-width:220px;">
                            <button type="button" id="verify-otp-btn" class="btn-primary w3-button w3-round-xlarge">
                                <i class="fas fa-check-circle"></i> Verify OTP
                            </button>
                        </div>
                        <div id="otp-status" class="otp-status">Send OTP to your email and verify before signup.</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="profile_image">Profile Photo</label>
                    <div class="upload-panel">
                        <input type="file" id="profile_image" name="profile_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <div class="w3-small w3-text-gray" style="margin-top:10px;">Optional. Upload a clear JPG, PNG, or WEBP image to show on your dashboard right away after signup.</div>
                    </div>
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
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Current Location</label>
                    <div class="location-box">
                        <button type="button" id="get-location-btn" class="btn-primary w3-button w3-round-xlarge">
                            <i class="fas fa-location-crosshairs"></i> Use Current Location
                        </button>
                        <div class="location-preview">
                            <input type="text" id="latitude_preview" class="form-control" placeholder="Latitude" readonly>
                            <input type="text" id="longitude_preview" class="form-control" placeholder="Longitude" readonly>
                        </div>
                        <div id="location-status" class="location-status">
                            Click the button to share your current location during signup.
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary w3-block w3-button w3-large w3-margin-top">Sign Up</button>

                <div class="w3-center w3-padding-16">
                    <p class="w3-small">Already have an account? <a href="signin.php" class="w3-text-green"><b>Sign in</b></a></p>
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

        document.addEventListener('DOMContentLoaded', function() {
            var getLocationButton = document.getElementById('get-location-btn');
            var locationStatus = document.getElementById('location-status');
            var latitudeInput = document.getElementById('latitude');
            var longitudeInput = document.getElementById('longitude');
            var latitudePreview = document.getElementById('latitude_preview');
            var longitudePreview = document.getElementById('longitude_preview');
            var emailInput = document.getElementById('email');
            var otpCodeInput = document.getElementById('otp_code');
            var sendOtpButton = document.getElementById('send-otp-btn');
            var verifyOtpButton = document.getElementById('verify-otp-btn');
            var otpStatus = document.getElementById('otp-status');
            var otpPill = document.getElementById('otp-pill');
            var emailVerifiedInput = document.getElementById('email_verified');
            var signupForm = document.querySelector('form[action="process_signup.php"]');

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
                emailVerifiedInput.value = verified ? '1' : '0';
                otpPill.classList.toggle('is-verified', verified);
                otpPill.innerHTML = verified
                    ? '<i class="fas fa-circle-check"></i> Verified'
                    : '<i class="fas fa-shield-alt"></i> Not verified';
            }

            function normalizeEmail() {
                return (emailInput.value || '').trim().toLowerCase();
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

            function setLocationValues(latitude, longitude) {
                latitudeInput.value = latitude;
                longitudeInput.value = longitude;
                latitudePreview.value = latitude;
                longitudePreview.value = longitude;
            }

            function clearLocationValues() {
                latitudeInput.value = '';
                longitudeInput.value = '';
                latitudePreview.value = '';
                longitudePreview.value = '';
            }

            function isLocalHost() {
                return window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
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
                    setOtpStatus('Email verified successfully. You can now complete signup.', false, true);
                } catch (error) {
                    setVerifiedState(false);
                    setOtpStatus(error.message, true, false);
                } finally {
                    verifyOtpButton.disabled = false;
                }
            });

            signupForm.addEventListener('submit', function(event) {
                if (emailVerifiedInput.value !== '1') {
                    event.preventDefault();
                    setOtpStatus('Please verify your email with OTP before signup.', true, false);
                    otpCodeInput.focus();
                }
            });

            getLocationButton.addEventListener('click', function() {
                clearLocationValues();

                if (!navigator.geolocation) {
                    locationStatus.textContent = 'Geolocation is not supported by your browser.';
                    return;
                }

                if (!window.isSecureContext && !isLocalHost()) {
                    locationStatus.textContent = 'Current location works only on HTTPS or localhost. Open this page with localhost or enable HTTPS.';
                    return;
                }

                getLocationButton.disabled = true;
                locationStatus.textContent = 'Detecting your current location...';

                navigator.geolocation.getCurrentPosition(function(position) {
                    var latitude = position.coords.latitude.toFixed(7);
                    var longitude = position.coords.longitude.toFixed(7);

                    setLocationValues(latitude, longitude);
                    locationStatus.textContent = 'Location added successfully. Latitude: ' + latitude + ', Longitude: ' + longitude;
                    getLocationButton.disabled = false;
                }, function(error) {
                    var message = 'Unable to fetch location.';

                    if (error.code === 1) {
                        message = 'Location permission was denied. Please allow browser location access and try again.';
                    } else if (error.code === 2) {
                        message = 'Location information is unavailable on this device right now.';
                    } else if (error.code === 3) {
                        message = 'Location request timed out. Try again where GPS or network signal is stronger.';
                    }

                    if (!window.isSecureContext && !isLocalHost()) {
                        message += ' If you opened the site with an IP address, switch to localhost or HTTPS.';
                    }

                    locationStatus.textContent = message;
                    getLocationButton.disabled = false;
                }, {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 300000
                });
            });
        });
    </script>
</body>
</html>
