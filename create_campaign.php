<?php
/**
 * EcoRise - Create Campaign
 * 
 * Allows users to start a new crowdfunding campaign.
 */
require_once 'config.php';
require_once __DIR__ . '/includes/public_nav.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('signin.php', 'Please sign in to start a project.', 'error');
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start a Project | EcoRise</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 60px auto;
            background: white;
            padding: 40px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
        }
    </style>
</head>
<body class="w3-light-grey">
    <?php render_public_nav('project-create'); ?>

    <div class="w3-container w3-padding-64">
        <div class="form-container animate-in">
            <div class="w3-center w3-margin-bottom">
                <h1 class="w3-xxlarge">Launch Your Eco-Project</h1>
                <p class="w3-text-gray">Fill in the details below to start raising funds for your environmental initiative.</p>
            </div>

            <?php if (isset($_SESSION['msg'])): ?>
                <div class="w3-panel w3-<?php echo $_SESSION['msg_type'] === 'error' ? 'red' : 'green'; ?> w3-round-large w3-padding-16">
                    <p><?php echo $_SESSION['msg']; unset($_SESSION['msg'], $_SESSION['msg_type']); ?></p>
                </div>
            <?php endif; ?>

            <form action="process_create_campaign.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="title">Project Title</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="e.g., Sundarbans Reforestation Drive" required>
                </div>

                <div class="form-group">
                    <label for="description">Project Description</label>
                    <textarea id="description" name="description" class="form-control" rows="5" placeholder="Describe your mission, goals, and how the funds will be used..." required></textarea>
                </div>

                <div class="w3-row-padding" style="margin:0 -16px">
                    <div class="w3-half form-group">
                        <label for="division">Select Division</label>
                        <select id="division" name="division" class="form-control" required onchange="updateDistricts()">
                            <option value="">Select Division</option>
                            <option value="Dhaka">Dhaka</option>
                            <option value="Khulna">Khulna</option>
                            <option value="Chittagong">Chittagong</option>
                            <option value="Rajshahi">Rajshahi</option>
                            <option value="Sylhet">Sylhet</option>
                            <option value="Rangpur">Rangpur</option>
                            <option value="Mymensingh">Mymensingh</option>
                            <option value="Barisal">Barisal</option>
                        </select>
                    </div>
                    <div class="w3-half form-group">
                        <label for="district">Select District</label>
                        <select id="district" name="district" class="form-control" required disabled>
                            <option value="">Select Division First</option>
                        </select>
                    </div>
                </div>

                <div class="w3-row-padding" style="margin:0 -16px">
                    <div class="w3-half form-group">
                        <label for="target_amount">Target Goal (৳)</label>
                        <input type="number" id="target_amount" name="target_amount" class="form-control" step="0.01" min="1" placeholder="5000" required>
                    </div>
                    <div class="w3-half form-group">
                        <label for="image">Project Banner Image</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*" required>
                        <p class="w3-tiny w3-text-gray">Landscape orientation (16:10) works best.</p>
                    </div>
                </div>

                <button type="submit" class="btn-primary w3-block w3-button w3-large w3-margin-top">Launch Project</button>
            </form>
        </div>
    </div>

    <script>
        const locationData = {
            "Dhaka": ["Dhaka", "Faridpur", "Gazipur", "Gopalganj", "Kishoreganj", "Madaripur", "Manikganj", "Munshiganj", "Narayanganj", "Narsingdi", "Rajbari", "Shariatpur", "Tangail"],
            "Khulna": ["Bagerhat", "Chuadanga", "Jessore", "Jhenaidah", "Khulna", "Kushtia", "Magura", "Meherpur", "Narail", "Satkhira"],
            "Chittagong": ["Bandarban", "Brahmanbaria", "Chandpur", "Chittagong", "Comilla", "Cox's Bazar", "Feni", "Khagrachhari", "Lakshmipur", "Noakhali", "Rangamati"],
            "Rajshahi": ["Bogra", "Joypurhat", "Naogaon", "Natore", "Chapainawabganj", "Pabna", "Rajshahi", "Sirajganj"],
            "Sylhet": ["Habiganj", "Moulvibazar", "Sunamganj", "Sylhet"],
            "Rangpur": ["Dinajpur", "Gaibandha", "Kurigram", "Lalmonirhat", "Nilphamari", "Panchagarh", "Rangpur", "Thakurgaon"],
            "Mymensingh": ["Jamalpur", "Mymensingh", "Netrokona", "Sherpur"],
            "Barisal": ["Jhalokati", "Barguna", "Barisal", "Bhola", "Patuakhali", "Pirojpur"]
        };

        function updateDistricts() {
            const division = document.getElementById("division").value;
            const districtSelect = document.getElementById("district");
            
            districtSelect.innerHTML = '<option value="">Select District</option>';
            
            if (division && locationData[division]) {
                locationData[division].forEach(district => {
                    const option = document.createElement("option");
                    option.value = district;
                    option.text = district;
                    districtSelect.appendChild(option);
                });
                districtSelect.disabled = false;
            } else {
                districtSelect.disabled = true;
                districtSelect.innerHTML = '<option value="">Select Division First</option>';
            }
        }
    </script>
</body>
</html>
